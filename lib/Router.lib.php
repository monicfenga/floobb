<?php defined('FLOO') or die('FlooBB'); 

/**
 * 路由层【核心】——原 common.php 的 param()/xn_url_parse 迁入于此。
 *
 * SPEC §3.1 路由契约：
 * - 传输层：单入口 index.php；路径来源优先级 ① $_SERVER['PATH_INFO'] ② $_GET['r'] ③ 空（首页）
 * - 解析层：按 `/` 切成有序段数组，三位置契约 [控制器, 动作(缺省 index), ...参数]
 * - page/sort/filter 等非结构性修饰符一律走 query string，经 param() 读取，不进路径
 * - HTTP 方法感知：$_SERVER['REQUEST_METHOD']（GET/POST）参与分发
 * 
 * @param array $arr 路径段数组\
 * @param int $key 路径段键值\
 * @param string $default 默认值\
 * @return string 路径段值
 */

function array_value($arr, $key, $default = '') {
	return isset($arr[$key]) ? $arr[$key] : $default;
}

/**
 * 取请求参数（RAW，不做 HTML 转义）。
 * 返回值类型由 $defval 决定，调用方【禁止】再用 (string)/(int)/(float)/(bool) 强转：
 *   param('x', '')      → string
 *   param('x', 0)       → int
 *   param('x', 0.0)     → float
 *   param('x', false)   → bool
 *   param('x', [])      → array
 * HTML 转义只在【输出层】做（模板里用 htmlspecialchars），控制器逻辑一律用原始值。
 *
 * @param string|int|float|bool|array $defval 默认值；其类型决定返回值类型
 * @return string|int|float|bool|array
 */
function param($key, $defval = '') {
	$raw = $_REQUEST[$key] ?? null;
	if ($raw === null) {
		return $defval;
	}
	if (is_array($defval)) {
		return is_array($raw) ? $raw : array();
	}
	if (is_int($defval)) {
		return is_array($raw) ? (int) ($raw[0] ?? 0) : (int) $raw;
	}
	if (is_float($defval)) {
		return is_array($raw) ? (float) ($raw[0] ?? 0) : (float) $raw;
	}
	if (is_bool($defval)) {
		return is_array($raw) ? false : filter_var($raw, FILTER_VALIDATE_BOOLEAN);
	}
	// 默认 string
	return is_array($raw) ? '' : (string) $raw;
}

/**
 * 旧式 Xiuno 风格 URL 解析（保留供兼容，路由主路径不再依赖）
 * 
 * @param string $request_url 请求 URL
 * @return array 解析后的参数数组
 */
function xn_url_parse($request_url) {
	$request_url = str_replace('/?', '/', $request_url);
	$arr = parse_url($request_url);

	$q = array_value($arr, 'path');
	$pos = strrpos($q, '/');
	$pos === FALSE && $pos = -1;
	$q = substr($q, $pos + 1);
	$sep = strpos($q, '?') === FALSE ? strpos($q, '&') : FALSE;
	if ($sep !== FALSE) {
		$front = substr($q, 0, $sep);
		$behind = substr($q, $sep + 1);
	} else {
		$front = $q;
		$behind = '';
	}

	if (substr($front, -4) == '.htm') $front = substr($front, 0, -4);
	$r = $front ? (array)explode('-', $front) : array();

	$arr1 = $arr2 = $arr3 = array();
	$behind and parse_str($behind, $arr1);

	if (!empty($arr['query'])) {
		parse_str($arr['query'], $arr2);
	} else {
		!empty($_GET) and $_GET = array();
	}
	$arr3 = $arr1 + $arr2;
	if ($arr3) {
		count($arr3) != count($_GET) and $_GET = $arr3;
	} else {
		!empty($_GET) and $_GET = array();
	}
	$r += $arr3;

	isset($r[0]) and $r[0] == 'index.php' and $r[0] = 'index';
	return $r;
}

/**
 * 路由层【核心】——原 common.php 的 param()/xn_url_parse 迁入于此。
 */
class Router {

	/** @var array|null 有序段数组缓存 */
	private static $segments = NULL;

	/**
	 * 解析路径：PATH_INFO → ?r= → 空（首页）
	 * @return array 有序段数组，如 ['forum','view','123']
	 */
	public static function segments() {
		if (self::$segments !== NULL) return self::$segments;

		$path = '';
		if (!empty($_SERVER['PATH_INFO'])) {
			$path = $_SERVER['PATH_INFO'];
		} elseif (isset($_GET['r'])) {
			$path = (string) $_GET['r'];
		}

		$seg = array();
		foreach (explode('/', trim($path, '/')) as $s) {
			$s = trim($s);
			if ($s !== '') $seg[] = $s;
		}
		self::$segments = $seg;

		// $_REQUEST = COOKIE + POST + GET，再并入路径段（param(0)/param(1)/param(2) 照常可用）
		$_REQUEST = array_merge($_COOKIE, $_POST, $_GET);
		foreach ($seg as $i => $s) {
			if (!isset($_REQUEST[$i])) $_REQUEST[$i] = $s;
		}
		return $seg;
	}

	/**
	 * 当前请求方法（GET/POST/...），HTTP 方法感知分发用
	 */
	public static function method() {
		return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
	}

	/**
	 * 生成站内 URL（子文件夹部署友好）：url('auth/login') → /floobb/auth/login
	 */
	public static function url($path = '') {
		return APP_BASE . '/' . ltrim((string) $path, '/');
	}

	/**
	 * query string 修饰符读取（page/sort/filter/tab/order 等，绝不进路径）
	 * @param string $key 查询参数键值
	 * @param mixed $defval 默认值
	 * @return mixed 查询参数值
	 */
	public static function query($key, $defval = '') {
		return param($key, $defval);
	}

	/**
	 * 三位置契约分发：
	 *   ['forum']            → Controller\ForumController::index()
	 *   ['forum','view','123'] → Controller\ForumController::view('123')
	 *
	 * @param string $controllerName 首段（控制器名）
	 * @return bool 是否成功分发
	 */
	public static function dispatch($controllerName) {
		$seg = self::segments();
		$action = isset($seg[1]) ? $seg[1] : 'index';
		$args = array_slice($seg, 2);

		// action 名只允许字母数字下划线，防路径注入
		if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $action)) $action = 'index';

		$class = ucfirst(strtolower($controllerName)) . 'Controller';
		$method = self::method();

		if (!class_exists($class)) return false;
		$obj = new $class();

		// HTTP 方法感知：优先 actionMethod()（如 postAdd()），回退 action()
		if ($method === 'POST' && method_exists($obj, $action . 'Post')) {
			call_user_func_array(array($obj, $action . 'Post'), $args);
			return true;
		}
		if (method_exists($obj, $action)) {
			call_user_func_array(array($obj, $action), $args);
			return true;
		}
		return false;
	}
}
