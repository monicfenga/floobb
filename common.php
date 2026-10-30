<?php
	/* This file provides includes and sets up sessions, certain variables,
	remember me functionality and logins. 
	This file is included from many different pages. */

/* 	
	include_once("class.User.php");
	include_once("class.Topic.php");
	include_once("class.Post.php");
	include_once("class.Forum.php");
	include_once("class.PM.php");
	include_once("function.OnlineList.php");
	include_once("outHtml.php");
	
	if (isset($_COOKIE['username']) && isset($_COOKIE['password']) && ($_SESSION['loggedIn'] == false || !isset($_SESSION['loggedIn'])))
	{
		$fileC = file("db/forumUsers.dat");
		foreach ($fileC as $item)
		{
			$temp = new User($item);
			if ($_COOKIE['username'] == $temp->getUserId() && $_COOKIE['password'] == $temp->getPassword())
			{
				setcookie("username",$_COOKIE['username'],time()+(60*60*24*14));
				setcookie("password",$_COOKIE['password'],time()+(60*60*24*14));
				$_SESSION['loggedIn'] = true;
				$_SESSION['user'] = $temp;
			}
		}
	}

	function isUserLoggedIn() {
		return isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] == true;
	}
	
	if (isUserLoggedIn()) {
		addUser($_SESSION['user']->getUserId());
	}
	else {
		addGuest($_SERVER['SERVER_ADDR']);
	}
	
	$boardname = trim(file_get_contents("db/boardname.dat"));
	$boarddescription = trim(file_get_contents("db/boarddescription.dat"));
	define('BOARDNAME',$boardname);
	define('BOARDDESCRIPTION',$boarddescription);
	 */

	$_REQUEST = array_merge($_COOKIE, $_POST, $_GET, xn_url_parse($_SERVER['REQUEST_URI']));

function array_value($arr, $key, $default = '') {
	return isset($arr[$key]) ? $arr[$key] : $default;
}

/**
 * URL format: http://www.domain.com/demo/?user-login.htm?a=b&c=d
 * URL format: http://www.domain.com/demo/?user-login.htm&a=b&c=d
 * URL format: http://www.domain.com/demo/user-login.htm?a=b&c=d
 * URL format: http://www.domain.com/demo/user-login.htm&a=b&c=d
 * array(
 *     0 => user,
 *     1 => login
 *     a => b
 *     c => d
 * )
 */
function xn_url_parse($request_url) {
	// 处理: /demo/?user-login.htm?a=b&c=d
	// 结果：/demo/user-login.htm?a=b&c=d
	$request_url = str_replace('/?', '/', $request_url);
	$arr = parse_url($request_url);

	$q = array_value($arr, 'path');
	$pos = strrpos($q, '/');
	$pos === FALSE && $pos = -1;
	$q = substr($q, $pos + 1); // 截取最后一个 / 后面的内容
	// 查找第一个 ? & 进行分割
	$sep = strpos($q, '?') === FALSE ? strpos($q, '&') : FALSE;
	if ($sep !== FALSE) {
		// 对后半部分截取，并且分析
		$front = substr($q, 0, $sep);
		$behind = substr($q, $sep + 1);
	} else {
		$front = $q;
		$behind = '';
	}

	if (substr($front, -4) == '.htm') $front = substr($front, 0, -4);
	$r = $front ? (array)explode('-', $front) : array();

	// 将后半部分合并
	$arr1 = $arr2 = $arr3 = array();
	$behind and parse_str($behind, $arr1);

	// 将 xxx.htm?a=b&c=d 放到后面，并且修正 $_GET
	if (!empty($arr['query'])) {
		parse_str($arr['query'], $arr2);
	} else {
		!empty($_GET) and $_GET = array();
	}
	$arr3 = $arr1 + $arr2;
	if ($arr3) {
		//array_diff_key($arr3, $_GET) || array_diff_key($_GET, $arr3);
		count($arr3) != count($_GET) and $_GET = $arr3;
	} else {
		!empty($_GET) and $_GET = array();
	}
	$r += $arr3;

	$_SERVER['REQUEST_URI_NO_PATH'] = substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '/') + 1);

	// 是否开启 /user/login 这种格式的 URL
	if (false/* !empty($conf['url_rewrite_on']) && $conf['url_rewrite_on'] == 3 */) {
		$r = xn_url_parse_path_format($_SERVER['REQUEST_URI']) + $r;
	}

	isset($r[0]) and $r[0] == 'index.php' and $r[0] = 'index';
	return $r;
}

function xn_url_parse_path_format($s) {
	$get = array();
	substr($s, 0, 1) == '/' and $s = substr($s, 1);
	$arr = explode('/', $s);
	$get = $arr;
	$last = array_pop($arr);
	if (strpos($last, '?') !== FALSE) {
		$get = $arr;
		$arr1 = explode('?', $last);
		parse_str($arr1[1], $arr2);
		$get[] = $arr1[0];
		$get = array_merge($get, $arr2);
	}
	return $get;
}

function param($key, $defval = '', $htmlspecialchars = TRUE, $addslashes = FALSE) {
	if (!isset($_REQUEST[$key]) || ($key === 0 && empty($_REQUEST[$key]))) {
		if (is_array($defval)) {
			return array();
		} else {
			return $defval;
		}
	}
	$val = $_REQUEST[$key];
	$val = param_force($val, $defval, $htmlspecialchars, $addslashes);
	return $val;
}

function param_force($val, $defval, $htmlspecialchars = TRUE, $addslashes = FALSE) {
	$get_magic_quotes_gpc = false;
	if (is_array($defval)) {
		$defval = empty($defval) ? '' : $defval[0]; // 数组的第一个元素，如果没有则为空字符串
		if (is_array($val)) {
			foreach ($val as &$v) {
				if (is_array($v)) {
					$v = $defval;
				} else {
					if (is_string($defval)) {
						//$v = trim($v);
						$addslashes and !$get_magic_quotes_gpc && $v = addslashes($v);
						!$addslashes and $get_magic_quotes_gpc && $v = stripslashes($v);
						$htmlspecialchars and $v = htmlspecialchars($v);
					} else {
						$v = intval($v);
					}
				}
			}
		} else {
			return array();
		}
	} else {
		if (is_array($val)) {
			$val = $defval;
		} else {
			if (is_string($defval)) {
				//$val = trim($val);
				$addslashes and !$get_magic_quotes_gpc && $val = addslashes($val);
				!$addslashes and $get_magic_quotes_gpc && $val = stripslashes($val);
				$htmlspecialchars and $val = htmlspecialchars($val);
			} else {
				$val = intval($val);
			}
		}
	}
	return $val;
}
