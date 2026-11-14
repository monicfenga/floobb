<?php
/**
 * Activity.php — SNS 动态仓储层（Phase 2，SPEC §4 activity 实体、§9 #17）
 *
 * 关联数组 + 静态仓储函数（禁 OOP 实体）。数据落 data/activity/<newEntry()>.dat.json。
 * 系统事件写入（注册/发主题/回帖/关注）由对应控制器调用 record()，失败静默日志不阻断主流程。
 */

class Activity
{
	/** type 白名单（SPEC §4） */
	const TYPES = array('status', 'register', 'new_topic', 'new_reply', 'comment', 'follow');
	/** visibility 白名单（SPEC §9 #17） */
	const VISIBILITIES = array('public', 'login', 'followers', 'private');
	/** 时间线每页条数（拍板：与回帖分页一致） */
	const PER_PAGE = 10;

	/**
	 * 写入一条活动（系统事件统一入口）。
	 * 任何失败静默 error_log，绝不阻断主流程（KICKOFF 硬约束）。
	 * @param string $type    Activity::TYPES 之一
	 * @param string $actor   用户名
	 * @param string $text    status/comment 的正文；系统事件留空
	 * @param string $refId   关联 topic_id / post_id / activity_id / username
	 * @param string $visibility  public|login|followers|private（缺省取作者 default_visibility）
	 * @return string|null 活动 id，失败 null
	 */
	public static function record($type, $actor, $text = '', $refId = '', $visibility = '')
	{
		try {
			if (!in_array($type, self::TYPES, true)) return null;
			if ($visibility === '' || !in_array($visibility, self::VISIBILITIES, true)) {
				$visibility = self::defaultVisibility($actor);
			}
			$id = flatDB::newEntry();
			$activity = array(
				'id' => $id,
				'type' => $type,
				'actor' => (string) $actor,
				'text' => (string) $text,
				'ref_id' => (string) $refId,
				'created' => time(),
				'public' => true,
				'visibility' => $visibility,
				'likes' => 0,            // 点赞数（int，默认 0；用户拍板 2026-11-14）
				'liked_by' => array(),   // 点过赞的用户名（防重复点赞 + 支持取消）
			);
			if (flatDB::saveDB('activity', $id, $activity) === false) {
				throw new RuntimeException('saveDB failed');
			}
			// 插件扩展点（SPEC §9 #17）：活动落盘后触发
			Plugin::hook('activity_record', $activity);
			return $id;
		} catch (Throwable $e) {
			error_log('[Activity::record] ' . $e->getMessage());
			return null;
		}
	}

	/** 作者的新动态默认可见度（users.default_visibility，缺省 public） */
	public static function defaultVisibility($username)
	{
		$user = User::get((string) $username);
		$v = $user['default_visibility'] ?? 'public';
		return in_array($v, self::VISIBILITIES, true) ? $v : 'public';
	}

	/**
	 * viewer 能否看到活动（四级可见性，按 actor 判断，SPEC §9 #17）。
	 * @param string|null $viewer 用户名；null = 游客
	 */
	public static function canView($activity, $viewer)
	{
		$actor = (string) ($activity['actor'] ?? '');
		if ($actor === '') return false;
		if ($viewer !== null && (string) $viewer === $actor) return true; // 作者恒可见
		$visibility = $activity['visibility'] ?? 'public';
		switch ($visibility) {
			case 'public':
				return true;
			case 'login':
				return $viewer !== null;
			case 'followers':
				if ($viewer === null) return false;
				$actorUser = User::get($actor);
				return in_array((string) $viewer, $actorUser['fans'] ?? array(), true);
			case 'private':
			default:
				return false;
		}
	}

	/** 公共时间线（public=true 全体可见 + 可见性过滤 + type 筛选 + 关注流 + 分页） */
	public static function listTimeline($page = 1, $type = null, $viewer = null, $followOnlyFor = null)
	{
		$rows = array();
		$files = flatDB::listDB('activity');
		// listDB 返回文件名列表（含 .dat.json），剥壳后按 created 倒序排
		foreach ($files as $file) {
			$id = preg_replace('/\.dat\.json$/i', '', (string) $file);
			$a = flatDB::getDB('activity', $id);
			if (!is_array($a) || empty($a['id'])) continue;
			// type 筛选（query string 白名单外视为全部）
			if ($type !== null && ($a['type'] ?? '') !== $type) continue;
			// 关注流：只看 actor ∈ 我关注的人 + 我自己
			if ($followOnlyFor !== null) {
				$me = (string) $followOnlyFor;
				$meUser = User::get($me);
				$follows = $meUser['follows'] ?? array();
				if (!in_array((string) ($a['actor'] ?? ''), $follows, true)
					&& (string) ($a['actor'] ?? '') !== $me) {
					continue;
				}
			}
			$rows[] = $a;
		}
		// created 倒序；同秒时按 id 字符串倒序（id 尾部含递增随机段，保证新条目在前）
		usort($rows, function ($a, $b) {
			$c = ($b['created'] ?? 0) <=> ($a['created'] ?? 0);
			return $c !== 0 ? $c : ((string) ($b['id'] ?? '')) <=> ((string) ($a['id'] ?? ''));
		});
		// 可见性过滤（作者恒可见等，游客只看 public）
		$rows = array_values(array_filter($rows, function ($a) use ($viewer) {
			return self::canView($a, $viewer);
		}));
		return array(
			'total' => count($rows),
			'items' => array_slice($rows, (max(1, (int) $page) - 1) * self::PER_PAGE, self::PER_PAGE),
		);
	}

	/** 某用户的活动（用户页时间线用；type 系统事件也会带出） */
	public static function listByActor($username, $viewer = null)
	{
		$rows = array();
		foreach (flatDB::listDB('activity') as $file) {
			$id = preg_replace('/\.dat\.json$/i', '', (string) $file);
			$a = flatDB::getDB('activity', $id);
			if (!is_array($a) || ($a['actor'] ?? '') !== (string) $username) continue;
			if (!self::canView($a, $viewer)) continue;
			$rows[] = $a;
		}
		usort($rows, function ($a, $b) {
			$c = ($b['created'] ?? 0) <=> ($a['created'] ?? 0);
			return $c !== 0 ? $c : ((string) ($b['id'] ?? '')) <=> ((string) ($a['id'] ?? ''));
		});
		return $rows;
	}

	/** 按类型列出（管理/统计用） */
	public static function listByType($type, $viewer = null)
	{
		return self::listTimeline(1, $type, $viewer)['items']; // 筛选语义复用；翻页由调用方处理
	}

	/** 取单条（不存在返回 null） */
	public static function get($id)
	{
		if ($id === '' || !flatDB::DBexists('activity', $id)) return null;
		$a = flatDB::getDB('activity', $id);
		return is_array($a) ? $a : null;
	}

	/**
	 * 点赞 toggle（用户拍板 2026-11-14：仅 status 类型的动态可点赞）。
	 * 已点过 → 取消（likes-1、移出 liked_by）；未点 → 点赞（likes+1）。
	 * @return int|null 新的 likes 计数；动态不存在/类型不符/失败返回 null
	 */
	public static function toggleLike($id, $username)
	{
		try {
			$a = self::get((string) $id);
			if ($a === null || ($a['type'] ?? '') !== 'status') return null;
			$username = (string) $username;
			$likedBy = $a['liked_by'] ?? array();
			if (in_array($username, $likedBy, true)) {
				$likedBy = array_values(array_filter($likedBy, function ($u) use ($username) {
					return (string) $u !== $username;
				}));
			} else {
				$likedBy[] = $username;
			}
			$a['liked_by'] = $likedBy;
			$a['likes'] = max(0, count($likedBy));
			if (flatDB::saveDB('activity', (string) $id, $a) === false) return null;
			return $a['likes'];
		} catch (Throwable $e) {
			error_log('[Activity::toggleLike] ' . $e->getMessage());
			return null;
		}
	}

	/** 某活动的评论（type=comment 且 ref_id=父活动 id），created 正序 */
	public static function listComments($activityId)
	{
		$rows = array();
		foreach (flatDB::listDB('activity') as $file) {
			$id = preg_replace('/\.dat\.json$/i', '', (string) $file);
			$a = flatDB::getDB('activity', $id);
			if (!is_array($a) || ($a['type'] ?? '') !== 'comment') continue;
			if (($a['ref_id'] ?? '') !== (string) $activityId) continue;
			$rows[] = $a;
		}
		usort($rows, function ($a, $b) {
			return ($a['created'] ?? 0) <=> ($b['created'] ?? 0);
		});
		return $rows;
	}
}
