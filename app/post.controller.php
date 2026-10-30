<?php

defined('FLOO') or die('potato'); // NOOOO

/**
 * 帖子控制器（SPEC §3.1 路由表 / §5 MVP）
 *
 * - create POST /post/create/{tid}  回帖（校验未锁定；forum post_count+1）
 * - edit   GET/POST /post/edit/{pid}  编辑（所有者或 level≥2；content_md 重写重渲染 + 记 edited）
 * - delete POST /post/delete/{pid}  删除（作者或 level≥2；计数扣减；主题空则连带删主题）
 *
 * 硬规则：写操作 Auth::csrfCheck() 失败 403；输入一律 param()。
 */
class PostController {

	public function index() {
		self::redirect('');
	}

	/* ---------------- 回帖 ---------------- */

	public function create($tid = '') {
		// GET /post/create/{tid} → 回到主题页（回帖表单在主题页内）
		self::redirect('topic/view/' . rawurlencode((string) $tid));
	}

	public function createPost($tid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');

		$topic = Topic::get($tid);
		if ($topic === null) self::notFound('主题不存在。');
		if (Topic::isLocked($topic)) self::forbidden('主题已锁定，无法回帖。');

		$content = (string) param('content', '', FALSE);
		if (trim($content) === '') self::redirect('topic/view/' . rawurlencode((string) $tid));

		$forumId = (string) ($topic['forum_id'] ?? '');
		$pid = Post::create(array(
			'topic_id' => (string) $tid,
			'forum_id' => $forumId,
			'author' => $user['username'],
			'created' => date('Y-m-d\TH:i:s'),
			'edited' => null,
			'content_md' => $content,
		));
		// topic.post_ids 维护 + last_reply_at + 版块 post_count+1
		$topic['post_ids'][] = $pid;
		$topic['last_reply_at'] = date('Y-m-d\TH:i:s');
		Topic::save((string) $tid, $topic);
		if ($forumId !== '') Forum::adjustCounts($forumId, 0, 1);

		// 跳到最后一页（时间正序分页，每页 10）
		$n = count(Post::listByTopic((string) $tid));
		$lastPage = max(1, (int) ceil($n / 10));
		self::redirect('topic/view/' . rawurlencode((string) $tid) . '?page=' . $lastPage);
	}

	/* ---------------- 编辑 ---------------- */

	public function edit($pid = '') {
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');
		$post = Post::get($pid);
		if ($post === null) self::notFound('帖子不存在。');
		if ($user['username'] !== ($post['author'] ?? '') && (int) $user['level'] < 2) self::forbidden();

		$topic = Topic::get($post['topic_id'] ?? '');
		self::render('post_edit', array(
			'error' => null,
			'post' => $post,
			'topic' => $topic,
		));
	}

	public function editPost($pid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');
		$post = Post::get($pid);
		if ($post === null) self::notFound('帖子不存在。');
		if ($user['username'] !== ($post['author'] ?? '') && (int) $user['level'] < 2) self::forbidden();

		$content = (string) param('content', '', FALSE);
		if (trim($content) === '') {
			self::render('post_edit', array('error' => '正文不能为空。', 'post' => $post, 'topic' => Topic::get($post['topic_id'] ?? '')));
		}

		$post['content_md'] = $content;
		$post['edited'] = date('Y-m-d\TH:i:s');
		Post::save((string) $pid, $post); // save() 内部重渲染 content_html

		self::redirect('topic/view/' . rawurlencode((string) ($post['topic_id'] ?? '')) . '#p' . rawurlencode((string) $pid));
	}

	/* ---------------- 删除 ---------------- */

	public function deletePost($pid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');
		$post = Post::get($pid);
		if ($post === null) self::notFound('帖子不存在。');
		if ($user['username'] !== ($post['author'] ?? '') && (int) $user['level'] < 2) self::forbidden();

		$topicId = (string) ($post['topic_id'] ?? '');
		$forumId = (string) ($post['forum_id'] ?? '');
		Post::delete($pid);

		// topic.post_ids 维护
		$topic = Topic::get($topicId);
		$remaining = 0;
		if ($topic !== null) {
			$ids = array_values(array_filter((array) ($topic['post_ids'] ?? array()), function ($id) use ($pid) {
				return (string) $id !== (string) $pid;
			}));
			$topic['post_ids'] = $ids;
			$remaining = count($ids);
			Topic::save($topicId, $topic);
		}
		if ($forumId !== '') Forum::adjustCounts($forumId, 0, -1);

		// 主题下已无帖子 → 连带删主题
		if ($topic !== null && $remaining === 0) {
			if ($forumId !== '') {
				Forum::adjustCounts($forumId, -1, 0);
				if (!empty($topic['sticky'])) Forum::setStickyRef($forumId, $topicId, false);
			}
			Topic::delete($topicId);
			self::redirect('forum/view/' . rawurlencode($forumId));
		}
		self::redirect('topic/view/' . rawurlencode($topicId));
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

	private static function forbidden($msg = '403 Forbidden: 权限不足或 CSRF token 无效。') {
		http_response_code(403);
		header('Content-Type: text/plain; charset=utf-8');
		exit($msg);
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
