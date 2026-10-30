<?php

defined('FLOO') or die('FlooBB');

/**
 * 站内信仓储【model 层 = 关联数组 + 静态仓储函数，SPEC §3.3】
 * 存储：data/pms/<username>.dat.json = { "inbox": [...], "outbox": [...] }（SPEC §4）
 * 发送 = 双写（收件人 inbox 追加 + 发件人 outbox 追加同 id 副本）；
 * 已读只作用收件人侧 inbox 条目；删除各删各副本，单向不通知对方。
 */
class PM {

	/**
	 * 取某用户的信箱（inbox + outbox）；文件不存在视为空箱
	 */
	public static function getBox($username) {
		if (!flatDB::DBexists('pms', (string) $username)) {
			return array('inbox' => array(), 'outbox' => array());
		}
		$box = flatDB::getDB('pms', (string) $username);
		return array(
			'inbox' => array_values((array) ($box['inbox'] ?? array())),
			'outbox' => array_values((array) ($box['outbox'] ?? array())),
		);
	}

	public static function saveBox($username, array $box) {
		return flatDB::saveDB('pms', (string) $username, $box);
	}

	/**
	 * 发送：双写两侧文件。已由调用方禁止自发自收。
	 * @return string 新消息 id
	 */
	public static function send($from, $to, $subject, $body) {
		$id = flatDB::newEntry();
		$now = date('Y-m-d\TH:i:s');
		$msg = array(
			'id' => $id,
			'from' => (string) $from,
			'to' => (string) $to,
			'subject' => (string) $subject,
			'body' => (string) $body,
			'time' => $now,
			'read' => false,
		);
		$toBox = self::getBox($to);
		$toBox['inbox'][] = $msg;
		self::saveBox($to, $toBox);
		$fromBox = self::getBox($from);
		$fromBox['outbox'][] = $msg;
		self::saveBox($from, $fromBox);
		return $id;
	}

	/**
	 * 在指定信箱侧（inbox/outbox）按 id 查找消息；不存在返回 null
	 */
	public static function find($username, $side, $id) {
		$box = self::getBox($username);
		foreach ($box[$side] as $i => $msg) {
			if ((string) ($msg['id'] ?? '') === (string) $id) {
				return array('index' => $i, 'msg' => $msg);
			}
		}
		return null;
	}

	/**
	 * 标记收件箱某条为已读
	 */
	public static function markRead($username, $id) {
		$box = self::getBox($username);
		foreach ($box['inbox'] as $i => $msg) {
			if ((string) ($msg['id'] ?? '') === (string) $id) {
				if (empty($msg['read'])) {
					$box['inbox'][$i]['read'] = true;
					self::saveBox($username, $box);
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * 删除指定侧的一条消息（各删各副本，单向）
	 */
	public static function remove($username, $side, $id) {
		$box = self::getBox($username);
		foreach ($box[$side] as $i => $msg) {
			if ((string) ($msg['id'] ?? '') === (string) $id) {
				unset($box[$side][$i]);
				$box[$side] = array_values($box[$side]);
				self::saveBox($username, $box);
				return true;
			}
		}
		return false;
	}

	/**
	 * 未读数（导航徽标用）：仅统计当前用户 inbox
	 */
	public static function unreadCount($username) {
		$n = 0;
		foreach (self::getBox($username)['inbox'] as $msg) {
			if (empty($msg['read'])) $n++;
		}
		return $n;
	}
}
