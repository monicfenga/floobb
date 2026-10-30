<?php

defined('FLOO') or die('potato'); // NOOOO

/**
 * 首页控制器：GET / → 版块列表
 */
class HomeController {

	public function index() {
		global $conf, $header;
		$forums = Forum::listAll();
		// 版块卡片附最后活跃时间（该版块最新主题的 created）
		foreach ($forums as &$f) {
			$f['last_active'] = Forum::lastActive((string) ($f['id'] ?? ''));
		}
		unset($f);

		$header['title'] = $conf['title'];
		$header['mobile_title'] = $conf['title'];

		require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'homepage.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
	}
}
