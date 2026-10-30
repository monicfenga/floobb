<?php namespace Model;

defined('FLOO') or die('potato'); // NOOOO

/**
 * 版块仓储【model 层 = 关联数组 + 静态仓储函数，SPEC §3.3】
 * 存储：data/forums/<forumId>.dat.json（SPEC §4）
 */
class Forum {

	public static function get($forumId) {
		$forumId = (string) $forumId;
		if ($forumId === '' || !\flatDB::DBexists('forums', $forumId)) return null;
		return \flatDB::getDB('forums', $forumId);
	}

	public static function exists($forumId) {
		return self::get($forumId) !== null;
	}

	/**
	 * 全部版块，按 order 升序
	 */
	public static function listAll() {
		$forums = array();
		foreach (\flatDB::listDB('forums') as $file) {
			$id = basename($file, '.dat.json');
			$f = \flatDB::getDB('forums', $id);
			if (is_array($f)) $forums[] = $f;
		}
		usort($forums, function ($a, $b) {
			return ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
		});
		return $forums;
	}

	public static function save($forumId, array $data) {
		return \flatDB::saveDB('forums', $forumId, $data);
	}

	public static function create(array $data) {
		$id = \flatDB::newEntry();
		$data['id'] = $id;
		$data['order'] = (int) ($data['order'] ?? count(self::listAll()));
		\flatDB::saveDB('forums', $id, $data);
		return $id;
	}
}
