<?php namespace Controller;

defined('FLOO') or die('potato'); // NOOOO

/**
 * 版块控制器骨架（Phase 1 实现业务：add/view/order，SPEC §3.1 路由表）
 */
class ForumController {

	public function index() {
		self::notImplemented('forum/index：版块列表（首页已承担），Phase 1 实现');
	}

	public function add() {
		self::notImplemented('forum/add：新建版块（GET 表单 / POST 提交），Phase 1 实现');
	}

	public function addPost() {
		self::notImplemented('forum/add POST：提交新建版块，Phase 1 实现');
	}

	public function view($fid = '') {
		self::notImplemented('forum/view：版块页，Phase 1 实现');
	}

	public function orderPost($fid = '') {
		self::notImplemented('forum/order POST：版块排序，Phase 1 实现');
	}

	private static function notImplemented($msg) {
		http_response_code(501);
		echo '<!doctype html><meta charset="utf-8"><title>501</title>';
		echo '<p>Phase 0 骨架占位：' . htmlspecialchars($msg) . '</p>';
		echo '<p><a href="<?= Router::url() ?>">返回首页</a></p>';
	}
}
