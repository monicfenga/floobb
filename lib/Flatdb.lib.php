<?php defined('FLOO') or die('potato'); // NOOOO

/**
 * 数据库与文件操作类【核心】
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

	public static function getDB($type, $name, $dbType='.dat.json'){
		$e = DATA_DIR . $type . DS;
		if (file_exists($e.$name.$dbType)) {
			return json_decode(file_get_contents($e.$name.$dbType), true);
		} else {
			self::makeDB($type, $name, $dbType);
			return array();
		}
		
		//return json_decode((file_exists($e.$name.$dbType) ? file_get_contents($e.$name.$dbType) : ''), true);
	}

	public static function makeDB($type, $name, $dbType='.dat.json'){
		$e = DATA_DIR . $type . DS;
	 if(!file_exists($e.$name.$dbType)){
				$createDB = fopen($e.$name.$dbType, 'w+');
			fwrite($createDB, '{}');
			fclose($createDB); 
		}
	}

	public static function saveDB($type, $name, $data, $dbType='.dat.json'){
		$e = DATA_DIR . $type . DS;
		if(file_exists($e.$name.$dbType)){
			$rewriteDB = fopen($e.$name.$dbType, 'w+');
			$data = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
			fwrite($rewriteDB, $data);
			fclose($rewriteDB);
			return true;
		}else{
			return false;
		}
	}

	public static function removeDB($type, $name, $dbType='.dat.json'){
		$e = DATA_DIR . $type . DS;
		if(file_exists($e.$name.$dbType)){
			return unlink($e.$name.$dbType) ? true : false;
		}
	}

	public static function DBexists($type, $name, $dbType='.dat.json'){
		$e = DATA_DIR . $type . DS;
		return file_exists($e.$name.$dbType) ? true : false;
	}

	public static function listDB($type) : array{
		$e = DATA_DIR . $type . DS;
		return array_diff(scandir($e), ['.','..']);
	}
	
	/**
	 * 创建新行所需的ID（文件名）
	 * @return string
	 */
	public static function newEntry() {
		return date('Y-m-dHis') . substr(uniqid(), -5);
	}

}
