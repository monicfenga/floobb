<?php namespace Controller;

defined('FLOO') or die('potato'); // NOOOO

/**
 * 用户控制器骨架（Phase 1 实现业务：view/settings，SPEC §3.1 路由表）
 * 注意三位置契约：/user/view/Geticer → view('Geticer')，用户名走 action 之后
 */
class UserController {

	public function index() {
		self::notImplemented('user/index：用户列表，Phase 1 实现');
	}

	public function view($name = '') {
		self::notImplemented('user/view：用户资料页，Phase 1 实现');
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
		echo '<p><a href="<?= Router::url() ?>">返回首页</a></p>';
	}
}
