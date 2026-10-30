<?php defined('FLOO') or die('FlooBB'); ?>
<nav class="breadcrumb">
    <li><a href="<?= Router::url() ?>">首页</a></li>
    <?php if ($forum !== null): ?>
    <li><a href="<?= Router::url('forum/view/' . $forum['id']) ?>"><?= htmlspecialchars($forum['name'] ?? '') ?></a></li>
    <?php endif; ?>
    <li class="active"><?= htmlspecialchars($topic['title'] ?? '') ?></li>
</nav>

<div class="flex between items-start wrap gap-2 mb-1">
    <h1 class="mb-0">
        <?php if (!empty($topic['sticky'])): ?><span class="tag tag-warning me-1">置顶</span><?php endif; ?>
        <?php if (!empty($topic['locked'])): ?><span class="tag tag-error me-1">已锁定</span><?php endif; ?>
        <?= htmlspecialchars($topic['title'] ?? '') ?>
    </h1>
    <ul class="list-group list-group-horizontal mb-2">
        <li class="active"><strong>帖子</strong></li>
        <li><a class="<?= $sort === 'oldest' ? 'active' : '' ?>" href="<?= Router::url('topic/view/' . $topic['id']) ?><?= $filter === 'author' ? '?filter=author' : '' ?>">正序</a></li>
        <li><a class="<?= $sort === 'latest' ? 'active' : '' ?>" href="<?= Router::url('topic/view/' . $topic['id']) ?>?sort=latest<?= $filter === 'author' ? '&filter=author' : '' ?>">倒序</a></li>
        <li><a class="<?= $filter === 'author' ? 'active' : '' ?>" href="<?= Router::url('topic/view/' . $topic['id']) ?>?filter=author<?= $sort === 'latest' ? '&sort=latest' : '' ?>">只看楼主</a></li>
    </ul>
</div>
<p class="muted small">
    楼主 <a href="<?= Router::url('user/view/' . rawurlencode((string) ($topic['author'] ?? ''))) ?>"><?= htmlspecialchars($topic['author'] ?? '') ?></a>
    · <?= date('Y-m-d H:i', $topic['created'] ?? 0) ?>
    · <?= count((array) ($topic['post_ids'] ?? array())) ?> 帖
</p>

<?php if (Auth::level() >= 2): ?>
<div class="btn-group mb-3" role="group" aria-label="管理操作">
    <form method="post" action="<?= Router::url('topic/lock/' . $topic['id']) ?>" class="inline">
        <?= Auth::csrfField() ?>
        <button type="submit" class="btn btn-ghost"><?= empty($topic['locked']) ? '锁定' : '解锁' ?></button>
    </form>
    <form method="post" action="<?= Router::url('topic/sticky/' . $topic['id']) ?>" class="inline">
        <?= Auth::csrfField() ?>
        <button type="submit" class="btn btn-ghost"><?= empty($topic['sticky']) ? '置顶' : '取消置顶' ?></button>
    </form>
    <form method="post" action="<?= Router::url('topic/move/' . $topic['id']) ?>" class="inline">
        <?= Auth::csrfField() ?>
        <select name="fid" aria-label="移动到版块" required>
            <?php foreach (($allForums ?? array()) as $fId => $fName): ?>
            <option value="<?= htmlspecialchars($fId) ?>" <?= $fId === (string) ($topic['forum_id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($fName) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ghost">移动</button>
    </form>
    <form method="post" action="<?= Router::url('topic/delete/' . $topic['id']) ?>" class="inline" onsubmit="return confirm('确定删除该主题及其全部帖子？');">
        <?= Auth::csrfField() ?>
        <button type="submit" class="btn btn-error btn-ghost">删除主题</button>
    </form>
</div>
<?php endif; ?>

<?php $report_type = 'topic'; $report_target = (string) $topic['id']; $report_label = '举报主题'; ?>
<?php require __DIR__ . DS . '_report_btn.tpl.php'; ?>

<?php require __DIR__ . DS . '_poll.tpl.php'; ?>

<?php foreach ($posts as $row): $p = $row['post']; ?>
<article class="form-group" id="p<?= $p['id'] ?>">
    <div class="listmessage form-control">
        <div class="flex between items-start wrap gap-2">
            <time>
                <a href="#p<?= $p['id'] ?>" class="muted">#<?= substr($p['id'], -5) ?></a>
                <?= date('Y-m-d H:i', $p['created'] ?? 0) ?>
                <?php if (!empty($p['edited'])): ?><span class="muted">· 编辑于 <?= date('m-d H:i', $p['edited']) ?></span><?php endif; ?>
            </time>
            <div class="flex gap-1 items-center wrap">
                <?php if ($row['can_edit']): ?>
                <a class="tag tag-info" href="<?= Router::url('post/edit/' . $p['id']) ?>">编辑</a>
                <?php endif; ?>
                <?php if ($row['can_delete']): ?>
                <form method="post" action="<?= Router::url('post/delete/' . $p['id']) ?>" class="inline" onsubmit="return confirm('确定删除该帖子？');">
                    <?= Auth::csrfField() ?>
                    <button type="submit" class="tag tag-error" style="border:0;cursor:pointer;">删除</button>
                </form>
                <?php endif; ?>
                <?php $report_type = 'post'; $report_target = (string) $p['id']; $report_label = '举报'; ?>
                <?php require __DIR__ . DS . '_report_btn.tpl.php'; ?>
            </div>
        </div>
        <div class="post-content">
            <?= $p['content_html'] ?? '' ?>
            <?php if (!empty($row['author_sig'])): ?><div class="sig dashed-top"><?= htmlspecialchars((string) $row['author_sig']) ?></div><?php endif; ?>
        </div>
    </div>
    <div class="listuser form-label">
        <a href="<?= Router::url('user/view/' . rawurlencode((string) ($p['author'] ?? ''))) ?>"><?= htmlspecialchars($row['author_nickname']) ?></a>
        <?php if (!empty($row['author_avatar'])): ?>
        <img src="<?= Router::url('uploads/avatars/' . rawurlencode((string) $row['author_avatar'])) ?>" alt="">
        <?php endif; ?>
    </div>
</article>
<?php endforeach; ?>

<?= $pageLinks ?>

<?php if (!empty($topic['locked'])): ?>
<div class="alert alert-error" role="status">主题已锁定，无法回帖。</div>
<?php elseif (Auth::isLoggedIn()): ?>
<h2 class="small mt-4">回复</h2>
<form method="post" action="<?= Router::url('post/create/' . $topic['id']) ?>">
    <?= Auth::csrfField() ?>
    <label class="form-group">
        <textarea class="form-control" id="reply-content" name="content" rows="4" required placeholder="Markdown 回帖…" style="min-width: calc(100% - 11rem);"></textarea>
        <span class="form-label">回帖内容（Markdown）</span>
    </label>
    <button type="submit" class="btn btn-primary">回帖</button>
</form>
<?php else: ?>
<div class="alert"><a href="<?= Router::url('auth/login') ?>">登录</a> 后回帖。</div>
<?php endif; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/google-code-prettify@1.0.5/bin/prettify.min.css">
<script src="https://cdn.jsdelivr.net/npm/google-code-prettify@1.0.5/bin/prettify.min.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () { if (window.PR) PR.prettyPrint(); });</script>
