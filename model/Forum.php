<?php

defined('FLOO') or die('FlooBB');

/**
 * 版块仓储【model 层 = 关联数组 + 静态仓储函数，SPEC §3.3】
 * 存储：data/forums/<forumId>.dat.json（SPEC §4）
 */
class Forum {

	public static function get($forumId) {
		$forumId = (string) $forumId;
		if ($forumId === '' || !flatDB::DBexists('forums', $forumId)) return null;
		return flatDB::getDB('forums', $forumId);
	}

	public static function exists($forumId) {
		return self::get($forumId) !== null;
	}

	/**
	 * 全部版块，按 order 升序
	 */
	public static function listAll() {
		$forums = array();
		foreach (flatDB::listDB('forums') as $file) {
			$id = basename($file, '.dat.json');
			$f = flatDB::getDB('forums', $id);
			if (is_array($f)) $forums[] = $f;
		}
		usort($forums, function ($a, $b) {
			return (($a['order'] ?? 0) <=> ($b['order'] ?? 0));
		});
		return $forums;
	}

	public static function save($forumId, array $data) {
		return flatDB::saveDB('forums', $forumId, $data);
	}

	public static function create(array $data) {
		// 版块 ID = slug（可读 URL）；主题/帖子仍用 flatDB::newEntry()
		$id = (string) ($data['slug'] ?? '');
		if ($id === '' || self::exists($id)) return null;
		$data['id'] = $id;
		$data['order'] = $data['order'] ?? count(self::listAll());
		$data['topic_count'] = $data['topic_count'] ?? 0;
		$data['post_count'] = $data['post_count'] ?? 0;
		$data['sticky_topic_ids'] = (array) ($data['sticky_topic_ids'] ?? array());
		flatDB::saveDB('forums', $id, $data);
		return $id;
	}

	/**
	 * 版块最后活跃时间（该版块下最新主题的活动时间；无主题返回 0）
	 */
	public static function lastActive($forumId) {
		$latest = 0;
		foreach (flatDB::listDB('topics') as $file) {
			$t = flatDB::getDB('topics', basename($file, '.dat.json'));
			if (is_array($t) && (string) ($t['forum_id'] ?? '') === (string) $forumId) {
				// 回帖活跃优先，退回发帖时间
				$c = ($t['last_reply_at'] ?? 0) ?: ($t['created'] ?? 0);
				if ($c > $latest) $latest = $c;
			}
		}
		return $latest;
	}

	/**
	 * 计数一致性：建/删主题与帖子同步增减（delta 可为负，负向不穿 0）
	 */
	public static function adjustCounts($forumId, $topicsDelta, $postsDelta) {
		$f = self::get($forumId);
		if ($f === null) return;
		$f['topic_count'] = max(0, (int) ($f['topic_count'] ?? 0) + (int) $topicsDelta);
		$f['post_count'] = max(0, (int) ($f['post_count'] ?? 0) + (int) $postsDelta);
		self::save($forumId, $f);
	}

	/**
	 * 维护 forums.sticky_topic_ids（SPEC §4）：置顶/取消置顶时同步
	 */
	public static function setStickyRef($forumId, $topicId, $on) {
		$f = self::get($forumId);
		if ($f === null) return;
		$ids = array_values((array) ($f['sticky_topic_ids'] ?? array()));
		$idx = array_search((string) $topicId, $ids, true);
		if ($on && $idx === false) {
			$ids[] = (string) $topicId;
		} elseif (!$on && $idx !== false) {
			unset($ids[$idx]);
		}
		$f['sticky_topic_ids'] = array_values($ids);
		self::save($forumId, $f);
	}
}
