<?php
# Security constant
const FLOO = TRUE;

if (file_exists("install.php")) {
	header("Location: install.php");
}
require_once("init.php");

/*
 * front-controller 分发（SPEC §3.1 三位置契约）：
 *   /            → HomeController::index
 *   /forum/view/1 → Controller\ForumController::view('1')
 * 回退：index.php?r=forum/view/1
 */
$seg = Router::segments();
$controller = isset($seg[0]) ? strtolower((string) $seg[0]) : 'home';
if (!preg_match('/^[a-z][a-z0-9_]*$/', $controller)) $controller = 'home';

if (!Router::dispatch($controller)) {
	http_response_code(404);
	header('Content-Type: text/html; charset=utf-8');
	echo '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><title>404</title>';
	echo '<body class="container py-5"><h1>404</h1><p>未知路由：<code>' . htmlspecialchars($controller) . '</code></p>';
	echo '<p><a href="/">返回首页</a></p></body></html>';
}
