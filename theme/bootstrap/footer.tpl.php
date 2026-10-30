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
		<span class="ms-2">⏰ <?= round(microtime(true) - (float) $FLOO_START, 3) ?>s丨<span id="usagedd-lt">0.000s</span></span>
		<span class="ms-2">🍰 <?= round(memory_get_usage() / 1024 / 1024, 2) ?>M</span>
	</div>
	<details class="text-start container mt-2">
		<summary class="small text-body-secondary">flatDB 查询明细（<?= count((array) $FLATDB_TOTAL_QUERY) ?> 类）</summary>
		<pre class="small mb-0"><?php print_r($FLATDB_TOTAL_QUERY); ?></pre>
	</details>
	<?php endif; ?>
</footer>
<script src="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/js/bootstrap.bundle.min.js') ?>"></script>
<script id="theme_js">
(function () {
	'use strict';
	var KEY = 'floobb-theme';
	// 尽早应用已存储的主题，减少闪白
	var saved = null;
	try { saved = localStorage.getItem(KEY); } catch (e) {}
	var theme = saved === 'dark' || saved === 'light'
		? saved
		: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
	document.documentElement.setAttribute('data-bs-theme', theme);

	function updateIcon() {
		var icon = document.getElementById('themeToggleIcon');
		if (icon) icon.textContent = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '☀️' : '🌙';
	}
	updateIcon();

	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.getElementById('themeToggle');
		if (!btn) return;
		btn.addEventListener('click', function () {
			var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
			document.documentElement.setAttribute('data-bs-theme', next);
			try { localStorage.setItem(KEY, next); } catch (e) {}
			updateIcon();
		});
	});
})();
</script>
<script id="usagedd_js">
    function usagedd_update_ttfb() {
        // performance.timing 已废弃（Chrome 取值异常）→ 用 Navigation Timing L2，退回 performance.now()
        var nav = window.performance.getEntriesByType ? window.performance.getEntriesByType('navigation')[0] : null;
        var loadTime = nav && nav.domContentLoadedEventEnd > 0 ? nav.domContentLoadedEventEnd : window.performance.now();
        var sec = Math.round(loadTime / 10) / 100;
        var el = document.getElementById("usagedd-lt");
        if (el !== null) {
            el.innerHTML = sec + "s";
        }
        console.info("LoadTime: " + sec);
    }
	document.addEventListener("DOMContentLoaded", function() {
        usagedd_update_ttfb();
    })
</script>
</body>
</html>
