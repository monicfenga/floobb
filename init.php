<?php defined('FLOO') or die('potato'); // NOOOO

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
	if (strpos($class, 'Model\\') === 0) {
		$file = PATH_ROOT . DS . 'model' . DS . substr($class, 6) . '.php';
		is_file($file) and require_once $file;
	} elseif (strpos($class, 'Controller\\') === 0) {
		// Controller\HomeController → app/home.controller.php
		$name = substr($class, 11, -10); // 去前缀 Model\ / 后缀 Controller
		$file = PATH_ROOT . DS . 'app' . DS . strtolower($name) . '.controller.php';
		is_file($file) and require_once $file;
	}
}

spl_autoload_register('floo_spl_autoload');
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
if (!flatDB::DBexists('config', 'config')) {
	# Make & Load Install File
    
	$install = fopen('install.php', 'w');
	fwrite($install, file_get_contents(LIB_DIR . 'install.file'));
	fclose($install);
    header('Location: ' . BASEPATH . DS . 'install.php');
    
} else {
    $conf = flatDB::getDB('config', 'config');
}

if (is_file('install.php')){unlink('install.php');}

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