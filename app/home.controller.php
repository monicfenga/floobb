<?php namespace Controller;

defined('FLOO') or die('potato'); // NOOOO

/**
 * 首页控制器：GET / → 版块列表
 */
class HomeController {

	public function index() {
		global $conf, $header;
		$forums = \Model\Forum::listAll();

		$header['title'] = $conf['title'];
		$header['mobile_title'] = $conf['title'];

		require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'homepage.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
	}
}
