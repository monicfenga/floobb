<?php

defined('FLOO') or die('FlooBB');

/**
 * 动态控制器（Phase 2，SPEC §3.1 / §9 #17）
 * - index        GET /activity             → 转发 timeline()（Router 首段直达坑：无第 2 段时 action=index）
 * - timeline     GET /activity（同上语义）  时间线：?page= 翻页、?type= 筛选（白名单）、?tab=following 关注流
 * - timelinePost POST /activity            发纯文本动态（type=status，登录态 + CSRF，可见性取 default_visibility）
 * - commentPost  POST /activity/comment/{aid}  评论动态（type=comment，ref_id=父活动，可见性继承父）
 * 三位置契约：/activity/comment/{aid} → commentPost('{aid}')
 */
class ActivityController {

	public function index() {
		self::timeline();
	}

	/** POST /activity（action=index 的方法感知回退：indexPost 不存在会掉回 GET 的 index()） */
	public function indexPost() {
		self::timelinePost();
	}

	public function timeline() {
		global $conf, $header;

		// 筛选与翻页（query string，SPEC §3.1）
		$type = Router::query('type', '');
		if (!in_array($type, Activity::TYPES, true)) $type = null;
		$page = max(1, (int) Router::query('page', 1));
		$tab = Router::query('tab', '') === 'following' ? 'following' : 'all';

		$viewer = Auth::user();
		$viewerName = $viewer !== null ? (string) $viewer['username'] : null;

		// 关注流（登录态限定；游客强制回全部流）
		$followOnlyFor = ($tab === 'following' && $viewerName !== null) ? $viewerName : null;

		$data = Activity::listTimeline($page, $type, $viewerName, $followOnlyFor);

		// 组装渲染行：动作文案/关联链接/头像 + 子评论（挂父卡片下方，不单独占卡片）
		$items = array();
		foreach ($data['items'] as $a) {
			$row = self::formatActivity($a, $viewerName);
			if ($a['type'] === 'status' || $a['type'] === 'comment') {
				foreach (Activity::listComments((string) $a['id']) as $c) {
					if (Activity::canView($c, $viewerName)) {
						$row['comments'][] = self::formatActivity($c, $viewerName);
					}
				}
			}
			$items[] = $row;
		}

		// 分页链接保留当前 tab/type（Paginate::pageLink 在 $loc 后拼 page=N）
		$locQuery = array();
		if ($tab === 'following') $locQuery[] = 'tab=following';
		if ($type !== null) $locQuery[] = 'type=' . rawurlencode($type);
		$loc = 'activity' . (!empty($locQuery) ? '?' . implode('&', $locQuery) . '&' : '?');
		$pages = max(1, (int) ceil($data['total'] / Activity::PER_PAGE));

		$header['title'] = '动态';
		self::render('activity_timeline', array(
			'items' => $items,
			'total' => $data['total'],
			'page' => $page,
			'pages' => $pages,
			'loc' => $loc,
			'tab' => $tab,
			'type' => $type,
			'viewer' => $viewer,
		));
	}

	/** 活动 → 模板渲染行（头像/昵称/动作文案/时间/关联链接） */
	private static function formatActivity(array $a, $viewerName) {
		$actor = (string) ($a['actor'] ?? '');
		$actorUser = User::get($actor);
		$nickname = (string) ($actorUser['nickname'] ?? $actor);
		$avatar = '';
		if (!empty($actorUser['avatar'])) {
			$avatar = 'uploads/avatars/' . rawurlencode((string) $actorUser['avatar']);
		}

		// 动作文案 + 关联链接（按 type；KICKOFF：actor 昵称、动作、关联链接可达）
		switch ($a['type'] ?? '') {
			case 'register':
				$action = '加入了本站';
				$link = Router::url('user/view/' . rawurlencode($actor));
				break;
			case 'new_topic':
				$topic = Topic::get((string) ($a['ref_id'] ?? ''));
				$action = '发布了主题《' . (string) ($topic['title'] ?? '（已删除）') . '》';
				$link = $topic !== null ? Router::url('topic/view/' . rawurlencode((string) $a['ref_id'])) : '';
				break;
			case 'new_reply':
				$topic = Topic::get((string) ($a['ref_id'] ?? ''));
				$action = '在《' . (string) ($topic['title'] ?? '（已删除）') . '》中回帖';
				$link = $topic !== null ? Router::url('topic/view/' . rawurlencode((string) $a['ref_id'])) : '';
				break;
			case 'follow':
				$action = '关注了 ' . (string) ($a['ref_id'] ?? '');
				$link = Router::url('user/view/' . rawurlencode((string) ($a['ref_id'] ?? '')));
				break;
			case 'comment':
				$action = '评论了动态';
				$link = ''; // 评论不单独跳转，挂父卡片下方
				break;
			case 'status':
			default:
				$action = '发布了动态';
				$link = '';
				break;
		}

		return array(
			'id' => (string) ($a['id'] ?? ''),
			'type' => (string) ($a['type'] ?? 'status'),
			'actor' => $actor,
			'nickname' => $nickname,
			'avatar' => $avatar,
			'action' => $action,
			'text' => (string) ($a['text'] ?? ''),
			'link' => $link,
			'created' => (int) ($a['created'] ?? 0),
			'likes' => (int) ($a['likes'] ?? 0),
			'liked' => $viewerName !== null && in_array((string) $viewerName, $a['liked_by'] ?? array(), true),
			'comments' => array(),
		);
	}

	/** POST /activity 发动态 */
	public function timelinePost() {
		if (!Auth::csrfCheck()) self::forbidden();
		$viewer = Auth::user();
		if ($viewer === null) self::redirect('auth/login');

		$text = trim(param('text', ''));
		// 纯文本动态：strip_tags 后限长（与 Bio 同量级，200 字符）
		$text = trim(strip_tags($text));
		if ($text === '' || mb_strlen($text) > 200) {
			self::redirect('activity?error=invalid');
		}

		// 本次发布的可见度：表单下拉优先，非法值由 record() 回退到作者 default_visibility
		Activity::record('status', (string) $viewer['username'], $text, '', param('visibility', ''));
		self::redirect('activity');
	}

	/** POST /activity/comment/{aid} 评论动态 */
	public function commentPost($aid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$viewer = Auth::user();
		if ($viewer === null) self::redirect('auth/login');

		$parent = Activity::get((string) $aid);
		if ($parent === null) {
			http_response_code(404);
			header('Content-Type: text/plain; charset=utf-8');
			exit('404: 动态不存在。');
		}

		$text = trim(strip_tags(param('text', '')));
		if ($text === '' || mb_strlen($text) > 200) {
			self::redirect('activity?error=invalid');
		}

		// 评论可见性继承父活动（拍板：SPEC §4）
		Activity::record('comment', (string) $viewer['username'], $text, (string) $parent['id'], (string) ($parent['visibility'] ?? 'public'));
		self::redirect('activity');
	}

	/** POST /activity/like/{aid} 点赞 toggle（登录态 + CSRF；仅 status 动态） */
	public function likePost($aid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$viewer = Auth::user();
		if ($viewer === null) self::redirect('auth/login');

		Activity::toggleLike((string) $aid, (string) $viewer['username']);
		self::redirect('activity');
	}

	/* ---------------- 视图与响应辅助（与 UserController 同构） ---------------- */

	private static function render($tpl, array $vars = array()) {
		global $conf, $header, $token;
		extract($vars);

		if (empty($_SESSION['token'])) {
			$token = CSRF::generate();
		}
		$header['title'] = $header['title'] ?? ($conf['title'] ?? 'FlooBB');

		require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . $tpl . '.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
		exit;
	}

	private static function forbidden() {
		http_response_code(403);
		header('Content-Type: text/plain; charset=utf-8');
		exit('403 Forbidden: 权限不足或 CSRF token 无效。');
	}

	private static function redirect($path) {
		header('Location: ' . Router::url($path));
		exit;
	}
}
