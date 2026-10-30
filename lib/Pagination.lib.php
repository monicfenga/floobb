<?php defined('FLOO') or die('FlooBB'); 

/**
 * 分页【吸纳自 WebPress/libs/Pagination.lib.php（Paginate）】
 *
 * 适配点（相对源）：
 * - 守卫 WEBPRESS → FLOO
 * - pid() 直读 $_GET['p'] → 经 param() 读 'page'（SPEC §3.1：page 走 query string）
 * - pageLink() 链接参数 p= → page=，$loc 需以 ?page= 或 &page= 形式拼好再传入
 * - Bootstrap 5 pagination markup（默认主题即 Bootstrap 5）
 */
class Paginate {
	protected function __construct() {
		// Nothing here
	}
	/**
	 * 生成分页链接
	 * 
	 * @param int $p 当前页码
	 * @param int $total 总页数
	 * @param string $loc 基础 URL（包含 ?page= 或 &page=）
	 * @return string 分页链接 HTML
	 */
	public static function pageLink($p, $total, $loc) {
		$start = ($p - 4) >= 1 ? $p - 4 : 1;
		$end = ($p + 4) <= $total ? $p + 4 : $total;
		$out = '
			<nav aria-label="pagination">
			  <ul class="pagination justify-content-center mt-3">' .
			($p > 1 ? '<li class="page-item"><a class="page-link" href="' . $loc . 'page=' . ($p - 1) . '" rel="prev">&larr;</a></li>' : '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">&larr;</a></li>') .
			($start === 1 ? '' : '<li class="page-item"><a class="page-link" href="' . $loc . 'page=' . ($start - 1) . '">…</a></li>');
		for ($i = $start; $i <= $end; $i++) {
			if ($p === $i)
				$out .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $i . '</span></li>';
			else
				$out .= '<li class="page-item"><a class="page-link" href="' . $loc . 'page=' . $i . '">' . $i . '</a></li>';
		}
		$out .= ($end === $total ? '' : '<li class="page-item"><a class="page-link" href="' . $loc . 'page=' . ($end + 1) . '">…</a></li>') .
			($p < $total ? '<li class="page-item"><a class="page-link" href="' . $loc . 'page=' . ($p + 1) . '" rel="next">&rarr;</a></li>' : '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">&rarr;</a></li>');
		$out .= '</ul>
          </nav>';
		# Return all links of Pagination
		return $out;
	}
	/**
	 * 计算总页数
	 * 
	 * @param array $items 数据项
	 * @param int $nb 每页显示项数
	 * @return int 总页数
	 */
	public static function countPage($items, $nb) {
		$itemNum = count($items);
		if ($itemNum === 0)
			return 1;
		else
			return (int) ceil($itemNum / $nb);
	}
	/**
	 * 获取当前页数据
	 * 
	 * @param array $items 数据项
	 * @param int $p 当前页码
	 * @param int $nb 每页显示项数
	 * @return array 当前页数据
	 */
	public static function viewPage($items, $p, $nb) {
		return array_slice($items, $nb * ($p - 1), $nb);
	}

	/**
	 * 读取当前页码（query string ?page=，经 param() 过滤）
	 * 
	 * @param int $total 总页数
	 * @return int 当前页码	
	 */
	public static function pid($total) {
		$p = param('page', 1);
		if ($p < 1)
			return 1;
		return $p;
	}
}
