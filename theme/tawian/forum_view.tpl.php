<?php defined('FLOO') or die('FlooBB'); ?>
<nav class="breadcrumb">
    <li><a href="<?= Router::url() ?>">首页</a></li>
    <li class="active"><?= htmlspecialchars($forum['name'] ?? '') ?></li>
</nav>

<div class="flex between items-start mb-1">
    <h1 class="mb-0"><?= htmlspecialchars($forum['name'] ?? '') ?></h1>
    <?php if (Auth::isLoggedIn()): ?>
    <a class="btn btn-primary" href="<?= Router::url('topic/create/' . $forum['id']) ?>">新建主题</a>
    <?php else: ?>
    <a class="btn" href="<?= Router::url('auth/login') ?>">登录后发帖</a>
    <?php endif; ?>
</div>
<p class="muted">
    <?= htmlspecialchars($forum['description'] ?? '') ?>
    <span class="ms-2">主题 <?= (int) ($forum['topic_count'] ?? 0) ?> · 帖子 <?= (int) ($forum['post_count'] ?? 0) ?></span>
</p>

<ul class="list-group list-group-horizontal mb-2">
    <li class="active"><strong>排序</strong></li>
    <?php $fidEsc = $forum['id']; ?>
    <li><a class="<?= $sort === 'latest' ? 'active' : '' ?>" href="<?= Router::url('forum/view/' . $fidEsc) ?>?sort=latest">最新发帖</a></li>
    <li><a class="<?= $sort === 'replied' ? 'active' : '' ?>" href="<?= Router::url('forum/view/' . $fidEsc) ?>?sort=replied">最新回帖</a></li>
    <li><a class="<?= $sort === 'posts' ? 'active' : '' ?>" href="<?= Router::url('forum/view/' . $fidEsc) ?>?sort=posts">最多回帖</a></li>
    <li><a class="<?= $sort === 'views' ? 'active' : '' ?>" href="<?= Router::url('forum/view/' . $fidEsc) ?>?sort=views">最多查看</a></li>
</ul>

<?php if (!empty($stickyTopics)): ?>
<h2 class="small muted mt-3">置顶主题</h2>
<ul class="list-group mb-3">
    <?php foreach ($stickyTopics as $t): ?>
    <li class="flex between items-center wrap gap-2">
        <div>
            <span class="tag tag-warning me-1">置顶</span>
            <?php if (!empty($t['locked'])): ?><span class="tag tag-error me-1">已锁定</span><?php endif; ?>
            <a href="<?= Router::url('topic/view/' . $t['id']) ?>"><?= htmlspecialchars($t['title'] ?? '') ?></a>
            <small class="muted ms-2"><?= htmlspecialchars($t['author'] ?? '') ?> · <?= date('Y-m-d H:i', $t['created'] ?? 0) ?></small>
        </div>
        <?php require THEME_DIR . $conf['theme'] . DS . '_topic_actions.tpl.php'; ?>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<h2 class="small muted">全部主题</h2>
<?php if (empty($topics) && empty($stickyTopics)): ?>
<div class="alert" role="status">还没有主题，来发第一帖。</div>
<?php else: ?>
<ul class="list-group">
    <?php foreach ($topics as $t): ?>
    <li class="flex between items-center wrap gap-2">
        <div>
            <?php if (!empty($t['locked'])): ?><span class="tag tag-error me-1">已锁定</span><?php endif; ?>
            <a href="<?= Router::url('topic/view/' . $t['id']) ?>"><?= htmlspecialchars($t['title'] ?? '') ?></a>
            <small class="muted ms-2"><?= htmlspecialchars($t['author'] ?? '') ?> · <?= date('Y-m-d H:i', $t['created'] ?? 0) ?> · <?= count((array) ($t['post_ids'] ?? array())) ?> 帖 · <?= $t['views'] ?? 0 ?> 查看</small>
        </div>
        <?php require THEME_DIR . $conf['theme'] . DS . '_topic_actions.tpl.php'; ?>
    </li>
    <?php endforeach; ?>
</ul>
<?= $pageLinks ?>
<?php endif; ?>
