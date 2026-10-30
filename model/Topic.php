<?php

defined('FLOO') or die('potato'); // NOOOO

/**
 * 主题仓储【model 层 = 关联数组 + 静态仓储函数，SPEC §3.3】
 * 存储：data/topics/<topicId>.dat.json（SPEC §4）
 */
class Topic {

	public static function get($topicId) {
		$topicId = (string) $topicId;
		if ($topicId === '' || !flatDB::DBexists('topics', $topicId)) return null;
		return flatDB::getDB('topics', $topicId);
	}

	/**
	 * 版块下的主题（置顶在前，其余按 created 降序）
	 */
	public static function listByForum($forumId) {
		$topics = array();
		foreach (flatDB::listDB('topics') as $file) {
			$id = basename($file, '.dat.json');
			$t = flatDB::getDB('topics', $id);
			if (is_array($t) && (string) ($t['forum_id'] ?? '') === (string) $forumId) {
				$topics[] = $t;
			}
		}
		usort($topics, function ($a, $b) {
			$sticky = ((int) ($b['sticky'] ?? false)) <=> ((int) ($a['sticky'] ?? false));
			return $sticky !== 0 ? $sticky : strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? ''));
		});
		return $topics;
	}

	public static function save($topicId, array $data) {
		return flatDB::saveDB('topics', $topicId, $data);
	}

	/**
	 * 某作者的主题数（个人资料页统计；flat-file 全扫，量级小可接受）
	 */
	public static function countByAuthor($author) {
		$n = 0;
		foreach (flatDB::listDB('topics') as $file) {
			$t = flatDB::getDB('topics', basename($file, '.dat.json'));
			if (is_array($t) && (string) ($t['author'] ?? '') === (string) $author) $n++;
		}
		return $n;
	}

	public static function delete($topicId) {
		return flatDB::removeDB('topics', (string) $topicId);
	}

	public static function create(array $data) {
		$id = flatDB::newEntry();
		$data['id'] = $id;
		flatDB::saveDB('topics', $id, $data);
		return $id;
	}

	/* ---- 业务判断（函数表达，不挂对象属性）---- */

	public static function isLocked(array $topic) {
		return (bool) ($topic['locked'] ?? false);
	}

	public static function isSticky(array $topic) {
		return (bool) ($topic['sticky'] ?? false);
	}

	public static function canEdit(array $topic, $user) {
		if ($user === null) return false;
		return $user['username'] === ($topic['author'] ?? '') || (int) $user['level'] >= 3;
	}
}
