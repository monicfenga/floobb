<?php

defined('FLOO') or die('potato'); // NOOOO

/**
 * 主题控制器（SPEC §3.1 路由表）
 *
 * - view   GET  /topic/view/{tid}   帖子流 + 分页 + ?sort=latest/oldest + ?filter=author（只看楼主）
 * - create GET/POST /topic/create[/{fid}]  新主题（首帖一并建，写 topics+posts+forum 计数）
 * - lock   POST /topic/lock/{tid}   锁定/解锁切换（moderate：level≥2）
 * - sticky POST /topic/sticky/{tid} 置顶/取消切换（moderate：level≥2，同步 forums.sticky_topic_ids）
 * - move   POST /topic/move/{tid}   移动版块（moderate：level≥2，新旧版块计数互换）
 * - delete POST /topic/delete/{tid} 删主题（作者或 level≥2，连带删 posts，计数扣减）
 *
 * 硬规则：写操作 Auth::csrfCheck() 失败 403；输入一律 param()。
 */
class TopicController {

	public function index() {
		self::redirect('');
	}

	/* ---------------- 主题详情 ---------------- */

	public function view($tid = '') {
		global $conf, $header;

		$topic = Topic::get($tid);
		if ($topic === null) self::notFound('主题不存在。');
		Topic::incrViews($tid); // 浏览计数（每次 GET +1）
		$topic = Topic::get($tid); // 取回最新计数
		$forum = Forum::get($topic['forum_id'] ?? '');

		// query string 修饰符：sort=latest/oldest（默认 oldest=时间正序）；filter=author 只看楼主
		$sort = (string) Router::query('sort', 'oldest');
		if (!in_array($sort, array('latest', 'oldest'), true)) $sort = 'oldest';
		$filter = (string) Router::query('filter', '');

		$posts = Post::listByTopic($tid);
		if ($filter === 'author') {
			$author = (string) ($topic['author'] ?? '');
			$posts = array_values(array_filter($posts, function ($p) use ($author) {
				return (string) ($p['author'] ?? '') === $author;
			}));
		}
		// listByTopic 默认 created 正序（oldest）；latest 反转
		if ($sort === 'latest') $posts = array_reverse($posts);

		$user = Auth::user();
		$per = 10;
		$total = Paginate::countPage($posts, $per);
		$page = min(Paginate::pid($total), $total);
		$pagePosts = Paginate::viewPage($posts, $page, $per);

		// 组装视图行：作者昵称 + 权限标记
		$postsView = array();
		foreach ($pagePosts as $p) {
			$author = User::get((string) ($p['author'] ?? ''));
			$isOwner = $user !== null && $user['username'] === ($p['author'] ?? '');
			$postsView[] = array(
				'post' => $p,
				'author_nickname' => $author['nickname'] ?? ($p['author'] ?? ''),
				'author_avatar' => (string) ($author['avatar'] ?? ''),
				'author_bio' => (string) ($author['bio'] ?? ''),
				'can_edit' => $isOwner || ($user !== null && (int) $user['level'] >= 2),
				'can_delete' => $isOwner || ($user !== null && (int) $user['level'] >= 2),
			);
		}

		// $loc 必须是 Router::url() 绝对路径（memory ④）
		$qs = array();
		if ($sort !== 'oldest') $qs['sort'] = $sort;
		if ($filter === 'author') $qs['filter'] = $filter;
		$base = Router::url('topic/view/' . rawurlencode((string) $topic['id']));
		$loc = $base . (empty($qs) ? '?' : '?' . http_build_query($qs) . '&') . 'page=';
		$pageLinks = Paginate::pageLink($page, $total, $loc);

		// 移动表单的版块下拉（主题页管理区）
		$allForums = array();
		foreach (Forum::listAll() as $f) {
			$allForums[(string) ($f['id'] ?? '')] = (string) ($f['name'] ?? '');
		}

		$header['title'] = $topic['title'] . ' - ' . ($conf['title'] ?? 'FlooBB');
		self::render('topic_view', array(
			'topic' => $topic,
			'forum' => $forum,
			'posts' => $postsView,
			'sort' => $sort,
			'filter' => $filter,
			'pageLinks' => $pageLinks,
			'allForums' => $allForums,
			'cur_page' => $page,
			'total_pages' => $total,
		));
	}

	/* ---------------- 新建主题（首帖一并建） ---------------- */

	public function create($fid = '') {
		if (!Auth::isLoggedIn()) self::redirect('auth/login');
		self::render('topic_create', array(
			'error' => null,
			'values' => array('fid' => $fid, 'title' => '', 'content' => ''),
			'forums' => Forum::listAll(),
		));
	}

	public function createPost($fid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');

		$fid = (string) param('fid', $fid, FALSE);
		$title = trim(strip_tags((string) param('title', '', FALSE)));
		$content = (string) param('content', '', FALSE);
		$values = array('fid' => $fid, 'title' => $title, 'content' => $content);

		$renderError = function ($msg) use ($values) {
			self::render('topic_create', array(
				'error' => $msg,
				'values' => $values,
				'forums' => Forum::listAll(),
			));
		};

		if (!Forum::exists($fid)) $renderError('请选择有效的目标版块。');
		if ($title === '' || mb_strlen($title) > 120) $renderError('标题不能为空且不超过 120 字符。');
		if (trim($content) === '') $renderError('正文不能为空。');

		$now = date('Y-m-d\TH:i:s');
		$tid = Topic::create(array(
			'forum_id' => $fid,
			'title' => $title,
			'author' => $user['username'],
			'created' => $now,
			'last_reply_at' => $now,
			'views' => 0,
			'sticky' => false,
			'locked' => false,
			'poll_id' => null,
			'post_ids' => array(),
		));
		$pid = Post::create(array(
			'topic_id' => $tid,
			'forum_id' => $fid,
			'author' => $user['username'],
			'created' => $now,
			'edited' => null,
			'content_md' => $content,
		));
		// 首帖计入 post_ids + 版块计数（建主题 = topic_count+1 & post_count+1）
		$topic = Topic::get($tid);
		$topic['post_ids'][] = $pid;
		Topic::save($tid, $topic);
		Forum::adjustCounts($fid, 1, 1);

		self::redirect('topic/view/' . rawurlencode($tid));
	}

	/* ---------------- moderate 动作（level≥2） ---------------- */

	public function lockPost($tid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		if (Auth::level() < 2) self::forbidden();
		$topic = Topic::get($tid);
		if ($topic === null) self::notFound('主题不存在。');

		$topic['locked'] = empty($topic['locked']); // 切换
		Topic::save($tid, $topic);
		self::redirect('topic/view/' . rawurlencode((string) $tid));
	}

	public function stickyPost($tid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		if (Auth::level() < 2) self::forbidden();
		$topic = Topic::get($tid);
		if ($topic === null) self::notFound('主题不存在。');

		$on = empty($topic['sticky']);
		$topic['sticky'] = $on;
		Topic::save($tid, $topic);
		// 同步 forums.sticky_topic_ids（SPEC §4）
		if (!empty($topic['forum_id'])) Forum::setStickyRef($topic['forum_id'], $tid, $on);
		self::redirect('topic/view/' . rawurlencode((string) $tid));
	}

	public function movePost($tid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		if (Auth::level() < 2) self::forbidden();
		$topic = Topic::get($tid);
		if ($topic === null) self::notFound('主题不存在。');

		$newFid = (string) param('fid', '', FALSE);
		$oldFid = (string) ($topic['forum_id'] ?? '');
		if (!Forum::exists($newFid)) self::forbidden();
		if ($newFid !== $oldFid) {
			$posts = Post::listByTopic($tid);
			$n = count($posts);
			// 新旧版块计数互换（topic +1/-1，post 连带）
			Forum::adjustCounts($oldFid, -1, -$n);
			Forum::adjustCounts($newFid, 1, $n);
			$topic['forum_id'] = $newFid;
			Topic::save($tid, $topic);
			// 帖子冗余字段 forum_id 同步
			foreach ($posts as $p) {
				$p['forum_id'] = $newFid;
				Post::save((string) $p['id'], $p);
			}
			// 置顶引用随主题搬家
			if (!empty($topic['sticky'])) {
				Forum::setStickyRef($oldFid, $tid, false);
				Forum::setStickyRef($newFid, $tid, true);
			}
		}
		self::redirect('topic/view/' . rawurlencode((string) $tid));
	}

	/* ---------------- 删除（作者或 level≥2，连带删 posts） ---------------- */

	public function deletePost($tid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');
		$topic = Topic::get($tid);
		if ($topic === null) self::notFound('主题不存在。');
		if ($user['username'] !== ($topic['author'] ?? '') && (int) $user['level'] < 2) self::forbidden();

		$forumId = (string) ($topic['forum_id'] ?? '');
		$posts = Post::listByTopic($tid);
		foreach ($posts as $p) {
			Post::delete((string) $p['id']);
		}
		if ($forumId !== '') {
			Forum::adjustCounts($forumId, -1, -count($posts));
			if (!empty($topic['sticky'])) Forum::setStickyRef($forumId, $tid, false);
		}
		Topic::delete($tid);
		self::redirect('forum/view/' . rawurlencode($forumId));
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
