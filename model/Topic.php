<?php

defined('FLOO') or die('FlooBB');

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
	 * 版块下的主题（不含置顶排序逻辑，排序按 $sort）：
	 * latest=最新发帖 created / replied=最新回帖 last_reply_at / posts=最多回帖 / views=最多查看
	 */
	public static function listByForum($forumId, $sort = 'latest') {
		$topics = array();
		foreach (flatDB::listDB('topics') as $file) {
			$id = basename($file, '.dat.json');
			$t = flatDB::getDB('topics', $id);
			if (is_array($t) && (string) ($t['forum_id'] ?? '') === (string) $forumId) {
				$topics[] = $t;
			}
		}
		return self::sortBy($topics, $sort);
	}

	/**
	 * 排序键统一入口：稳定性依赖 created 兜底
	 */
	public static function sortBy(array $topics, $sort = 'latest') {
		$lastActivity = function (array $t) {
			return ($t['last_reply_at'] ?? 0) ?: ($t['created'] ?? 0);
		};
		usort($topics, function ($a, $b) use ($sort, $lastActivity) {
			$createdDesc = ($b['created'] ?? 0) <=> ($a['created'] ?? 0); // 兜底：时间降序
			$activityDesc = $lastActivity($b) <=> $lastActivity($a); // 最近活动降序
			switch ($sort) {
				case 'latest':  return $createdDesc;
				case 'replied': return $activityDesc ?: $createdDesc;
				case 'posts':   return (count((array) ($b['post_ids'] ?? array())) <=> count((array) ($a['post_ids'] ?? array()))) ?: $createdDesc;
				case 'views':   return (($b['views'] ?? 0) <=> ($a['views'] ?? 0)) ?: $createdDesc;
				default:        return $createdDesc;
			}
		});
		return $topics;
	}

	/**
	 * 全站最新主题（首页用；按最后活跃降序，置顶在前）
	 */
	public static function listLatest($limit = 10) {
		$topics = array();
		foreach (flatDB::listDB('topics') as $file) {
			$t = flatDB::getDB('topics', basename($file, '.dat.json'));
			if (is_array($t)) $topics[] = $t;
		}
		$topics = self::sortBy($topics, 'replied');
		usort($topics, function ($a, $b) {
			return ($b['sticky'] ?? false) <=> ($a['sticky'] ?? false);
		});
		return array_slice($topics, 0, (int) $limit);
	}

	/**
	 * 浏览计数 +1（主题详情页每次 GET 调用）
	 */
	public static function incrViews($topicId) {
		$t = self::get($topicId);
		if ($t === null) return;
		$t['views'] = ($t['views'] ?? 0) + 1;
		self::save((string) $topicId, $t);
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

	/**
	 * 某作者的主题，created 降序截前 $limit 条（个人资料页「近期发帖」用）
	 */
	public static function listByAuthor($author, $limit = 10) {
		$topics = array();
		foreach (flatDB::listDB('topics') as $file) {
			$t = flatDB::getDB('topics', basename($file, '.dat.json'));
			if (is_array($t) && (string) ($t['author'] ?? '') === (string) $author) $topics[] = $t;
		}
		$topics = self::sortBy($topics, 'latest');
		return array_slice($topics, 0, (int) $limit);
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
		return $user['username'] === ($topic['author'] ?? '') || $user['level'] >= 3;
	}
}
