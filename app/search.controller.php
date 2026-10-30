<?php

defined('FLOO') or die('FlooBB');

/**
 * 搜索控制器（SPEC §3.1：GET /search，全部走 query string，路径不含关键字）
 */
class SearchController {

	/**
	 * GET /search → 同 query（Router 缺省 action=index）
	 */
	public function index() {
		$this->query();
	}

	/**
	 * GET /search?q=&scope=&page=
	 */
	public function query() {
		global $conf;
		$q = param('q', '');
		$scope = param('scope', 'topics');
		if (!in_array($scope, array('topics', 'posts', 'users'), true)) $scope = 'topics';
		$page = max(1, param('page', 1));

		$result = Search::query($q, $scope);
		$items = $result['items'];
		$truncated = $result['truncated'];

		$per = 20;
		$total = count($items);
		$totalPage = max(1, (int) ceil($total / $per));
		$page = min($page, $totalPage);
		$items = array_slice($items, ($page - 1) * $per, $per);

		$header = array('title' => '搜索 - ' . ($conf['title'] ?? 'FlooBB'));
		require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'search.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
	}
}
