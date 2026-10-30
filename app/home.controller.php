<?php

defined('FLOO') or die('FlooBB');

/**
 * 首页控制器：GET / → 版块列表
 */
class HomeController {

	public function index() {
		global $conf, $header;
		$forums = Forum::listAll();
		// 版块卡片附最后活跃时间（回帖活跃优先）
		$forumNames = array();
		foreach ($forums as &$f) {
			$f['last_active'] = Forum::lastActive((string) ($f['id'] ?? ''));
			$forumNames[(string) ($f['id'] ?? '')] = (string) ($f['name'] ?? '');
		}
		unset($f);

		// 全站最新主题（置顶在前，最后活跃降序）
		$latestTopics = array();
		foreach (Topic::listLatest(10) as $t) {
			$fid = (string) ($t['forum_id'] ?? '');
			$latestTopics[] = array(
				'topic' => $t,
				'forum_id' => $fid,
				'forum_name' => $forumNames[$fid] ?? '',
			);
		}

		$header['title'] = $conf['title'];
		$header['mobile_title'] = $conf['title'];

		require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'homepage.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
	}
}
