<?php defined('FLOO') or die('potato'); // NOOOO

/**
 * @var array 内存 KV 缓存（请求级）
 */
$FLATDB_MEMCACHE = array();
/**
 * @var array{c: array, r: array, u: array, d: array} 调试计数（仅 DEBUG_MODE 记录）
 */
$FLATDB_TOTAL_QUERY = array('c' => array(), 'r' => array(), 'u' => array(), 'd' => array());
/**
 * @var array{c: int, r: int, u: int, d: int} 调试计数（仅 DEBUG_MODE 记录）
 */
$FLATDB_TOTAL_QUERY_NUM = array('c' => 0, 'r' => 0, 'u' => 0, 'd' => 0);

/**
 * 数据库与文件操作类【核心】
 *
 * JSON flat-file 引擎（唯一数据层）。
 * - 内存 KV 缓存：$FLATDB_MEMCACHE（单次请求内，避免重复 file_get_contents + json_decode）
 * - 调试计数：$FLATDB_TOTAL_QUERY（c/r/u/d 路径数组）+ $FLATDB_TOTAL_QUERY_NUM（c/r/u/d 计数），
 *   仅 DEBUG_MODE 时记录
 * - 存储格式保持 JSON（.dat.json）
 */
class flatDB {
	/**
	 * Protected constructor since this is a static class.
	 *
	 * @access  protected
	 */
	protected function __construct() {
		// Nothing here
	}

	/**
	 * 记录一条调试计数
	 * @param string $op  c=create / r=read / u=update / d=delete
	 * @param string $path 目标文件路径
	 */
	public static function trace($op, $path) {
		global $FLATDB_TOTAL_QUERY, $FLATDB_TOTAL_QUERY_NUM;
		if (!defined('DEBUG_MODE') || !DEBUG_MODE) return;
		if (isset($FLATDB_TOTAL_QUERY[$op])) $FLATDB_TOTAL_QUERY[$op][] = $path;
		if (isset($FLATDB_TOTAL_QUERY_NUM[$op])) $FLATDB_TOTAL_QUERY_NUM[$op] += 1;
	}

	/**
	 * 缓存键
	 * @param string $type 数据库类型（如 forum）
	 * @param string $name 数据库名称（如 view）
	 * @param string $dbType 数据库文件类型（如 .dat.json）
	 * @return string 缓存键
		 */
	private static function cacheKey($type, $name, $dbType='.dat.json') {
		return $type . DS . $name . $dbType;
	}

	public static function getDB($type, $name, $dbType='.dat.json'){
		global $FLATDB_MEMCACHE;
		$e = DATA_DIR . $type . DS;
		$key = self::cacheKey($type, $name, $dbType);
		if (isset($FLATDB_MEMCACHE[$key])) {
			return $FLATDB_MEMCACHE[$key];
		}
		if (file_exists($e.$name.$dbType)) {
			self::trace('r', $e.$name.$dbType);
			$data = json_decode(file_get_contents($e.$name.$dbType), true);
			$FLATDB_MEMCACHE[$key] = $data;
			return $data;
		} else {
			self::makeDB($type, $name, $dbType);
			$FLATDB_MEMCACHE[$key] = array();
			return array();
		}
	}

	/**
	 * 创建数据库文件（如果不存在）
	 * @param string $type 数据库类型（如 forum）
	 * @param string $name 数据库名称（如 view）
	 * @param string $dbType 数据库文件类型（如 .dat.json）
	 */
		public static function makeDB($type, $name, $dbType='.dat.json'){
		global $FLATDB_MEMCACHE;
		$e = DATA_DIR . $type . DS;
		if(!is_dir($e)){
			mkdir($e, 0777, true);
		}
		if(!file_exists($e.$name.$dbType)){
			self::trace('c', $e.$name.$dbType);
			file_put_contents($e.$name.$dbType, '{}', LOCK_EX);
			$key = self::cacheKey($type, $name, $dbType);
			$FLATDB_MEMCACHE[$key] = array();
		}
	}

	/**
	 * 保存数据库文件
	 * @param string $type 数据库类型（如 forum）
	 * @param string $name 数据库名称（如 view）
	 * @param array $data 数据（JSON 格式）
	 * @param string $dbType 数据库文件类型（如 .dat.json）
	 * @return bool 是否成功
	 */
		public static function saveDB($type, $name, $data, $dbType='.dat.json'){
		global $FLATDB_MEMCACHE;
		$e = DATA_DIR . $type . DS;
		if(!is_dir($e)){
			mkdir($e, 0777, true);
		}
		$json = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
		self::trace(file_exists($e.$name.$dbType) ? 'u' : 'c', $e.$name.$dbType);
		$ok = file_put_contents($e.$name.$dbType, $json, LOCK_EX) !== false;
		if ($ok) {
			$FLATDB_MEMCACHE[self::cacheKey($type, $name, $dbType)] = $data;
		}
		return $ok;
	}

	/**
	 * 删除数据库文件
	 * @param string $type 数据库类型（如 forum）
	 * @param string $name 数据库名称（如 view）
	 * @param string $dbType 数据库文件类型（如 .dat.json）
	 * @return bool 是否成功
	 */
		public static function removeDB($type, $name, $dbType='.dat.json'){
		global $FLATDB_MEMCACHE;
		$e = DATA_DIR . $type . DS;
		if(file_exists($e.$name.$dbType)){
			$ok = unlink($e.$name.$dbType);
			if ($ok) {
				self::trace('d', $e.$name.$dbType);
				unset($FLATDB_MEMCACHE[self::cacheKey($type, $name, $dbType)]);
			}
			return $ok;
		}
		return false;
	}

	/**
	 * 检查数据库文件是否存在
	 * @param string $type 数据库类型（如 forum）
	 * @param string $name 数据库名称（如 view）
	 * @param string $dbType 数据库文件类型（如 .dat.json）
	 * @return bool 是否存在
	 */
		public static function DBexists($type, $name, $dbType='.dat.json'){
		$e = DATA_DIR . $type . DS;
		return file_exists($e.$name.$dbType) ? true : false;
	}

	/**
	 * 列出数据库文件
	 * @param string $type 数据库类型（如 forum）
	 * @return array 数据库文件列表（不包含 . 和 ..）
	 */
		public static function listDB($type) : array{
		$e = DATA_DIR . $type . DS;
		if (!is_dir($e)) return array();
		return array_values(array_diff(scandir($e), ['.','..']));
	}

	/**
	 * 创建新行所需的ID（文件名）
	 * @return string
	 */
	public static function newEntry() {
		return date('Y-m-dHis') . substr(uniqid(), -5);
	}

}
