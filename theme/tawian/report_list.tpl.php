<?php defined('FLOO') or die('FlooBB');
/** @var array $rows @var string $tab open|done */
?>
<nav class="breadcrumb">
    <li><a href="<?= Router::url() ?>">首页</a></li>
    <li class="active">举报队列</li>
</nav>

<h1 class="mb-3">举报队列</h1>

<ul class="list-group mb-3" style="border:0;">
    <li class="active"><strong>状态</strong></li>
    <li><a class="<?= $tab === 'open' ? 'active' : '' ?>" href="<?= Router::url('report/list') ?>?tab=open">待处理</a></li>
    <li><a class="<?= $tab === 'done' ? 'active' : '' ?>" href="<?= Router::url('report/list') ?>?tab=done">已处理</a></li>
</ul>

<?php if (empty($rows)): ?>
<div class="alert" role="status"><?= $tab === 'open' ? '暂无待处理举报。' : '暂无处理记录。' ?></div>
<?php else: ?>
<?php foreach ($rows as $row): $r = $row['report']; ?>
<div class="card mb-2">
    <div class="card-body py-2">
        <div class="flex between items-start wrap gap-2">
            <div>
                <span class="tag tag-warning"><?= htmlspecialchars(Report::reasons()[(string) ($r['reason'] ?? '')] ?? (string) ($r['reason'] ?? '')) ?></span>
                <span class="tag"><?= (string) ($r['type'] ?? '') === 'post' ? '回帖' : ((string) ($r['type'] ?? '') === 'user' ? '用户' : '主题') ?></span>
                <?php if (!empty($row['link'])): ?>
                <a href="<?= $row['link'] ?>" class="strong"><?= htmlspecialchars($row['summary'] ?: '查看目标') ?></a>
                <?php else: ?>
                <span class="muted"><?= htmlspecialchars($row['summary']) ?></span>
                <?php endif; ?>
            </div>
            <small class="muted"><?= date('m-d H:i', $r['created'] ?? 0) ?></small>
        </div>
        <p class="small muted mb-1">
            举报人：<a href="<?= Router::url('user/view/' . rawurlencode((string) ($r['reporter'] ?? ''))) ?>"><?= htmlspecialchars((string) ($r['reporter'] ?? '')) ?></a>
            <?php if ((string) ($r['detail'] ?? '') !== ''): ?>· 说明：<?= htmlspecialchars((string) $r['detail']) ?><?php endif; ?>
        </p>
        <?php if ($tab === 'open'): ?>
        <form method="post" action="<?= Router::url('report/handle/' . rawurlencode((string) ($r['id'] ?? ''))) ?>" class="flex gap-2 wrap items-center">
            <?= Auth::csrfField() ?>
            <?php if ((string) ($r['type'] ?? '') === 'user'): ?>
            <select class="form-control" name="duration" aria-label="封禁时长" required style="width:auto;">
                <option value="1d">封禁 1 天</option>
                <option value="7d">封禁 7 天</option>
                <option value="30d">封禁 30 天</option>
                <option value="permanent">永久封禁</option>
            </select>
            <button type="submit" name="action" value="ban" class="btn btn-error btn-sm" onclick="return confirm('确定按所选时长封禁该用户？将私信通知其封禁时长。');">封禁</button>
            <button type="submit" name="action" value="dismiss" class="btn btn-ghost btn-sm" onclick="return confirm('确定忽略该举报？将私信告知举报者。');">忽略</button>
            <?php else: ?>
            <button type="submit" name="action" value="delete" class="btn btn-error btn-sm" onclick="return confirm('确定删除该目标？将同时私信通知举报者与被举报者。');">删除</button>
            <button type="submit" name="action" value="dismiss" class="btn btn-ghost btn-sm" onclick="return confirm('确定忽略该举报？将私信告知举报者。');">忽略</button>
            <?php endif; ?>
        </form>
        <?php else: ?>
        <p class="small mb-0">
            <?php $resolution = (string) ($r['resolution'] ?? ''); ?>
            <span class="tag <?= $resolution === 'deleted' ? 'tag-error' : ($resolution === 'banned' ? '' : 'tag-default') ?>">
                <?= $resolution === 'deleted' ? '已删除' : ($resolution === 'banned' ? '已封禁' : '已忽略') ?>
            </span>
            处理人：<?= htmlspecialchars($row['resolved_by_name']) ?>
        </p>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
