<?php namespace Model;

defined('FLOO') or die('potato'); // NOOOO

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
		if ($username === '' || !\flatDB::DBexists('users', $username)) return null;
		return \flatDB::getDB('users', $username);
	}

	public static function exists($username) {
		$username = (string) $username;
		return $username !== '' && \flatDB::DBexists('users', $username);
	}

	/**
	 * 创建用户；用户名即文件名，须先过合法性与唯一性校验
	 */
	public static function create(array $data) {
		if (empty($data['username']) || self::exists($data['username'])) return false;
		\flatDB::saveDB('users', $data['username'], $data);
		return true;
	}

	public static function save($username, array $data) {
		return \flatDB::saveDB('users', $username, $data);
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
}
