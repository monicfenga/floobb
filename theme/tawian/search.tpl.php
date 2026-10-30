<?php defined('FLOO') or die('FlooBB');
/** @var string $q @var string $scope @var array $items @var bool $truncated @var int $page $totalPage */
$scopes = array('topics' => '主题', 'posts' => '帖子', 'users' => '用户');
?>
<nav class="breadcrumb">
    <li><a href="<?= Router::url() ?>">首页</a></li>
    <li class="active">搜索</li>
</nav>

<h1 class="mb-3">搜索</h1>

<form method="get" action="<?= Router::url('search') ?>" class="grid mb-4" role="search">
    <label class="form-group cell cell-6">
        <input type="text" class="form-control" id="s-q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="输入关键词…" maxlength="100">
        <span class="form-label">关键词</span>
    </label>
    <label class="form-group cell cell-3">
        <select class="form-control" id="s-scope" name="scope">
            <?php foreach ($scopes as $key => $label): ?>
            <option value="<?= $key ?>"<?= $scope === $key ? ' selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <span class="form-label">范围</span>
    </label>
    <div class="cell cell-3">
        <button type="submit" class="btn btn-primary w-100">搜索</button>
    </div>
</form>

<?php if (trim($q) === ''): ?>
<p class="muted">请输入关键词后搜索。</p>
<?php else: ?>
<p class="muted">「<strong><?= htmlspecialchars($q) ?></strong>」· 范围：<?= $scopes[$scope] ?> · 命中 <strong><?= count($items) ?></strong> / 共 <?= $total ?> 条</p>

<?php if ($truncated): ?>
<div class="alert alert-info py-2 small">帖子过多，仅扫描了最新 <?= Search::POST_POOL ?> 条回帖；更早的帖子未纳入本次搜索。</div>
<?php endif; ?>
<?php if (Auth::level() >= 2): ?>
<p class="small muted">[mod] 搜索池：最新 <?= Search::POST_POOL ?> 帖；topics/users 全量扫描。</p>
<?php endif; ?>

<?php if (empty($items)): ?>
<p class="muted">没有找到匹配的内容。</p>
<?php elseif ($scope === 'topics'): ?>
<ul class="list-group">
    <?php foreach ($items as $it): ?>
    <li>
        <a href="<?= Router::url('topic/view/' . $it['topic_id']) ?>"><?= htmlspecialchars($it['title']) ?></a>
        <small class="muted ms-1"><?= htmlspecialchars($it['author']) ?> · <?= date('Y-m-d H:i', $it['created']) ?> · <?= $it['posts'] ?> 帖</small>
    </li>
    <?php endforeach; ?>
</ul>
<?php elseif ($scope === 'posts'): ?>
<ul class="list-group">
    <?php foreach ($items as $it): ?>
    <li>
        <a href="<?= Router::url('topic/view/' . $it['topic_id']) ?>#p<?= $it['post_id'] ?>"><?= htmlspecialchars($it['topic_title'] !== '' ? $it['topic_title'] : '（主题已删除）') ?></a>
        <small class="muted ms-1"><?= htmlspecialchars($it['author']) ?> · <?= date('Y-m-d H:i', $it['created']) ?></small>
        <p class="mb-0 mt-1 small muted"><?= htmlspecialchars($it['excerpt']) ?></p>
    </li>
    <?php endforeach; ?>
</ul>
<?php else: /* users */ ?>
<ul class="list-group">
    <?php foreach ($items as $it): ?>
    <li class="flex between items-center">
        <a href="<?= Router::url('user/view/' . rawurlencode($it['username'])) ?>"><?= htmlspecialchars($it['nickname']) ?></a>
        <small class="muted">level <?= $it['level'] ?> · <?= date('Y-m-d', $it['registered']) ?> 注册</small>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($totalPage > 1): ?>
<nav aria-label="分页" class="mt-3"><ul class="pagination">
    <?php for ($i = 1; $i <= $totalPage; $i++): ?>
    <li class="page-item<?= $i === $page ? ' active' : '' ?>"><a class="page-link" href="<?= Router::url('search') ?>?q=<?= rawurlencode($q) ?>&amp;scope=<?= $scope ?>&amp;page=<?= $i ?>"><?= $i ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<?php endif; ?>
