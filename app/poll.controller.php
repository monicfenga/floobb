<?php defined('FLOO') or die('FlooBB');

/**
 * 投票控制器（SPEC §3.1 /poll/vote/{tid}，扩展 /poll/close/{tid}）
 *
 * - votePost  POST /poll/vote/{tid}  投票（登录 + CSRF；重复投/超限/过期拒绝）
 * - closePost POST /poll/close/{tid} 关闭投票（主题作者或 level≥2 + CSRF）
 *
 * 投票与回帖独立：锁定主题照常可投；截止时间过期视为自动关闭。
 */
class PollController {

	public function index() {
		self::redirect('');
	}

	public function votePost($tid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');

		$topic = Topic::get($tid);
		if ($topic === null) self::notFound('主题不存在。');
		$poll = Poll::get($tid);
		if ($poll === null) self::notFound('该主题没有投票。');

		// 裸取 $_POST['choices']，白名单过滤：只收 0..N-1 合法下标，其余（含非法输入）一律丢弃
		$nOptions = count((array) ($poll['options'] ?? array()));
		$choices = array();
		foreach ((array) ($_POST['choices'] ?? array()) as $c) {
			$c = (int) $c;
			if ($c >= 0 && $c < $nOptions) $choices[] = $c;
		}
		$err = Poll::vote($tid, $user['username'], $choices);
		if ($err !== null) {
			http_response_code(422);
			header('Content-Type: text/plain; charset=utf-8');
			exit(self::errText($err));
		}
		self::redirect('topic/view/' . rawurlencode((string) $tid));
	}

	public function closePost($tid = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');
		$topic = Topic::get($tid);
		if ($topic === null) self::notFound('主题不存在。');
		// 关闭权限：主题作者 或 level≥2（用户决议）
		$isAuthor = (string) ($topic['author'] ?? '') === (string) $user['username'];
		if (!$isAuthor && (int) $user['level'] < 2) self::forbidden();

		Poll::close($tid, $user['username']);
		self::redirect('topic/view/' . rawurlencode((string) $tid));
	}

	private static function errText($key) {
		$map = array(
			'poll.notfound' => '投票不存在。',
			'poll.closed' => '投票已关闭。',
			'poll.expired' => '投票已过截止时间。',
			'poll.already' => '你已经投过票了。',
			'poll.option.empty' => '请至少选择一个选项。',
			'poll.option.invalid' => '选项无效。',
			'poll.toomany' => '超出最多可选数。',
			'poll.save.failed' => '保存失败，请重试。',
		);
		return $map[$key] ?? '投票失败。';
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
