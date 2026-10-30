<?php defined('FLOO') or die('FlooBB'); 

/**
 * 杂项实用函数【核心】
 */
class Util {
	/**
	 * @access  protected
	 */
	protected function __construct() {
		// Nothing here
	}

	/**
	 * 缩短数字
	 * 1 = 1；10 = 10；100 = 100；1000 = 1k；10000 = 10k
	 *
	 * @param int $int 要缩短的数字
	 * @return int|string 缩短后的数字
	 */
	public static function shortNum($int) {
		if ($int < 1000) {

			return intval($int);
		} else {
			return round(intval($int) / 1000, 1) . 'K';
		}
	}

	/**
	 * 时间戳或日期转换成人类可理解的方式
	 *
	 * @param mixed $id
	 * @param string $pattern
	 * @param bool $cooldate
	 */

	public static function toDate($id, $pattern = 'Y/m/d H:i', $cooldate = true) {
		global $lang, $conf;
		$timestamp = strtotime(substr($id, 0, 16));
		$diff = time() - $timestamp;
		if ($pattern === $conf['date_format'] && $cooldate && $diff < 604800) { //1 week
			$periods = array(86400 => $lang['day'], 3600 => $lang['hour'], 60 => $lang['minute'], 1 => $lang['second']);
			foreach ($periods as $key => $value) {
				if ($diff >= $key) {
					$num = (int) ($diff / $key);
					if (TIMESTAMP)
						$date = $num . ' ' . $value . ($num > 1 ? $lang['plural'] : '') . ' ' . $lang['ago'];
					else
						$date = $lang['ago'] . ' ' . $num . ' ' . $value . ($num > 1 ? $lang['plural'] : '');
				}
			}
		}
		$date = date($pattern, $timestamp);

		return $date;
	}

	/**
	 * 获取文件大小
	 * @param string $file Filepath
	 * @param int $digits Digits to display
	 * @return string Size (KB, MB, GB, TB) or boolean
	 */
	public static function getFilesize($file, $digits = 2) {
		$bytes = filesize($file);
		if ($bytes < 1024) return $bytes . ' B';
		elseif ($bytes < 1048576) return round($bytes / 1024, $digits) . ' KB';
		elseif ($bytes < 1073741824) return round($bytes / 1048576, $digits) . ' MB';
		elseif ($bytes < 1099511627776) return round($bytes / 1073741824, $digits) . ' GB';
		else return round($bytes / 1099511627776, $digits) . ' TB';
	}

	/**
	 * 文件大小转换成人类可读格式
	 *
	 * @param int $size
	 * @return string
	 */
	public static function sizeConversion($size) {
		$unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

		return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $unit[$i];
	}

	/**
	 * 获取内存使用量
	 * @param string $type 要获取的类型：available = 可用，peak = 峰值，current = 当前
	 * @return string
	 */
	public static function getMemory($type = 'usage') {
		if ((string) $type === 'available') {
			$memoryAvailable = filter_var(ini_get("memory_limit"), FILTER_SANITIZE_NUMBER_INT);
			$memoryAvailable = $memoryAvailable * 1024 * 1024;
			$size = (int) $memoryAvailable;
		} elseif ((string) $type === 'peak') {
			$size = (int) memory_get_peak_usage(true);
		} elseif ((string) $type === 'usage') {
			$size = (int) memory_get_usage(true);
		} else {
			$size = 0;
		}

		return Util::sizeConversion($size);
	}

	/**
	 * Méthode de journalisation
	 *
	 * @param	text	string
	 * @param	type	string
	 *  <code>
	 *      echo Util::journal($text);
	 *  </code>	
	 **/
	/**
	 * 日志记录
	 *
	 * @param mixed $text 内容
	 * @param int $type 类型
	 */
	public static function journal($text, $type = 0) {
		if (is_array($text)) {
			error_log('------------------------', $type);
			error_log('Array', $type);
			error_log('------------------------', $type);
			foreach ($text as $key => $value) {
				error_log($key . '=>' . $value, $type);
			}
			error_log('------------------------', $type);
		}
		error_log('(' . VERSION . ') (' . $_SERVER['REQUEST_URI'] . ') ' . $text, $type);
	}
}

/**
 * php防注入和XSS攻击通用过滤【核心扩展】
 */
class xssFilter extends Util {

	static function string_remove_xss($html) {
		preg_match_all("/\<([^\<]+)\>/is", $html, $ms);

		$searchs[] = '<';
		$replaces[] = '&lt;';
		$searchs[] = '>';
		$replaces[] = '&gt;';

		if ($ms[1]) {
			$allowtags = 'img|a|table|thead|tbody|tfoot|tr|td|th|br|p|b|strong|i|em|ol|ul|li|blockquote|h1|h2|h3|h4|h5|h6|details|summary';
			$ms[1] = array_unique($ms[1]);
			foreach ($ms[1] as $value) {
				$searchs[] = "&lt;" . $value . "&gt;";

				$value = str_replace('&amp;', '_uch_tmp_str_', $value);
				$value = self::string_htmlspecialchars($value);
				$value = str_replace('_uch_tmp_str_', '&amp;', $value);

				$value = str_replace(array('\\', '/*'), array('.', '/.'), $value);
				$skipkeys = array(
					'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate',
					'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange',
					'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick',
					'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate',
					'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete',
					'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel',
					'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart',
					'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop',
					'onsubmit', 'onunload', 'javascript', 'script', 'eval', 'behaviour', 'expression'
				);
				$skipstr = implode('|', $skipkeys);
				$value = preg_replace(array("/($skipstr)/i"), '.', $value);
				if (!preg_match("/^[\/|\s]?($allowtags)(\s+|$)/is", $value)) {
					$value = '';
				}
				$replaces[] = empty($value) ? '' : "<" . str_replace('&quot;', '"', $value) . ">";
			}
		}
		$html = str_replace($searchs, $replaces, $html);

		return $html;
	}

	static function string_htmlspecialchars($string, $flags = null) {
		if (is_array($string)) {
			foreach ($string as $key => $val) {
				$string[$key] = self::string_htmlspecialchars($val, $flags);
			}
		} else {
			if ($flags === null) {
				$string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
				if (strpos($string, '&amp;#') !== false) {
					$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
				}
			} else {
				if (!defined('CHARSET') || (strtolower(CHARSET) == 'utf-8')) {
					$charset = 'UTF-8';
				} else {
					$charset = 'ISO-8859-1';
				}
				$string = htmlspecialchars($string, $flags, $charset);
			}
		}

		return $string;
	}
}
