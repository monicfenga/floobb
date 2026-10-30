<?php namespace Controller;

defined('FLOO') or die('potato'); // NOOOO

/**
 * 认证控制器骨架（Phase 1 实现业务：login/register/logout/reset，SPEC §3.1 路由表）
 * 注意：logout 一律 POST + CSRF（原 logout.php 为 GET，重建改 POST）
 */
class AuthController {

	public function index() {
		self::notImplemented('auth/index：Phase 1 实现');
	}

	public function login() {
		self::notImplemented('auth/login GET：登录表单，Phase 1 实现');
	}

	public function loginPost() {
		self::notImplemented('auth/login POST：登录提交（Auth::login + CSRF + 尝试限制），Phase 1 实现');
	}

	public function register() {
		self::notImplemented('auth/register GET：注册表单（Captcha + CSRF），Phase 1 实现');
	}

	public function registerPost() {
		self::notImplemented('auth/register POST：注册提交（自动登录，不邮件激活），Phase 1 实现');
	}

	public function logoutPost() {
		self::notImplemented('auth/logout POST：登出（CSRF），Phase 1 实现');
	}

	public function reset() {
		self::notImplemented('auth/reset GET：密码重置表单（recovery_codes），Phase 1 实现');
	}

	public function resetPost() {
		self::notImplemented('auth/reset POST：提交重置，Phase 1 实现');
	}

	private static function notImplemented($msg) {
		http_response_code(501);
		echo '<!doctype html><meta charset="utf-8"><title>501</title>';
		echo '<p>Phase 0 骨架占位：' . htmlspecialchars($msg) . '</p>';
		echo '<p><a href="<?= Router::url() ?>">返回首页</a></p>';
	}
}
