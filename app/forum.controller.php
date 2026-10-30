<?php

defined('FLOO') or die('FlooBB');

/**
 * 版块控制器（SPEC §3.1 路由表）
 *
 * - index  GET  /forum          → 重定向首页（首页已承担版块列表）
 * - add    GET/POST /forum/add  新建版块（level≥3）
 * - view   GET  /forum/view/{fid}  版块页：置顶区 + 主题列表 + 分页（?page= ?sort=latest/oldest）
 * - order  POST /forum/order/{fid}  版块排序（Phase 1 后续批次）
 *
 * 硬规则：写操作 Auth::csrfCheck() 失败 403；输入一律 param()。
 */
class ForumController {

	public function index() {
		self::redirect('');
	}

	public function add() {
		if (!Auth::isLoggedIn()) self::redirect('auth/login');
		if (Auth::level() < 3) self::forbidden();
		self::render('forum_add', array('error' => null, 'values' => array('name' => '', 'slug' => '', 'description' => '')));
	}

	public function addPost() {
		if (!Auth::csrfCheck()) self::forbidden();
		if (!Auth::isLoggedIn()) self::redirect('auth/login');
		if (Auth::level() < 3) self::forbidden();

		$name = trim(strip_tags((string) param('name', '', FALSE)));
		$description = trim(strip_tags((string) param('description', '', FALSE)));
		// slug 可留空 → 回退 name；填写时只允许字母数字横线下划线
		$slug = trim((string) param('slug', '', FALSE));
		$values = array('name' => $name, 'slug' => $slug, 'description' => $description);

		$renderError = function ($msg) use ($values) {
			self::render('forum_add', array('error' => $msg, 'values' => $values));
		};

		if ($name === '' || mb_strlen($name) > 50) $renderError('版块名不能为空且不超过 50 字符。');
		if (mb_strlen($description) > 200) $renderError('版块描述不能超过 200 字符。');
		if ($slug !== '' && !preg_match('/^[A-Za-z0-9_-]{1,50}$/', $slug)) {
			$renderError('slug 只允许英文字母、数字、横线、下划线，且不超过 50 字符。');
		}
		if ($slug === '') $slug = $name; // 留空回退 name
		if (Forum::exists($slug)) $renderError('slug 已被占用：' . $slug);

		$fid = Forum::create(array(
			'name' => $name,
			'slug' => $slug,
			'description' => $description,
			'order' => 0,
			'topic_count' => 0,
			'post_count' => 0,
			'sticky_topic_ids' => array(),
		));
		if ($fid === null) $renderError('slug 已被占用：' . $slug);
		self::redirect('forum/view/' . rawurlencode($fid));
	}

	public function view($fid = '') {
		global $conf, $header;

		$forum = Forum::get($fid);
		if ($forum === null) self::notFound('版块不存在。');

		// 排序走 query string（SPEC §3.1：非结构性修饰符不进路径）
		// latest=最新发帖 / replied=最新回帖 / posts=最多回帖 / views=最多查看
		$sort = (string) Router::query('sort', 'latest');
		if (!in_array($sort, array('latest', 'replied', 'posts', 'views'), true)) $sort = 'latest';

		// 置顶区独立展示，普通区分页
		$stickyTopics = array();
		$normalTopics = array();
		foreach (Topic::listByForum($fid, $sort) as $t) {
			if (!empty($t['sticky'])) $stickyTopics[] = $t;
			else $normalTopics[] = $t;
		}
		// 移动表单的版块下拉
		$allForums = array();
		foreach (Forum::listAll() as $f) {
			$allForums[(string) ($f['id'] ?? '')] = (string) ($f['name'] ?? '');
		}

		$per = 10;
		$total = Paginate::countPage($normalTopics, $per);
		$page = min(Paginate::pid($total), $total);
		$pageTopics = Paginate::viewPage($normalTopics, $page, $per);
		// $loc 必须是 Router::url() 绝对路径，pageLink 直接追加 page=N（memory ④ 遗留收口）
		$loc = Router::url('forum/view/' . rawurlencode((string) $forum['id'])) . '?sort=' . $sort . '&page=';
		$pageLinks = Paginate::pageLink($page, $total, $loc);

		$header['title'] = $forum['name'] . ' - ' . ($conf['title'] ?? 'FlooBB');
		self::render('forum_view', array(
			'forum' => $forum,
			'stickyTopics' => $stickyTopics,
			'topics' => $pageTopics,
			'sort' => $sort,
			'pageLinks' => $pageLinks,
			'allForums' => $allForums,
			'totalTopics' => count($stickyTopics) + count($normalTopics),
		));
	}

	public function orderPost($fid = '') {
		self::notImplemented('forum/order POST：版块排序');
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

	private static function notImplemented($msg) {
		http_response_code(501);
		echo '<!doctype html><meta charset="utf-8"><title>501</title>';
		echo '<p>占位：' . htmlspecialchars($msg) . '</p>';
		echo '<p><a href="' . Router::url() . '">返回首页</a></p>';
	}
}
