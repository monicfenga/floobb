<?php defined('FLOO') or die('FlooBB'); ?>
<?php
global $FLOO_START, $FLATDB_TOTAL_QUERY, $FLATDB_TOTAL_QUERY_NUM;
/* 调试信息：仅 DEBUG_MODE 且管理员（level≥3）可见 */
$showDebug = DEBUG_MODE && Auth::level() >= 3;
?>
</main>
<footer class="border-top mt-5 py-4 text-center">
	<div><?= htmlspecialchars($conf['footer_text'] ? $conf['footer_text'] . ' &middot; ' : '') ?> 
		Powered by <a href="https://github.com/monicfenga/floobb" rel="noopener">FlooBB</a> (GPLv3+)
	</div>
	<?php if ($showDebug): ?>
	<div class="small text-body-secondary mt-1">
		<span>🛢️ C:<?= (int) $FLATDB_TOTAL_QUERY_NUM['c'] ?> R:<?= (int) $FLATDB_TOTAL_QUERY_NUM['r'] ?> U:<?= (int) $FLATDB_TOTAL_QUERY_NUM['u'] ?> D:<?= (int) $FLATDB_TOTAL_QUERY_NUM['d'] ?></span>
		<span class="ms-2">⏰ <?= round(microtime(true) - (float) $FLOO_START, 3) ?>s</span>
		<span class="ms-2">🍰 <?= round(memory_get_usage() / 1024 / 1024, 2) ?>M</span>
	</div>
	<details class="text-start container mt-2">
		<summary class="small text-body-secondary">flatDB 查询明细（<?= count((array) $FLATDB_TOTAL_QUERY) ?> 类）</summary>
		<pre class="small mb-0"><?php print_r($FLATDB_TOTAL_QUERY); ?></pre>
	</details>
	<?php endif; ?>
</footer>
<script src="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
