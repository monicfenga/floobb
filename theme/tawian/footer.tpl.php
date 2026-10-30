<?php defined('FLOO') or die('FlooBB'); ?>
    </div>
</main>
<footer class="site-footer dashed-top">
    <div class="container">
        <?= htmlspecialchars($conf['footer_text'] ? $conf['footer_text'] . ' · ' : '') ?>
        Powered by <a href="https://github.com/monicfenga/floobb" rel="noopener">FlooBB</a> (GPLv3+)
        <?php
        global $FLOO_START, $FLATDB_TOTAL_QUERY, $FLATDB_TOTAL_QUERY_NUM;
        $showDebug = defined('DEBUG_MODE') && DEBUG_MODE && Auth::level() >= 3;
        if ($showDebug):
        ?>
        <div class="small muted mt-1">
            <span>&#128434; C:<?= (int) ($FLATDB_TOTAL_QUERY_NUM['c'] ?? 0) ?> R:<?= (int) ($FLATDB_TOTAL_QUERY_NUM['r'] ?? 0) ?> U:<?= (int) ($FLATDB_TOTAL_QUERY_NUM['u'] ?? 0) ?> D:<?= (int) ($FLATDB_TOTAL_QUERY_NUM['d'] ?? 0) ?></span>
            <span class="ms-2">&#9201; <span id="usagedd-lt">0.000s</span></span>
            <span class="ms-2">&#127856; <?= round(memory_get_usage() / 1024 / 1024, 2) ?>M</span>
        </div>
        <details class="text-left container mt-2">
            <summary class="small muted">flatDB 查询明细（<?= count((array) ($FLATDB_TOTAL_QUERY ?? array())) ?> 类）</summary>
            <pre class="small"><?php print_r($FLATDB_TOTAL_QUERY ?? array()); ?></pre>
        </details>
        <?php endif; ?>
    </div>
</footer>
<script>
(function () {
    'use strict';
    var KEY = 'floobb-theme';
    function updateIcon() {
        var icon = document.getElementById('themeToggleIcon');
        if (icon) icon.innerHTML = document.documentElement.classList.contains('dark') ? '&#9728;' : '&#9790;';
    }
    updateIcon();
    var btn = document.getElementById('themeToggle');
    if (btn) {
        btn.addEventListener('click', function () {
            var next = document.documentElement.classList.contains('dark') ? '' : 'dark';
            if (next) document.documentElement.classList.add('dark'); else document.documentElement.classList.remove('dark');
            try { localStorage.setItem(KEY, next); } catch (e) {}
            updateIcon();
        });
    }
})();
</script>
<script>
function usagedd_update_ttfb() {
    var nav = window.performance.getEntriesByType ? window.performance.getEntriesByType('navigation')[0] : null;
    var loadTime = nav && nav.domContentLoadedEventEnd > 0 ? nav.domContentLoadedEventEnd : window.performance.now();
    var sec = Math.round(loadTime / 10) / 100;
    var el = document.getElementById('usagedd-lt');
    if (el) el.textContent = sec + 's';
}
document.addEventListener('DOMContentLoaded', usagedd_update_ttfb);
</script>
</body>
</html>
