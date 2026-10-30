<?php

defined('FLOO') or die('FlooBB');

/**
 * 用户仓储【model 层 = 关联数组 + 静态仓储函数，SPEC §3.3】
 * 存储：data/users/<username>.dat.json（SPEC §4）
 */
class User {

	/**
	 * 按用户名取用户（关联数组），不存在返回 null
	 */
	public static function get($username) {
		$username = (string) $username;
		if ($username === '' || !flatDB::DBexists('users', $username)) return null;
		return flatDB::getDB('users', $username);
	}

	public static function exists($username) {
		$username = (string) $username;
		return $username !== '' && flatDB::DBexists('users', $username);
	}

	/**
	 * 创建用户；用户名即文件名，须先过合法性与唯一性校验
	 */
	public static function create(array $data) {
		if (empty($data['username']) || self::exists($data['username'])) return false;
		flatDB::saveDB('users', $data['username'], $data);
		return true;
	}

	public static function save($username, array $data) {
		return flatDB::saveDB('users', $username, $data);
	}

	/**
	 * 用户名合法性（注册/查重前过滤）：字母开头，字母数字下划线，3-24 位
	 */
	public static function validName($username) {
		return is_string($username) && preg_match('/^[A-Za-z][A-Za-z0-9_]{2,23}$/', $username) === 1;
	}

	/**
	 * 密码强度：≥8 位，含字母与数字
	 */
	public static function validPassword($password) {
		return is_string($password) && strlen($password) >= 8
			&& preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
	}

	public static function validEmail($email) {
		return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * 昵称合法性（显示名，可随时改，SPEC §9 #12）：1-30 字符，禁控制字符
	 */
	public static function validNickname($nickname) {
		return is_string($nickname) && mb_strlen($nickname) >= 1 && mb_strlen($nickname) <= 30
			&& !preg_match('/[\x00-\x1F\x7F]/', $nickname);
	}

	/**
	 * Bio 一句话介绍（知乎风，用户决议：替代 BBS 签名风格；帖子楼层/用户页展示）
	 * 0-200 字符，禁控制字符（单行文本）
	 */
	public static function validBio($bio) {
		return is_string($bio) && mb_strlen($bio) <= 200
			&& !preg_match('/[\x00-\x1F\x7F]/', $bio);
	}

	/* ---------------- 封禁（B3-4b；level 不动，封禁是独立状态） ---------------- */

	/** 永久封禁（时间字段规约：一律 unix int，SPEC §11.4） */
	const BAN_FOREVER = PHP_INT_MAX;

	/**
	 * 用户是否处于封禁中（banned_until > now；0 = 未封禁）
	 */
	public static function isBanned(array $user) {
		return ((int) ($user['banned_until'] ?? 0)) > time();
	}

	/**
	 * 封禁/解封唯一写入口（user/ban 路由与举报队列处理共用）。
	 * @param int $until unix 时间戳；0 = 解封；User::BAN_FOREVER = 永久
	 * @return bool
	 */
	public static function setBan($username, $until, $by, $reason = '') {
		$user = self::get($username);
		if ($user === null) return false;
		$user['banned_until'] = max(0, (int) $until);
		$user['banned_by'] = $user['banned_until'] > 0 ? (string) $by : '';
		$user['banned_reason'] = $user['banned_until'] > 0 ? (string) $reason : '';
		return self::save((string) $username, $user);
	}

	/* ---------------- recovery_codes（SPEC §9 #10：无邮件重置，哈希存储、一次性） ---------------- */

	const RECOVERY_COUNT = 6;

	/**
	 * 生成一批恢复码：$plain 写出明文（仅注册成功页展示一次），
	 * 返回值是哈希数组（直接存入 users 的 recovery_codes 字段）
	 *
	 * @param array|null $plain [输出] 明文恢复码列表
	 * @return array sha256 哈希列表
	 */
	public static function generateRecoveryCodes(&$plain = null) {
		$plain = array();
		$hashes = array();
		for ($i = 0; $i < self::RECOVERY_COUNT; $i++) {
			$code = bin2hex(random_bytes(5)); // 10 位十六进制
			$plain[] = $code;
			$hashes[] = hash('sha256', $code);
		}
		return $hashes;
	}

	/**
	 * 核销一个恢复码（一次性）：命中则从 $user['recovery_codes'] 移除并返回 true，
	 * 调用方须在核销成功后自行 Model\User::save()
	 *
	 * @param array $user 用户关联数组（引用，核销会修改）
	 * @param string $code 用户输入的明文恢复码（容忍大小写/空格/连字符）
	 * @return bool 命中并移除返回 true
	 */
	public static function consumeRecoveryCode(array &$user, $code) {
		$code = strtolower(preg_replace('/[\s\-]/', '', (string) $code));
		if ($code === '') return false;
		$hash = hash('sha256', $code);
		$stored = $user['recovery_codes'] ?? array();
		foreach ($stored as $i => $h) {
			if (hash_equals((string) $h, $hash)) {
				unset($stored[$i]);
				$user['recovery_codes'] = array_values($stored);
				return true;
			}
		}
		return false;
	}
}
