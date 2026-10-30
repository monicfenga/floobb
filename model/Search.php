<?php

defined('FLOO') or die('FlooBB');

/**
 * 搜索【model 层 = 静态函数 + flatDB，SPEC §3.3】
 *
 * B3-5 已定方案（用户拍板）：
 * - 范围：topics(title) / posts(content_md) / users(username+nickname)，?scope= 选
 * - 匹配：LIKE '%%' 风格子串匹配（stripos），不做分词、不建索引（SPEC §11 不引 data/index/）
 * - 截断：posts 最多扫最新 1000 帖；topics/users 全量；性能标准 = 3 秒内返回
 */
class Search {

	/** posts 搜索池上限（按 created 降序取前 N） */
	const POST_POOL = 1000;

	/**
	 * 执行搜索。返回 ['items'=>..., 'truncated'=>bool]
	 * @param string $q     原始查询串（空白即整体子串）
	 * @param string $scope topics|posts|users
	 */
	public static function query($q, $scope = 'topics') {
		$q = trim((string) $q);
		if ($q === '') return array('items' => array(), 'truncated' => false);

		switch ($scope) {
			case 'posts':    return self::searchPosts($q);
			case 'users':    return self::searchUsers($q);
			case 'topics':
			default:         return self::searchTopics($q);
		}
	}

	/** topics：title 子串命中，created 降序 */
	private static function searchTopics($q) {
		$items = array();
		foreach (flatDB::listDB('topics') as $file) {
			$t = flatDB::getDB('topics', basename($file, '.dat.json'));
			if (is_array($t) && self::hit($q, (string) ($t['title'] ?? ''))) {
				$items[] = array(
					'topic_id' => (string) ($t['id'] ?? basename($file, '.dat.json')),
					'title' => (string) ($t['title'] ?? ''),
					'author' => (string) ($t['author'] ?? ''),
					'created' => $t['created'] ?? 0,
					'posts' => count((array) ($t['post_ids'] ?? array())),
				);
			}
		}
		usort($items, function ($a, $b) { return ($b['created'] ?? 0) <=> ($a['created'] ?? 0); });
		return array('items' => $items, 'truncated' => false);
	}

	/** posts：content_md 子串命中；只扫最新 1000 帖（created 降序截断） */
	private static function searchPosts($q) {
		$pool = array();
		foreach (flatDB::listDB('posts') as $file) {
			$p = flatDB::getDB('posts', basename($file, '.dat.json'));
			if (is_array($p)) $pool[] = $p;
		}
		usort($pool, function ($a, $b) { return ($b['created'] ?? 0) <=> ($a['created'] ?? 0); });
		$truncated = count($pool) > self::POST_POOL;
		$pool = array_slice($pool, 0, self::POST_POOL);

		// 帖子 → 所属主题标题一次性建映射（结果页展示用）
		$topicTitles = array();
		$items = array();
		foreach ($pool as $p) {
			if (!self::hit($q, (string) ($p['content_md'] ?? ''))) continue;
			$tid = (string) ($p['topic_id'] ?? '');
			if (!isset($topicTitles[$tid])) {
				$t = Topic::get($tid);
				$topicTitles[$tid] = $t !== null ? (string) ($t['title'] ?? '') : '';
			}
			$items[] = array(
				'topic_id' => $tid,
				'post_id' => (string) ($p['id'] ?? ''),
				'topic_title' => $topicTitles[$tid],
				'author' => (string) ($p['author'] ?? ''),
				'created' => $p['created'] ?? 0,
				'excerpt' => self::excerpt((string) ($p['content_md'] ?? ''), $q),
			);
		}
		usort($items, function ($a, $b) { return ($b['created'] ?? 0) <=> ($a['created'] ?? 0); });
		return array('items' => $items, 'truncated' => $truncated);
	}

	/** users：username 或 nickname 子串命中（bio 不搜，按用户拍板范围），level 降序 */
	private static function searchUsers($q) {
		$items = array();
		foreach (flatDB::listDB('users') as $file) {
			$u = flatDB::getDB('users', basename($file, '.dat.json'));
			if (!is_array($u)) continue;
			$un = (string) ($u['username'] ?? '');
			$nk = (string) ($u['nickname'] ?? '');
			if (self::hit($q, $un) || self::hit($q, $nk)) {
				$items[] = array(
					'username' => $un,
					'nickname' => $nk !== '' ? $nk : $un,
					'level' => $u['level'] ?? 1,
					'registered' => $u['registered'] ?? 0,
				);
			}
		}
		usort($items, function ($a, $b) { return ($b['registered'] ?? 0) <=> ($a['registered'] ?? 0); });
		return array('items' => $items, 'truncated' => false);
	}

	/** LIKE '%%' 风格：大小写不敏感子串命中 */
	private static function hit($q, $haystack) {
		return $haystack !== '' && stripos($haystack, (string) $q) !== false;
	}

	/** 命中处摘录（前后各 60 字符，命中词打不上标记——转义在模板层做） */
	private static function excerpt($md, $q) {
		$pos = stripos($md, $q);
		$md = trim(preg_replace('/\s+/u', ' ', $md));
		$pos = stripos($md, $q);
		if ($pos === false) return mb_substr($md, 0, 120);
		$start = max(0, $pos - 60);
		$pre = $start > 0 ? '…' : '';
		$post = $pos + mb_strlen($q) + 60 < mb_strlen($md) ? '…' : '';
		return $pre . mb_substr($md, $start, mb_strlen($q) + 120) . $post;
	}
}
