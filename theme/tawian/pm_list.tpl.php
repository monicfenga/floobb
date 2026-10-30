<?php defined('FLOO') or die('FlooBB');
/** @var string $side inbox|outbox @var array $items @var array $peerNames @var string $pageLinks */
$unreadOnly = ($side === 'inbox');
?>
<nav class="breadcrumb">
    <li><a href="<?= Router::url() ?>">首页</a></li>
    <li class="active">私信</li>
</nav>

<div class="flex between items-center mb-3">
    <h1 class="mb-0"><?= $unreadOnly ? '收件箱' : '已发送' ?></h1>
    <a class="btn btn-primary btn-sm" href="<?= Router::url('pm/compose') ?>">写私信</a>
</div>

<ul class="list-group mb-2">
    <li class="active"><strong>文件夹</strong></li>
    <li><a class="<?= $unreadOnly ? 'active' : '' ?>" href="<?= Router::url('pm/inbox') ?>">收件箱</a></li>
    <li><a class="<?= !$unreadOnly ? 'active' : '' ?>" href="<?= Router::url('pm/outbox') ?>">已发送</a></li>
</ul>

<?php if (empty($items)): ?>
<div class="alert" role="status"><?= $unreadOnly ? '收件箱为空。' : '还没有发过私信。' ?></div>
<?php else: ?>
<ul class="list-group">
    <?php foreach ($items as $m): $mid = (string) ($m['id'] ?? ''); $peer = (string) ($unreadOnly ? ($m['from'] ?? '') : ($m['to'] ?? '')); ?>
    <li class="flex between items-center wrap gap-2">
        <div>
            <a class="<?= ($unreadOnly && empty($m['read'])) ? 'strong' : '' ?>" href="<?= Router::url('pm/view/' . rawurlencode($mid)) ?>"><?= htmlspecialchars((string) ($m['subject'] ?? '')) ?></a>
            <small class="muted ms-1">
                <?= $unreadOnly ? '来自' : '发给' ?>
                <a href="<?= Router::url('user/view/' . rawurlencode($peer)) ?>"><?= htmlspecialchars($peerNames[$peer] ?? $peer) ?></a>
                · <?= date('m-d H:i', $m['time'] ?? 0) ?>
            </small>
            <?php if ($unreadOnly && empty($m['read'])): ?><span class="tag tag-primary ms-1">未读</span><?php endif; ?>
        </div>
        <form method="post" action="<?= Router::url('pm/delete/' . rawurlencode($mid)) ?>" class="inline" onsubmit="return confirm('确定删除该私信？');">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="side" value="<?= htmlspecialchars($side) ?>">
            <button type="submit" class="btn btn-link btn-sm" style="color:var(--accent)">删除</button>
        </form>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?= $pageLinks ?>
