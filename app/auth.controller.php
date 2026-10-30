<?php

defined('FLOO') or die('FlooBB');

/**
 * 认证控制器（SPEC §3.1 路由表 / §5 MVP 认证项 / §9 #10）
 *
 * - login    GET/POST /auth/login    密码登录（Auth::login + 尝试限制 + RememberMe 可选）
 * - register GET/POST /auth/register Captcha + CSRF，注册即自动登录（禁邮件激活），发 recovery_codes
 * - logout   POST     /auth/logout   CSRF（原 logout.php 为 GET，重建改 POST）
 * - reset    GET/POST /auth/reset    recovery_codes 无邮件重置（Phase 3 升级 TOTP 挑战）
 *
 * 硬规则：写操作一律先 Auth::csrfCheck()，失败 403；用户输入一律 param()。
 * 注意：param() 返回原始值（不转义），类型由默认值决定；HTML 转义在输出层做。
 */
class AuthController {

	public function index() {
		self::redirect('');
	}

	/* ---------------- 登录 ---------------- */

	public function login() {
		if (Auth::isLoggedIn()) self::redirect('');
		self::render('auth_login', array('error' => null, 'success' => null));
	}

	public function loginPost() {
		if (!Auth::csrfCheck()) self::forbidden();

		$username = param('username', '');
		$password = param('password', '');

		if (Auth::login($username, $password, $error)) {
			if (param('remember', '') !== '') {
				Auth::remember($username);
			}
			self::redirect('');
		}
		// 封禁用户提示解封时间（B3-4b；banned_until 为 unix int）
		$banMsg = null;
		if ($error === 'auth.user_banned') {
			$u = User::get($username);
			$until = $u !== null ? (int) ($u['banned_until'] ?? 0) : 0;
			$untilText = $until >= User::BAN_FOREVER ? '永久' : date('Y-m-d H:i', $until);
			$reason = $u !== null ? (string) ($u['banned_reason'] ?? '') : '';
			$banMsg = '该账号已被封禁（至 ' . $untilText . '）' . ($reason !== '' ? '，原因：' . $reason : '') . '。';
		}
		self::render('auth_login', array(
			'error' => $error === 'auth.banned' ? '失败次数过多，IP 已被临时封锁，请稍后再试。'
				: ($banMsg !== null ? $banMsg : '用户名或密码错误。'),
			'success' => null,
			'username' => $username,
		));
	}

	/* ---------------- 注册 ---------------- */

	public function register() {
		if (Auth::isLoggedIn()) self::redirect('');
		self::render('auth_register', array('error' => null, 'values' => array()));
	}

	public function registerPost() {
		if (!Auth::csrfCheck()) self::forbidden();

		$username = param('username', '');
		$password = param('password', '');
		$confirm  = param('confirm', '');
		$email    = trim(param('email', ''));
		$captcha  = param('captcha', '');

		$values = array('username' => $username, 'email' => $email);

		// 校验顺序：验证码 → 用户名 → 密码 → 邮箱；验证码尝试一次性，无论对错即销毁
		$captchaOk = \Captcha::checkCaptcha(strtolower($captcha));
		unset($_SESSION['captcha_code']);
		if (!$captchaOk) {
			self::renderRegisterError('验证码错误。', $values);
		}
		if (!User::validName($username)) {
			self::renderRegisterError('用户名不合法：需字母开头，仅字母/数字/下划线，3-24 位。', $values);
		}
		if (User::exists($username)) {
			self::renderRegisterError('用户名已被占用。', $values);
		}
		if (!User::validPassword($password)) {
			self::renderRegisterError('密码强度不足：至少 8 位，须同时包含字母和数字。', $values);
		}
		if ($password !== $confirm) {
			self::renderRegisterError('两次输入的密码不一致。', $values);
		}
		if ($email !== '' && !User::validEmail($email)) {
			self::renderRegisterError('邮箱格式不正确。', $values);
		}

		// 建用户（SPEC §4 users 模型）；recovery_codes 只存哈希，明文仅注册成功页展示一次
		$now = time();
		$plainCodes = array();
		$user = array(
			'id' => bin2hex(random_bytes(6)),
			'username' => $username,
			'nickname' => $username, // 昵称默认=用户名，/user/settings 可改（SPEC §9 #12）
			'password_hash' => password_hash($password, PASSWORD_DEFAULT),
			'email' => $email,
			'level' => 1,
			'registered' => $now,
			'last_seen' => $now,
			'signature' => '',
			'avatar' => '',
			'recovery_codes' => User::generateRecoveryCodes($plainCodes),
			'remember_selector' => '',
			'remember_hash' => '',
			'totp_secret' => '',
			'totp_enabled' => false,
		);
		if (!User::create($user)) {
			self::renderRegisterError('注册失败：用户名已被占用。', $values);
		}

		// 自动登录（不邮件激活，SPEC §9 #6/#10）
		Auth::login($username, $password);
		self::render('auth_recovery', array('codes' => $plainCodes, 'username' => $username));
	}

	private static function renderRegisterError($msg, array $values) {
		self::render('auth_register', array('error' => $msg, 'values' => $values));
	}

	/* ---------------- 登出 ---------------- */

	public function logoutPost() {
		if (!Auth::csrfCheck()) self::forbidden();
		Auth::logout();
		self::redirect('');
	}

	/* ---------------- 密码重置（recovery_codes，无邮件） ---------------- */

	public function reset() {
		if (Auth::isLoggedIn()) self::redirect('');
		self::render('auth_reset', array('error' => null, 'username' => ''));
	}

	public function resetPost() {
		if (!Auth::csrfCheck()) self::forbidden();

		$username = param('username', '');
		$code = param('code', '');
		$password = param('password', '');
		$confirm = param('confirm', '');

		$user = User::get($username);
		if ($user === null) {
			self::renderResetError('用户不存在。', $username);
		}
		// 先验新密码，最后才核销恢复码——避免密码没填对白烧一个一次性码
		if (!User::validPassword($password)) {
			self::renderResetError('新密码强度不足：至少 8 位，须同时包含字母和数字。', $username);
		}
		if ($password !== $confirm) {
			self::renderResetError('两次输入的新密码不一致。', $username);
		}
		if (!User::consumeRecoveryCode($user, $code)) {
			self::renderResetError('恢复码无效。', $username);
		}

		$user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
		// 重置后作废旧 RememberMe 令牌，防旧凭据残留
		$user['remember_selector'] = '';
		$user['remember_hash'] = '';
		User::save($user['username'], $user);

		self::render('auth_login', array(
			'error' => null,
			'success' => '密码已重置，请用新密码登录。',
		));
	}

	private static function renderResetError($msg, $username) {
		self::render('auth_reset', array('error' => $msg, 'username' => $username));
	}

	/* ---------------- 视图与响应辅助 ---------------- */

	private static function render($tpl, array $vars = array()) {
		global $conf, $header, $token;
		extract($vars);

		// CSRF token 一次性：被消费（或即将渲染表单）时补发
		if (empty($_SESSION['token'])) {
			$token = CSRF::generate();
		}

		$header['title'] = ($header['title'] ?? $conf['title'] ?? 'FlooBB');
		require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . $tpl . '.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
		exit; // 渲染即终止：错误路径（验证码失败等）不得继续执行后续业务
	}

	/**
	 * 写操作 CSRF 失败 → 403（验收要求：token 缺失时写操作返回 403）
	 */
	private static function forbidden() {
		http_response_code(403);
		header('Content-Type: text/plain; charset=utf-8');
		exit('403 Forbidden: CSRF token 无效或缺失。');
	}

	private static function redirect($path) {
		header('Location: ' . Router::url($path));
		exit;
	}
}
