<?php

defined('FLOO') or die('FlooBB');

/**
 * 私信控制器（SPEC §3.1 /pm/inbox，扩展 outbox/compose/send/view/delete）
 *
 * - inbox    GET  /pm/inbox          收件箱（未读加粗，?page= 翻页）
 * - outbox   GET  /pm/outbox         已发送
 * - compose  GET  /pm/compose?to=x   写信表单（用户页入口带 to 预填）
 * - sendPost POST /pm/send           发送（CSRF；禁自发自收；收件人须存在）
 * - view     GET  /pm/view/{id}      详情（收件侧查看即标已读）
 * - deletePost POST /pm/delete/{id}  删除（side=inbox|outbox，各删各副本）
 *
 * 硬规则：未登录 302；写操作 Auth::csrfCheck() 失败 403；输入一律 param()。
 */
class PMController {

	public function index() {
		self::redirect('pm/inbox');
	}

	public function inbox() {
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');
		self::renderList('inbox', $user);
	}

	public function outbox() {
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');
		self::renderList('outbox', $user);
	}

	private static function renderList($side, $user) {
		global $header;
		$box = PM::getBox($user['username']);
		$items = $box[$side];
		// 新的在前
	usort($items, function ($a, $b) {
			return strcmp((string) ($b['time'] ?? ''), (string) ($a['time'] ?? ''));
		});

		$per = 10;
		$total = Paginate::countPage($items, $per);
		$page = min(Paginate::pid($total), $total);
		$pageItems = Paginate::viewPage($items, $page, $per);
		$loc = Router::url('pm/' . $side) . '?page=';
		$pageLinks = Paginate::pageLink($page, $total, $loc);

		// 列表对端（inbox 显示发件人，outbox 显示收件人）昵称
		$peerNames = array();
		foreach ($pageItems as $m) {
			$peer = (string) ($side === 'inbox' ? ($m['from'] ?? '') : ($m['to'] ?? ''));
			if (!isset($peerNames[$peer])) {
				$pu = User::get($peer);
				$peerNames[$peer] = $pu !== null ? (string) ($pu['nickname'] ?? $pu['username']) : $peer;
			}
		}

		$header['title'] = ($side === 'inbox' ? '收件箱' : '已发送') . ' - 私信';
		self::render('pm_list', array(
			'side' => $side,
			'items' => $pageItems,
			'peerNames' => $peerNames,
			'pageLinks' => $pageLinks,
		));
	}

	public function compose() {
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');
		$to = trim((string) Router::query('to', ''));
		self::render('pm_compose', array(
			'error' => null,
			'values' => array('to' => $to, 'subject' => '', 'body' => ''),
		));
	}

	public function sendPost() {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');

		$to = trim((string) param('to', '', FALSE));
		$subject = trim((string) param('subject', '', FALSE));
		$body = (string) param('body', '', FALSE);
		$values = array('to' => $to, 'subject' => $subject, 'body' => $body);

		$renderError = function ($msg) use ($values) {
			self::render('pm_compose', array('error' => $msg, 'values' => $values));
		};

		if ($to === '' || strcasecmp($to, (string) $user['username']) === 0) $renderError('收件人不能为空，且不能发给自己。');
		$toUser = User::get($to);
		if ($toUser === null) $renderError('收件人不存在。');
		if ($subject === '' || mb_strlen($subject) > 120) $renderError('标题不能为空且不超过 120 字符。');
		if (trim($body) === '') $renderError('正文不能为空。');

		PM::send($user['username'], $toUser['username'], $subject, $body);
		self::redirect('pm/outbox');
	}

	public function view($id = '') {
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');

		// 先收件侧后发件侧
		$side = 'inbox';
		$hit = PM::find($user['username'], 'inbox', $id);
		if ($hit === null) {
			$side = 'outbox';
			$hit = PM::find($user['username'], 'outbox', $id);
		}
		if ($hit === null) self::notFound('私信不存在或已删除。');

		// 收件侧查看即标已读
		if ($side === 'inbox') PM::markRead($user['username'], $id);

		$peer = (string) ($side === 'inbox' ? ($hit['msg']['from'] ?? '') : ($hit['msg']['to'] ?? ''));
		$pu = User::get($peer);
		$header['title'] = (string) ($hit['msg']['subject'] ?? '私信');
		self::render('pm_view', array(
			'side' => $side,
			'msg' => $hit['msg'],
			'peerName' => $pu !== null ? (string) ($pu['nickname'] ?? $pu['username']) : $peer,
		));
	}

	public function deletePost($id = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');

		$side = (string) param('side', 'inbox', FALSE);
		if (!in_array($side, array('inbox', 'outbox'), true)) self::forbidden();
		PM::remove($user['username'], $side, $id);
		self::redirect('pm/' . $side);
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
