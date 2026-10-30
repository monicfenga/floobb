<?php defined('FLOO') or die('FlooBB');

/**
 * 举报控制器（本批 4a：type 限 post|topic；type=user 举报在 4b 封禁体系做）
 *
 * - createPost POST /report/create/{type}/{target_id}  举报（登录 + CSRF；内联表单提交）
 * - list       GET  /report/list                       管理队列（level≥2；?tab=open|done）
 * - handlePost POST /report/handle/{id}                 处理（action=delete|dismiss；level≥2 + CSRF）
 *
 * 处理后用私信通知（操作版主当发件人，口径不同）：
 * - delete：通知举报者（已处理）+ 被举报者（内容被移除）
 * - dismiss：只通知举报者（经核实暂未处理）
 */
class ReportController {

	public function index() {
		self::redirect('report/list');
	}

	public function createPost($type = '', $targetId = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');

		$reason = (string) param('reason', '', FALSE);
		$detail = trim((string) param('detail', '', FALSE));
		$id = Report::create($type, $targetId, $user['username'], $reason, $detail);
		if ($id === null) self::notFound('举报目标不存在或举报参数无效。');

		// 回来源页（HTTP Referer 站内校验；取不到回首页）
		$back = (string) ($_SERVER['HTTP_REFERER'] ?? '');
		$base = rtrim(APP_BASE, '/');
		if ($back !== '' && strpos($back, $base . '/') === 0) {
			header('Location: ' . $back . (strpos($back, '?') === false ? '?' : '&') . 'reported=1');
			exit;
		}
		self::redirect('');
	}

	public function list() {
		if (!Auth::isLoggedIn()) self::redirect('auth/login');
		if (Auth::level() < 2) self::forbidden();

		$tab = (string) Router::query('tab', 'open');
		if (!in_array($tab, array('open', 'done'), true)) $tab = 'open';

		$lists = Report::listAll();
		$rows = array();
		foreach ($lists[$tab] as $r) {
			$rows[] = self::decorate($r);
		}
		self::render('report_list', array('rows' => $rows, 'tab' => $tab));
	}

	public function handlePost($id = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		if (!Auth::isLoggedIn()) self::redirect('auth/login');
		if (Auth::level() < 2) self::forbidden();

		$action = (string) param('action', '', FALSE); // delete|dismiss|ban（user 专用）
		$r = Report::get($id);
		if ($r === null) self::notFound('举报不存在。');
		if (!in_array($action, array('delete', 'dismiss', 'ban'), true)) self::forbidden();
		if ($action === 'ban' && (string) $r['type'] !== 'user') self::forbidden();
		if ((string) $r['status'] !== 'open') self::redirect('report/list');

		$mod = Auth::user()['username'];
		// 删除前先抓作者快照（post 删除后取不到）
		$targetAuthor = '';
		if ($action === 'delete') {
			if ((string) $r['type'] === 'post') {
				$targetAuthor = (string) (Post::get((string) $r['target_id'])['author'] ?? '');
			} else {
				$targetAuthor = (string) (Topic::get((string) $r['target_id'])['author'] ?? '');
			}
		}
		// ban 分支：调 User::setBan 单入口（与 user/ban 路由同一写路径）；权限规则一致
		$banApplied = false;
		if ($action === 'ban') {
			$targetUser = User::get((string) $r['target_id']);
			$me = Auth::user();
			if ($targetUser === null) self::notFound('被举报用户不存在。');
			if ((string) $targetUser['username'] === (string) $me['username']) self::forbidden();
			if ((int) $targetUser['level'] >= (int) $me['level']) self::forbidden();
			list($until, $untilText) = UserController::resolveDuration(
				(string) param('duration', '', FALSE),
				(string) param('until', '', FALSE)
			);
			if ($until === false) {
				http_response_code(422);
				header('Content-Type: text/plain; charset=utf-8');
				exit('422: 封禁时长参数无效。');
			}
			if (!User::setBan((string) $r['target_id'], $until, $mod, (string) ($r['reason'] ?? ''))) self::forbidden();
			$banApplied = true;
			// 私信通知被封用户（版主署名）
			$meName = (string) ($me['nickname'] ?? $me['username']);
			PM::send($mod, (string) $r['target_id'], '你的账号已被封禁',
				"你的账号已被版主 {$meName} 封禁至 {$untilText}（经用户举报核实）。如有疑问请回复本私信联系版主。");
		}
		$deleted = false;
		if ($action === 'delete') {
			$deleted = self::deleteTarget($r);
		}
		if (!Report::handle($id, $mod, $action)) {
			http_response_code(422);
			header('Content-Type: text/plain; charset=utf-8');
			exit('处理失败：举报状态已变化，请刷新。');
		}

		self::notify($r, $mod, $action, $targetAuthor);
		self::redirect('report/list');
	}

	/**
	 * 删除举报目标（topic/post 复用既有删除路径，计数照批次 2 单入口）
	 */
	private static function deleteTarget(array $r) {
		$tid = (string) $r['target_id'];
		if ((string) $r['type'] === 'post') {
			$post = Post::get($tid);
			if ($post === null) return false;
			$topicId = (string) ($post['topic_id'] ?? '');
			Post::delete($tid);
			// 主题 post_ids 移除 + 计数（与 post 删除路径同款）
			$topic = $topicId !== '' ? Topic::get($topicId) : null;
			if ($topic !== null) {
				$topic['post_ids'] = array_values(array_diff((array) ($topic['post_ids'] ?? array()), array($tid)));
				Topic::save($topicId, $topic);
				Forum::adjustCounts((string) ($topic['forum_id'] ?? ''), 0, -1);
			}
			return true;
		}
		// type=topic：走主题删除全路径（连带 posts + 计数 + 置顶引用）
		$topic = Topic::get($tid);
		if ($topic === null) return false;
		$forumId = (string) ($topic['forum_id'] ?? '');
		$posts = Post::listByTopic($tid);
		foreach ($posts as $p) {
			Post::delete((string) $p['id']);
		}
		if ($forumId !== '') {
			Forum::adjustCounts($forumId, -1, -count($posts));
			if (!empty($topic['sticky'])) Forum::setStickyRef($forumId, $tid, false);
		}
		// 若带投票，连带删 poll 文件
		if (!empty($topic['poll_id'])) flatDB::removeDB('polls', $tid);
		Topic::delete($tid);
		return true;
	}

	/**
	 * 私信通知（操作版主当发件人；口径不同）
	 */
	private static function notify(array $r, $mod, $action, $targetAuthor) {
		$typeText = (string) $r['type'] === 'post' ? '回帖' : ((string) $r['type'] === 'user' ? '用户' : '主题');
		$modU = User::get($mod);
		$modName = $modU !== null ? (string) ($modU['nickname'] ?? $mod) : $mod;

		// 给举报者（ban/delete/dismiss 都通知）
		$repU = User::get((string) $r['reporter']);
		if ($repU !== null) {
			if ($action === 'delete') {
				$body = "你举报的内容（{$typeText}）经核实已被删除。感谢你对社区秩序的贡献。";
			} elseif ($action === 'ban') {
				$body = "你举报的用户经核实已被封禁。感谢你对社区秩序的贡献。";
			} else {
				$body = "你举报的内容（{$typeText}）经核实暂未做处理，感谢反馈，我们会持续关注。";
			}
			PM::send($mod, (string) $r['reporter'], '你的举报已处理完毕', $body);
		}

		// 给被举报者（仅删除时通知；dismiss/ban 不再单独打扰——ban 已在 handlePost 发封禁通知）
		if ($action === 'delete' && $targetAuthor !== '' && $targetAuthor !== $mod && User::get($targetAuthor) !== null) {
			PM::send($mod, $targetAuthor, "你的{$typeText}已被移除", "你的{$typeText}因违反社区规范经用户举报后被版主移除。如有疑问请回复本私信联系版主 {$modName}。");
		}
	}

	/**
	 * 队列行装饰：目标摘要 + 链接 + 处理人昵称
	 */
	private static function decorate(array $r) {
		$type = (string) ($r['type'] ?? '');
		$tid = (string) ($r['target_id'] ?? '');
		$row = array('report' => $r, 'summary' => '', 'link' => '', 'resolved_by_name' => '');
		if ($type === 'post') {
			$p = Post::get($tid);
			if ($p !== null) {
				$t = Topic::get((string) ($p['topic_id'] ?? ''));
				$row['summary'] = mb_substr((string) ($p['content_md'] ?? ''), 0, 60);
				$row['link'] = $t !== null ? Router::url('topic/view/' . rawurlencode((string) $t['id'])) : '';
				$row['summary'] = '「' . (string) ($t['title'] ?? '?') . '」楼层：' . $row['summary'];
			} else {
				$row['summary'] = '（目标已删除）';
			}
		} elseif ($type === 'topic') {
			$t = Topic::get($tid);
			if ($t !== null) {
				$row['summary'] = (string) ($t['title'] ?? '');
				$row['link'] = Router::url('topic/view/' . rawurlencode($tid));
			} else {
				$row['summary'] = '（目标已删除）';
			}
		} elseif ($type === 'user') {
			$u = User::get($tid);
			if ($u !== null) {
				$row['summary'] = '用户 ' . (string) ($u['nickname'] ?? $tid)
					. (User::isBanned($u) ? '（当前已被封禁）' : '');
				$row['link'] = Router::url('user/view/' . rawurlencode($tid));
			} else {
				$row['summary'] = '（用户已不存在）';
			}
		}
		$rb = (string) ($r['resolved_by'] ?? '');
		if ($rb !== '') {
			$u = User::get($rb);
			$row['resolved_by_name'] = $u !== null ? (string) ($u['nickname'] ?? $rb) : $rb;
		}
		return $row;
	}

	/* ---------------- 视图与响应辅助 ---------------- */

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

	private static function notFound($msg = '页面不存在。') {
		http_response_code(404);
		header('Content-Type: text/html; charset=utf-8');
		echo '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><title>404</title>';
		echo '<body class="container py-5"><h1>404</h1><p>' . htmlspecialchars($msg) . '</p>';
		echo '<p><a href="' . Router::url() . '">返回首页</a></p></body></html>';
		exit;
	}
}
