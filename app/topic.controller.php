<?php namespace Controller;

defined('FLOO') or die('potato'); // NOOOO

/**
 * 主题控制器骨架（Phase 1 实现业务：view/lock，SPEC §3.1 路由表）
 */
class TopicController {

	public function index() {
		self::notImplemented('topic/index：Phase 1 实现');
	}

	public function view($tid = '') {
		self::notImplemented('topic/view：主题详情（帖子流），Phase 1 实现');
	}

	public function lockPost($tid = '') {
		self::notImplemented('topic/lock POST：锁定主题，Phase 1 实现');
	}

	private static function notImplemented($msg) {
		http_response_code(501);
		echo '<!doctype html><meta charset="utf-8"><title>501</title>';
		echo '<p>Phase 0 骨架占位：' . htmlspecialchars($msg) . '</p>';
		echo '<p><a href="<?= Router::url() ?>">返回首页</a></p>';
	}
}
