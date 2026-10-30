<?php defined('FLOO') or die('FlooBB'); 

/*
 *---------------------------------------------------------------
 * 请求计时起点（Server-Timing / 页脚调试共用）
 *---------------------------------------------------------------
 */
$FLOO_START = microtime(true);

/*
 *---------------------------------------------------------------
 * 定义常量
 *---------------------------------------------------------------
 */
# 文件夹斜杠
const DS = DIRECTORY_SEPARATOR;
# 根位置
const BASEPATH = '.';
const PATH_ROOT = __DIR__;
# 版本
const VERSION = '1.3.0';
# 时间戳格式，必须为true
const TIMESTAMP = TRUE;
# 需要过滤的关键词
const CONTENT_FILTER = '';
# 日志分隔符
const LOG_SEP = ' | ';
# 登录允许的最大尝试次数
const MAX_LOGIN_ATTEMPT = 3;
# 启用IP黑名单（是否对 IP 地址进行阻止），必须为true
const BLOCK_IP = TRUE;
# 预计阻止时间（分钟）
const BLOCK_TIMEOUT = 120;
# 字符集默认值
const CHARSET = 'UTF-8';
# 不同的文件夹路径
const UPLOADS_DIR = PATH_ROOT . DS . 'uploads' . DS;
const DATA_DIR = PATH_ROOT . DS . 'data' . DS;
const DATA_PLUGIN = DATA_DIR . 'plugin' . DS;
const BAN_DIR = PATH_ROOT . DS . 'data' . DS . 'ban' . DS;
const BAN_FILE = BAN_DIR . DS . 'blocklist.txt';
const LANG_DIR = PATH_ROOT . DS . 'lang' . DS;
const LIB_DIR = PATH_ROOT . DS . 'lib' . DS;
const PLUGIN_DIR = PATH_ROOT . DS . 'plugin' . DS;
const THEME_DIR = BASEPATH . DS . 'theme' . DS;
/*
 * Web 基准路径（子文件夹部署友好）：SCRIPT_NAME=/floobb/index.php → APP_BASE=/floobb
 * 所有站内链接一律 Router::url('auth/login') 生成，禁止硬编码绝对根路径
 */
define('APP_BASE', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'));
# JSON 美化输出
if (!defined('JSON_PRETTY_PRINT')) define('JSON_PRETTY_PRINT', 128);

/**
 * @var bool DEBUG_MODE 调试模式
 */
const DEBUG_MODE = true;
if (DEBUG_MODE) {
    ini_set('error_log', PATH_ROOT . DS . 'error.log');
    if (DEBUG_MODE === true) {
        error_reporting(E_ALL);
        ini_set('display_errors', true);
        ini_set('display_startup_errors', true);
        ini_set('html_errors', 1);
        set_time_limit(10);
    } else if (DEBUG_MODE === false) {
        error_reporting(0);
        ini_set('display_errors', false);
        ini_set('display_startup_errors', false);
        set_time_limit(60);
    }
}
# Multibyte string extension loaded.
mb_internal_encoding(CHARSET);
mb_http_output(CHARSET);
/*
 *---------------------------------------------------------------
 * 载入必要库
 *---------------------------------------------------------------
 */
require_once LIB_DIR . 'Flatdb.lib.php';
require_once LIB_DIR . 'Router.lib.php';
require_once LIB_DIR . 'Utils.lib.php';
require_once LIB_DIR . 'CSRF.lib.php';
require_once LIB_DIR . 'Auth.lib.php';
require_once LIB_DIR . 'Parsedown.lib.php';
require_once LIB_DIR . 'ParsedownExtra.lib.php';
require_once LIB_DIR . 'Pagination.lib.php';
require_once LIB_DIR . 'Captcha.lib.php';
require_once LIB_DIR . 'files.lib.php';

/**
 *---------------------------------------------------------------
 * 自动加载（model/ 仓储层 + app/ 控制器，无构建步骤）
 *---------------------------------------------------------------
 * @param string $class 类名（如 Model\User）
 * @return void
 */
function floo_spl_autoload($class) {
	// 裸类名，全项目禁 namespace：ForumController → app/forum.controller.php；Forum → model/Forum.php
	if (substr($class, -10) === 'Controller') {
		$file = PATH_ROOT . DS . 'app' . DS . strtolower(substr($class, 0, -10)) . '.controller.php';
		is_file($file) and require_once $file;
	} else {
		$file = PATH_ROOT . DS . 'model' . DS . $class . '.php';
		is_file($file) and require_once $file;
	}
}

spl_autoload_register('floo_spl_autoload');

/*
 *---------------------------------------------------------------
 * Server-Timing API：随响应头输出耗时（所有响应含 302/403 均带）
 * 头发送前回调，输出尚未 flush，可安全 header()
 *---------------------------------------------------------------
 */
header_register_callback(function () {
    global $FLOO_START, $FLATDB_TOTAL_QUERY_NUM;
    $totalMs = round((microtime(true) - (float) $FLOO_START) * 1000, 1);
    $q = is_array($FLATDB_TOTAL_QUERY_NUM) ? $FLATDB_TOTAL_QUERY_NUM : array('c' => 0, 'r' => 0, 'u' => 0, 'd' => 0);
    $dbCount = (int) $q['c'] + (int) $q['r'] + (int) $q['u'] + (int) $q['d'];
    // db 计数用 desc 描述（flatDB 无逐操作计时，dur 不虚构）；total 才是真实墙钟
    header('Server-Timing: db;desc="' . $dbCount . ' ops (C:' . $q['c'] . ' R:' . $q['r'] . ' U:' . $q['u'] . ' D:' . $q['d'] . ')", total;dur=' . $totalMs);
});
/*
 *---------------------------------------------------------------
 * 椒盐
 *---------------------------------------------------------------
 */
if (file_exists(DATA_DIR . 'key.php')) include(DATA_DIR . 'key.php');

/*
 *---------------------------------------------------------------
 * 最后准备
 *---------------------------------------------------------------
 */
/*
 *---------------------------------------------------------------
 * 安装守卫（Phase 1）：config 不存在 → 跳转安装向导
 *---------------------------------------------------------------
 */
if (!flatDB::DBexists('config', 'config')) {
	// 从模板（重新）生成安装向导再跳转：install.php 属临时生成文件，装完自毁，
	// 重装 = 删 config 后访问任意页即可自动重建（Phase 0 原思路，新栈内容）
	if (!is_file(PATH_ROOT . DS . 'install.php')) {
		$tpl = @file_get_contents(LIB_DIR . 'install.file');
		if ($tpl !== false) @file_put_contents(PATH_ROOT . DS . 'install.php', $tpl);
	}
	header('Location: ' . APP_BASE . '/install.php');
	exit;
} else {
    $conf = flatDB::getDB('config', 'config');
	// 已安装则安装向导自毁，防重复安装覆盖数据
	if (is_file(PATH_ROOT . DS . 'install.php')) { @unlink(PATH_ROOT . DS . 'install.php'); }
}

// 独立 session 名：与旧版程序隔离，避免 session 里遗留的旧版 User/Forum 对象
// 反序列化到新版静态类上触发 dynamic property 弃用警告，也防止两站互相串登录态
if (session_name() !== 'floobb_session') {
	session_name('floobb_session');
}
if (!session_id()) session_start();
// token 只在缺失时生成：POST 校验的是上一页渲染的 token，
// 若每次请求都重新生成则 $_SESSION['token'] 永远对不上（CSRF::check 成功后 unset，下次请求再生成）
$token = $_SESSION['token'] ?? CSRF::generate();
Auth::resume();

/**
 * @var array $header 页面标题、副标题、关键词、描述
 */
$header = array(
	'title'=>$conf['title'],
	'subtitle'=>'',
	'mobile_title'=>'',
	'keywords'=>'', 
	'description'=>strip_tags($conf['description']),
);