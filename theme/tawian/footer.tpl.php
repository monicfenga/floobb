<?php defined('FLOO') or die('FlooBB'); ?>
    </div>
</main>
<!-- 全局搜索弹窗 -->
<dialog id="search-modal" aria-label="搜索">
    <article>
        <header>
            <h5>搜索</h5>
            <button type="button" class="close" aria-label="关闭" data-target="search-modal">✕</button>
        </header>
        <main>
            <form id="search-modal-form" method="get" action="<?= Router::url('search') ?>" role="search">
                <label class="visually-hidden" for="search-modal-q">关键词</label>
                <input class="form-control full-width mb-2" type="search" id="search-modal-q" name="q" placeholder="输入关键词…" maxlength="100" autofocus>
                <label class="visually-hidden" for="search-modal-scope">搜索范围</label>
                <select class="form-control full-width" id="search-modal-scope" name="scope">
                    <option value="topics">主题标题</option>
                    <option value="posts">帖子内容</option>
                    <option value="users">用户名 / 昵称</option>
                </select>
            </form>
        </main>
        <footer>
            <button type="button" class="btn btn-link" data-target="search-modal">取消</button>
            <button type="submit" class="btn btn-primary btn-sm" form="search-modal-form">搜索</button>
        </footer>
    </article>
</dialog>
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
<script src="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/js/modal.js') ?>"></script>
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
