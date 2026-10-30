<?php defined('FLOO') or die('potato'); // NOOOO

/**
 * 上传/附件【吸纳自 WebPress/libs/files.lib.php（Files），按确认映射精简重写】
 *
 * 适配点（相对源）：
 * - 守卫 WEBPRESS → FLOO
 * - 去除 global $lang / ROOT conf 耦合：上传校验配置改由 floobb 常量 + $conf 传入
 * - upload() 不再 echo alert div：返回 bool，错误消息写入 $error（输出是控制器/模板的事）
 * - 不吸纳：uploadToFileManager / ManagerOpts / LockedItem / scanXML / minify /
 *   Perms / FullPerms / folderSize（源代码内有 bug）/ getFileData / catche / removeExtension
 *
 * 安全要求（SPEC §6）：限类型 + 重命名 + 非执行目录
 */
class Files{
	/**
	 * 纯静态类，禁止实例化
	 */
	protected function __construct(){

	}

	/**
	 * 上传文件
	 * @param string $name    $_FILES 中的字段名
	 * @param string $loc     目标目录（建议非执行目录，如 uploads/）
	 * @param string|null $rename 重命名（不含路径；null=保留原名）
	 * @param array|null $allowExt 允许的扩展名白名单（小写，无点）；null=用默认图片白名单
	 * @param int $maxSize    最大字节数；0=用默认 2MB
	 * @param string|null $error 输出错误消息（已 htmlspecialchars）
	 * @return bool
	 */
	public static function upload($name, $loc, $rename=null, $allowExt=null, $maxSize=0, &$error=null){
		$error = null;
		$allowExt = $allowExt ?: array('jpg','jpeg','png','gif','webp');
		$maxSize = $maxSize ?: 2 * 1024 * 1024; // 2MB

		if(!isset($_FILES[$name]) || !is_array($_FILES[$name]) || $_FILES[$name]['error'] !== UPLOAD_ERR_OK){
			$error = 'upload.failed.data';
			return false;
		}

		$target_dir = rtrim($loc, '/\\') . DS;
		if (!Files::checkFolder($target_dir)) {
			Files::createFolder($target_dir, 0755, true);
		}

		$original = basename($_FILES[$name]['name']);
		$target_file = $target_dir . $original;
		$file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

		if($_FILES[$name]['size'] > $maxSize){
			$error = 'upload.failed.large';
			return false;
		}
		if(!in_array($file_type, $allowExt, true)){
			$error = 'upload.failed.extentions';
			return false;
		}
		// 内容 MIME 校验（纵深防御：防止伪装扩展名的脚本落盘，配合上传目录禁执行）
		$mimeMap = array(
			'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
			'png' => 'image/png', 'gif' => 'image/gif',
			'webp' => 'image/webp',
		);
		if (class_exists('finfo')) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mime = $finfo ? $finfo->file($_FILES[$name]['tmp_name']) : false;
			if ($mime === false || !isset($mimeMap[$file_type]) || $mime !== $mimeMap[$file_type]) {
				$error = 'upload.failed.mime';
				return false;
			}
		}
		// 重命名目标同样校验扩展名
		if($rename !== null){
			$rename = basename($rename);
			if(strtolower(pathinfo($rename, PATHINFO_EXTENSION)) !== $file_type){
				$rename .= '.' . $file_type;
			}
			$target_file = $target_dir . $rename;
		}

		if(!is_uploaded_file($_FILES[$name]['tmp_name'])){
			$error = 'upload.failed';
			return false;
		}
		if(!@move_uploaded_file($_FILES[$name]['tmp_name'], $target_file)){
			$error = 'upload.failed';
			return false;
		}
		@chmod($target_file, 0644);
		return true;
	}

	/**
	 * 重命名/移动文件
	 * @param string $old 原路径
	 * @param string $new 新路径（可跨目录，即移动）
	 * @return bool 成功返回 true，失败被 @ 抑制返回 false
	 */
	public static function renameFile($old, $new){
		return @rename($old, $new);
	}

	/**
	 * 删除文件
	 * @param string $name 文件名（不含路径，自动 basename 防目录穿越）
	 * @param string $loc 所在目录
	 * @return bool 文件存在且删除成功返回 true；文件不存在返回 false
	 */
	public static function remove($name, $loc){
		$target = rtrim($loc, '/\\') . DS . basename($name);
		return file_exists($target) ? @unlink($target) : false;
	}

	/**
	 * 复制文件
	 * @param string $start 源路径
	 * @param string $end   目标路径
	 * @return bool
	 */
	public static function copyFile($start, $end){
		return @copy($start, $end);
	}

	/**
	 * 检查目录是否存在
	 * @param string $dir 目录路径
	 * @return bool
	 */
	public static function checkFolder($dir){
		return file_exists($dir) && is_dir($dir);
	}

	/**
	 * 创建目录（已存在时直接返回 true）
	 * @param string $dir       目录路径
	 * @param int    $mode      权限，默认 0755
	 * @param bool   $recursive 是否递归创建父目录
	 * @return bool
	 */
	public static function createFolder($dir, $mode=0755, $recursive=false){
		return !Files::checkFolder($dir) ? @mkdir($dir, (int)$mode, (bool)$recursive) : true;
	}

	/**
	 * 列出目录内容（不含 . / ..）
	 * @param string $path       目录路径
	 * @param bool   $removeDots 是否过滤 . / ..，默认 true
	 * @return array 文件/目录名列表（键已重排）；目录不存在返回空数组
	 */
	public static function Scan($path, $removeDots=true){
		if(!is_dir($path)) return array();
		$dirs = $removeDots===true ? array_diff(scandir($path), ['.','..']) : scandir($path);
		return array_values($dirs);
	}

	/**
	 * 递归删除目录及其全部内容【危险】
	 * @param string $dir 目录路径
	 * @return bool 最终 rmdir 是否成功；目录不存在返回 false
	 */
	public static function removeDir($dir){
		if(!is_dir($dir)) return false;
		foreach(glob($dir . '/*') as $file) {
	        if(is_dir($file))
	            self::removeDir($file);
	        else
	            @unlink($file);
	    }
	   return @rmdir($dir);
	}

	/**
	 * 字节数格式化为人类可读大小（B/KB/MB/GB/TB，向上取整）
	 * @param int $bytes 字节数
	 * @return string
	 */
	public static function sizeFormat($bytes){
		$kb = 1024;
		$mb = $kb * 1024;
		$gb = $mb * 1024;
		$tb = $gb * 1024;

		if (($bytes >= 0) && ($bytes < $kb)) {
			return $bytes . 'B';
		} elseif (($bytes >= $kb) && ($bytes < $mb)) {
			return ceil($bytes / $kb) . 'KB';
		} elseif (($bytes >= $mb) && ($bytes < $gb)) {
			return ceil($bytes / $mb) . 'MB';
		} elseif (($bytes >= $gb) && ($bytes < $tb)) {
			return ceil($bytes / $gb) . 'GB';
		} elseif ($bytes >= $tb) {
			return ceil($bytes / $tb) . 'TB';
		} else {
			return $bytes . 'B';
		}
	}
}
