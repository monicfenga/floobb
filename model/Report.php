<?php defined('FLOO') or die('FlooBB');

/**
 * 举报仓储【model 层 = 关联数组 + 静态仓储函数，SPEC §3.3】
 * 存储：data/reports/<reportId>.dat.json，一举报一文件（SPEC §4）
 * type 限 post|topic|user（B3-4b 加 user，处理动作=封禁）；status: open|resolved|dismissed
 */
class Report {

	/** 举报原因白名单（SPEC §4） */
	public static function reasons() {
		return array('spam' => '垃圾广告', 'abuse' => '辱骂攻击', 'illegal' => '违法违规', 'other' => '其他');
	}

	/**
	 * 创建举报。type 白名单 post|topic|user；target 必须真实存在；不许举报自己
	 * @return string|null 成功返回新举报 id；失败返回 null
	 */
	public static function create($type, $targetId, $reporter, $reason, $detail) {
		$type = (string) $type;
		if (!in_array($type, array('post', 'topic', 'user'), true)) return null;
		if (!in_array($reason, array_keys(self::reasons()), true)) return null;
		if ((string) $targetId === (string) $reporter) return null;

		// 目标存在性校验（顺带拿摘要供队列展示）
		if ($type === 'post') {
			$target = Post::get((string) $targetId);
		} elseif ($type === 'topic') {
			$target = Topic::get((string) $targetId);
		} else {
			$target = User::get((string) $targetId);
		}
		if ($target === null) return null;

		$id = flatDB::newEntry();
		flatDB::saveDB('reports', $id, array(
			'id' => $id,
			'type' => $type,
			'target_id' => (string) $targetId,
			'reporter' => (string) $reporter,
			'reason' => (string) $reason,
			'detail' => (string) $detail,
			'created' => date('Y-m-d\TH:i:s'),
			'status' => 'open',
			'resolved_by' => '',
			'resolution' => '',
		));
		return $id;
	}

	public static function get($id) {
		$id = (string) $id;
		if ($id === '' || !flatDB::DBexists('reports', $id)) return null;
		return flatDB::getDB('reports', $id);
	}

	/**
	 * 全量列表（队列量级小，直接全扫）；open 优先，各按时间倒序
	 * @return array ['open' => [...], 'done' => [...]]
	 */
	public static function listAll() {
		$open = array();
		$done = array();
		foreach (flatDB::listDB('reports') as $file) {
			$id = basename((string) $file, '.dat.json');
			$r = flatDB::getDB('reports', $id);
			if ($r === null) continue;
			if ((string) ($r['status'] ?? '') === 'open') $open[] = $r;
			else $done[] = $r;
		}
		$cmp = function ($a, $b) {
			return strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? ''));
		};
		usort($open, $cmp);
		usort($done, $cmp);
		return array('open' => $open, 'done' => $done);
	}

	/**
	 * 处理举报（单写路径维护状态）。
	 * @param string $action delete|dismiss|ban（ban 供 user 举报，B3-4b）
	 * @return bool
	 */
	public static function handle($id, $modUsername, $action) {
		$r = self::get($id);
		if ($r === null) return false;
		if (!in_array($action, array('delete', 'dismiss', 'ban'), true)) return false;
		if ((string) $r['status'] !== 'open') return false;

		$r['status'] = $action === 'dismiss' ? 'dismissed' : 'resolved';
		// resolution 用 SPEC §4 词汇表：deleted / no_action / banned
		$r['resolution'] = $action === 'delete' ? 'deleted' : ($action === 'ban' ? 'banned' : 'no_action');
		$r['resolved_by'] = (string) $modUsername;
		return flatDB::saveDB('reports', $id, $r);
	}
}
