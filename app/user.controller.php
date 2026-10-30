<?php namespace Controller;

defined('FLOO') or die('potato'); // NOOOO

/**
 * 用户控制器（SPEC §3.1 路由表）
 * - view：GET /user/view/{name} 个人资料页（查看他人，MVP §5）
 * - settings：GET/POST /user/settings 个人设置（昵称/签名/头像/邮箱/改密，后续批次）
 * 三位置契约：/user/view/Geticer → view('Geticer')，用户名走 action 之后
 */
class UserController {

	public function index() {
		self::notImplemented('user/index：用户列表，Phase 1 实现');
	}

	public function view($name = '') {
		global $conf, $header;

		$user = \Model\User::get($name);
		if ($user === null) {
			http_response_code(404);
			$header['title'] = '用户不存在';
			require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
			echo '<div class="alert alert-danger" role="alert">用户不存在。</div>';
			echo '<a class="btn btn-secondary" href="' . Router::url() . '">返回首页</a>';
			require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
			exit;
		}

		$header['title'] = (string) ($user['nickname'] ?? $user['username']);
		$stats = array(
			'topics' => \Model\Topic::countByAuthor($user['username']),
			'posts' => \Model\Post::countByAuthor($user['username']),
		);

		require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'user_view.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
		exit;
	}

	public function settings() {
		self::notImplemented('user/settings GET：设置表单，Phase 1 实现');
	}

	public function settingsPost() {
		self::notImplemented('user/settings POST：保存设置（CSRF；头像走 files.lib.php），Phase 1 实现');
	}

	private static function notImplemented($msg) {
		http_response_code(501);
		echo '<!doctype html><meta charset="utf-8"><title>501</title>';
		echo '<p>Phase 0 骨架占位：' . htmlspecialchars($msg) . '</p>';
		echo '<p><a href="' . \Router::url() . '">返回首页</a></p>';
	}
}
