<?php defined('FLOO') or die('FlooBB'); ?>
<!doctype html>
<html lang="<?= htmlspecialchars($conf['lang'] ?? 'zh-CN') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($header['title'] ?? $conf['title'] ?? 'FlooBB') ?></title>
<meta name="description" content="<?= htmlspecialchars($header['description'] ?? '') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/css/iosevka@latest">
<link rel="stylesheet" href="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/css/tawian-frontend.css') ?>">
<link rel="stylesheet" href="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/css/modal.css') ?>">
<script>
/* 尽早应用暗色偏好，避免闪白 */
(function () {
    try {
        var saved = localStorage.getItem('floobb-theme');
        if (saved === 'dark' || (!saved && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    } catch (e) {}
})();
</script>
</head>
<body>
<a class="skip-link" href="#main-content">跳转到主内容</a>
<header class="site-header dashed-bottom">
    <div class="container">
        <a class="site-title" href="<?= Router::url() ?>"><?= htmlspecialchars($conf['title'] ?? 'FlooBB') ?></a>
        <nav class="responsive-nav" aria-label="移动端导航开关">
            <button type="button" id="navigation-toggle-btn" aria-controls="site-nav-list" aria-expanded="false" aria-label="展开导航菜单"><span aria-hidden="true">&#9776;</span></button>
        </nav>
        <input type="checkbox" id="navigation-toggle" tabindex="-1" aria-hidden="true">
        <nav class="site-nav nav-separated" id="site-nav-list" aria-label="主导航">
            <button type="button" class="btn btn-link" data-target="search-modal" aria-label="搜索">搜索</button>
            <ul>
                <li><a href="<?= Router::url() ?>">首页</a></li>
                <?php if (Auth::isLoggedIn()): $navUser = Auth::user(); ?>
                <li><a href="<?= Router::url('user/view/' . urlencode((string) $navUser['username'])) ?>"><?= htmlspecialchars((string) ($navUser['nickname'] ?? $navUser['username'])) ?></a></li>
                <li><a href="<?= Router::url('user/settings') ?>">设置</a></li>
                <li>
                    <a href="<?= Router::url('pm/inbox') ?>">私信<?php $pmUnread = PM::unreadCount((string) $navUser['username']); ?><?php if ($pmUnread > 0): ?><span class="tag tag-error ms-1"><?= $pmUnread ?></span><?php endif; ?></a>
                </li>
                <?php if ($navUser['level'] >= 2): ?>
                <li><a href="<?= Router::url('report/list') ?>">举报队列</a></li>
                <?php endif; ?>
                <li>
                    <form method="post" action="<?= Router::url('auth/logout') ?>" class="inline">
                        <?= Auth::csrfField() ?>
                        <button type="submit" class="btn btn-link">登出</button>
                    </form>
                </li>
                <?php else: ?>
                <li><a href="<?= Router::url('auth/login') ?>">登录</a></li>
                <li><a href="<?= Router::url('auth/register') ?>">注册</a></li>
                <?php endif; ?>
                <li>
                    <button type="button" class="btn btn-link" id="themeToggle" aria-label="深色模式" aria-pressed="false">
                        <span id="themeToggleIcon" aria-hidden="true">&#9790;</span>
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</header>
<main class="site-main" id="main-content" tabindex="-1">
    <div class="container">
