<?php defined('FLOO') or die('potato'); // NOOOO

/**
 * 认证与登录态【核心，Phase 0 新增】
 *
 * - 登录态：session（$_SESSION['auth_user']）
 * - RememberMe：selector:validator 双 Cookie 令牌，validator 哈希落库，14 天
 * - 暴力破解：MAX_LOGIN_ATTEMPT=3 次失败后封 IP（BLOCK_IP / BLOCK_TIMEOUT 分钟，
 *   常量已在 init.php 定义，此处直接引用，不重定义）
 * - CSRF：写操作一律先过 Auth::csrfCheck()（CSRF::check）
 * - 密码：password_hash / password_verify（SPEC §6）
 */
class Auth {

	const REMEMBER_COOKIE = 'floobb_remember';
	const REMEMBER_DAYS = 14;

	protected function __construct() {
		// 静态类
	}

	/* ---------------- CSRF ---------------- */

	/**
	 * 写操作校验：POST token 必须 CSRF::check 通过
	 *
	 * @return bool token 合法返回 true，否则 false
	 */
	public static function csrfCheck() {
		return CSRF::check((string) param('token', ''));
	}

	/**
	 * 表单内嵌 token 隐藏域（模板用）
	 *
	 * @return string 带转义 value 的 <input type="hidden"> HTML 片段
	 */
	public static function csrfField() {
		global $token;
		return '<input type="hidden" name="token" value="' . htmlspecialchars($token ?? '') . '">';
	}

	/* ---------------- 登录态 ---------------- */

	/**
	 * 当前登录用户（关联数组）或 null
	 *
	 * @return array|null 用户数据；用户已不存在时清除 session 并返回 null
	 */
	public static function user() {
		if (empty($_SESSION['auth_user'])) return null;
		$user = Model\User::get($_SESSION['auth_user']);
		if ($user === null) {
			self::clearSession();
			return null;
		}
		return $user;
	}

	/**
	 * 是否已登录
	 *
	 * @return bool 已登录返回 true
	 */
	public static function isLoggedIn() {
		return self::user() !== null;
	}

	/**
	 * 当前用户等级：0=游客，1+=注册用户，2=版主，3=管理员
	 *
	 * @return int 用户等级，未登录返回 0
	 */
	public static function level() {
		$user = self::user();
		return $user === null ? 0 : (int) ($user['level'] ?? 1);
	}

	/**
	 * 清除 session 中的登录态
	 *
	 * @return void
	 */
	private static function clearSession() {
		unset($_SESSION['auth_user']);
	}

	/* ---------------- 登录 / 登出 ---------------- */

	/**
	 * 登录：验密码 + 尝试限制 + IP 封锁。成功写 session（调用方决定是否 RememberMe）。
	 * @return bool 失败时 $error 写入错误键
	 */
	public static function login($username, $password, &$error = null) {
		$error = null;
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';

		if (self::isBanned($ip)) {
			$error = 'auth.banned';
			return false;
		}

		$user = Model\User::get($username);
		if ($user === null || !password_verify((string) $password, (string) ($user['password_hash'] ?? ''))) {
			self::recordFail($ip);
			$error = 'auth.failed';
			return false;
		}

		// 成功：清计数
		unset($_SESSION['login_attempts']);

		self::clearSession();
		session_regenerate_id(true);
		$_SESSION['auth_user'] = $user['username'];
		return true;
	}

	/**
	 * 登出：清除 RememberMe Cookie 与 session 登录态
	 *
	 * @return void
	 */
	public static function logout() {
		self::forgetRemember();
		self::clearSession();
	}

	/* ---------------- RememberMe ---------------- */

	/**
	 * 签发 RememberMe Cookie（登录成功后调用）
	 */
	public static function remember($username) {
		$user = Model\User::get($username);
		if ($user === null) return false;

		$selector = bin2hex(random_bytes(9));
		$validator = bin2hex(random_bytes(32));
		$user['remember_selector'] = $selector;
		$user['remember_hash'] = hash('sha256', $validator);
		Model\User::save($username, $user);

		$expire = time() + self::REMEMBER_DAYS * 86400;
		setcookie(self::REMEMBER_COOKIE, $selector . ':' . $validator, [
			'expires' => $expire,
			'path' => '/',
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		return true;
	}

	/**
	 * 凭 RememberMe Cookie 恢复登录态（init.php 每次请求调用）
	 */
	public static function resume() {
		if (self::isLoggedIn() || empty($_COOKIE[self::REMEMBER_COOKIE])) return;

		$cookie = (string) $_COOKIE[self::REMEMBER_COOKIE];
		$parts = explode(':', $cookie, 2);
		if (count($parts) !== 2) return;
		list($selector, $validator) = $parts;

		// 按 selector 定位用户：全扫 users 目录（flat-file 量级小，可接受）
		foreach (flatDB::listDB('users') as $file) {
			$username = basename($file, '.dat.json');
			$user = Model\User::get($username);
			if ($user === null || ($user['remember_selector'] ?? '') !== $selector) continue;
			if (hash_equals((string) ($user['remember_hash'] ?? ''), hash('sha256', $validator))) {
				$_SESSION['auth_user'] = $username;
			}
			return;
		}
	}

	/**
	 * 作废 RememberMe：清空用户令牌字段并删除 Cookie（登出时调用）
	 *
	 * @return void
	 */
	public static function forgetRemember() {
		$user = self::user();
		if ($user !== null && !empty($user['remember_selector'])) {
			$user['remember_selector'] = '';
			$user['remember_hash'] = '';
			Model\User::save($user['username'], $user);
		}
		if (!empty($_COOKIE[self::REMEMBER_COOKIE])) {
			setcookie(self::REMEMBER_COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
		}
	}

	/* ---------------- 暴力破解防护 ---------------- */

	/**
	 * 记录一次登录失败；达到 MAX_LOGIN_ATTEMPT 且 BLOCK_IP 开启时封禁该 IP
	 *
	 * @param string $ip 失败来源 IP
	 * @return void
	 */
	private static function recordFail($ip) {
		$_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
		if (MAX_LOGIN_ATTEMPT > 0 && $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPT && BLOCK_IP) {
			self::banIp($ip);
			unset($_SESSION['login_attempts']);
		}
	}

	/**
	 * IP 是否被封（BAN_FILE：每行 "ip|过期时间戳"）
	 */
	public static function isBanned($ip) {
		if (!BLOCK_IP || !is_file(BAN_FILE)) return false;
		$now = time();
		$lines = file(BAN_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: array();
		$kept = array();
		$banned = false;
		foreach ($lines as $line) {
			$parts = explode('|', $line, 2);
			if (count($parts) !== 2) continue;
			list($banIp, $expiry) = $parts;
			if ((int) $expiry < $now) continue; // 过期自动清除
			if ($banIp === $ip) $banned = true;
			$kept[] = $line;
		}
		if (count($kept) !== count($lines)) {
			file_put_contents(BAN_FILE, implode("\n", $kept) . (count($kept) ? "\n" : ''), LOCK_EX);
		}
		return $banned;
	}

	/**
	 * 封禁 IP：追加 "ip|过期时间戳" 一行到 BAN_FILE，时长 BLOCK_TIMEOUT 分钟
	 *
	 * @param string $ip 要封禁的 IP
	 * @return bool 写入成功返回 true；IP 为空或 BLOCK_IP 关闭返回 false
	 */
	public static function banIp($ip) {
		if ($ip === '' || !BLOCK_IP) return false;
		if (!is_dir(BAN_DIR)) @mkdir(BAN_DIR, 0777, true);
		$expiry = time() + BLOCK_TIMEOUT * 60;
		return (bool) file_put_contents(BAN_FILE, $ip . '|' . $expiry . "\n", FILE_APPEND | LOCK_EX);
	}
}
