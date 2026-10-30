<?php defined('FLOO') or die('FlooBB');

/**
 * 投票仓储【model 层 = 关联数组 + 静态仓储函数，SPEC §3.3】
 * 存储：data/polls/<topicId>.dat.json（文件名 = tid，SPEC §4）
 * votes 结构：{ username: [optionIndex, ...] }（多选记多下标，单选单元素）
 */
class Poll {

	/** 选项数上下限 */
	const MIN_OPTIONS = 2;
	const MAX_OPTIONS = 8;

	/**
	 * 取某主题的投票；不存在返回 null
	 */
	public static function get($topicId) {
		$topicId = (string) $topicId;
		if ($topicId === '' || !flatDB::DBexists('polls', $topicId)) return null;
		return flatDB::getDB('polls', $topicId);
	}

	/**
	 * 建票（发主题时一并建；tid 即文件名）
	 * @param string $topicId 主题 id
	 * @param array $data question/options/max_choices/deadline
	 * @return bool
	 */
	public static function create($topicId, array $data) {
		$data['options'] = array_values((array) ($data['options'] ?? array()));
		$poll = array(
			'question' => (string) ($data['question'] ?? ''),
			'options' => $data['options'], // [{text, votes:0}]
			'votes' => new stdClass(),     // {}: 谁投了哪些选项下标（空对象落 JSON）
			'max_choices' => max(1, (int) ($data['max_choices'] ?? 1)),
			'deadline' => (string) ($data['deadline'] ?? ''),
			'closed' => false,
			'closed_by' => '',
		);
		return flatDB::saveDB('polls', (string) $topicId, $poll);
	}

	/**
	 * 投票。校验序：存在 → 未关闭 → 未过期 → 未投过 → 选项下标合法 → 数量≤max_choices
	 * @return string|null 成功返回 null；失败返回错误键
	 */
	public static function vote($topicId, $username, array $choices) {
		$poll = self::get($topicId);
		if ($poll === null) return 'poll.notfound';
		if (!empty($poll['closed'])) return 'poll.closed';
		if (self::isExpired($poll)) return 'poll.expired';
		$votes = (array) ($poll['votes'] ?? array());
		if (array_key_exists((string) $username, $votes)) return 'poll.already';

		$choices = array_values(array_unique(array_map('intval', $choices)));
		$n = count((array) ($poll['options'] ?? array()));
		foreach ($choices as $c) {
			if ($c < 0 || $c >= $n) return 'poll.option.invalid';
		}
		if (empty($choices)) return 'poll.option.empty';
		if (count($choices) > (int) ($poll['max_choices'] ?? 1)) return 'poll.toomany';

		// 记 votes + 选项票数（同一写路径维护一致性）
		$votes[(string) $username] = $choices;
		$poll['votes'] = $votes;
		foreach ($choices as $c) {
			$poll['options'][$c]['votes'] = (int) ($poll['options'][$c]['votes'] ?? 0) + 1;
		}
		return flatDB::saveDB('polls', (string) $topicId, $poll) ? null : 'poll.save.failed';
	}

	/**
	 * 关闭投票（主题作者或 level≥2，控制器校验）
	 */
	public static function close($topicId, $byUsername) {
		$poll = self::get($topicId);
		if ($poll === null) return false;
		$poll['closed'] = true;
		$poll['closed_by'] = (string) $byUsername;
		return flatDB::saveDB('polls', (string) $topicId, $poll);
	}

	/**
	 * 是否已过期（deadline 非空且已过 → 视为自动关闭，只读展示）
	 */
	public static function isExpired(array $poll) {
		$dl = (string) ($poll['deadline'] ?? '');
		return $dl !== '' && strtotime($dl) < time();
		}

	/**
	 * 某用户是否已投
	 */
	public static function hasVoted(array $poll, $username) {
		return array_key_exists((string) $username, (array) ($poll['votes'] ?? array()));
	}

	/**
	 * 投票人名单（展示层用 nickname ?? username 回退）
	 * @return array 每项 {name, nickname, choices[]}
	 */
	public static function voters(array $poll) {
		$out = array();
		foreach ((array) ($poll['votes'] ?? array()) as $username => $choices) {
			$u = User::get((string) $username);
			$out[] = array(
				'username' => (string) $username,
				'nickname' => $u !== null ? (string) ($u['nickname'] ?? $u['username']) : (string) $username,
				'choices' => array_map('intval', (array) $choices),
			);
		}
		return $out;
	}
}
