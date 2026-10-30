<?php defined('FLOO') or die('FlooBB');
/** @var string $side inbox|outbox @var array $items @var array $peerNames @var string $pageLinks */
$unreadOnly = ($side === 'inbox');
?>
<nav aria-label="breadcrumb">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="<?= Router::url() ?>">首页</a></li>
		<li class="breadcrumb-item active" aria-current="page">私信</li>
	</ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
	<h1 class="h3 mb-0"><?= $unreadOnly ? '收件箱' : '已发送' ?></h1>
	<a class="btn btn-primary btn-sm" href="<?= Router::url('pm/compose') ?>">写私信</a>
</div>

<ul class="nav nav-pills mb-3">
	<li class="nav-item"><a class="nav-link py-1 <?= $unreadOnly ? 'active' : '' ?>" href="<?= Router::url('pm/inbox') ?>">收件箱</a></li>
	<li class="nav-item"><a class="nav-link py-1 <?= !$unreadOnly ? 'active' : '' ?>" href="<?= Router::url('pm/outbox') ?>">已发送</a></li>
</ul>

<?php if (empty($items)): ?>
<div class="alert alert-secondary" role="status"><?= $unreadOnly ? '收件箱为空。' : '还没有发过私信。' ?></div>
<?php else: ?>
<ul class="list-group">
	<?php foreach ($items as $m): $mid = (string) ($m['id'] ?? ''); $peer = (string) ($unreadOnly ? ($m['from'] ?? '') : ($m['to'] ?? '')); ?>
	<li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
		<div>
			<a class="<?= ($unreadOnly && empty($m['read'])) ? 'fw-bold' : '' ?>"
				href="<?= Router::url('pm/view/' . rawurlencode($mid)) ?>"><?= htmlspecialchars((string) ($m['subject'] ?? '')) ?></a>
			<small class="text-body-secondary ms-1">
				<?= $unreadOnly ? '来自' : '发给' ?>
				<a href="<?= Router::url('user/view/' . rawurlencode($peer)) ?>"><?= htmlspecialchars($peerNames[$peer] ?? $peer) ?></a>
				· <?= date('m-d H:i', (int) ($m['time'] ?? 0)) ?>
			</small>
			<?php if ($unreadOnly && empty($m['read'])): ?><span class="badge text-bg-primary ms-1">未读</span><?php endif; ?>
		</div>
		<form method="post" action="<?= Router::url('pm/delete/' . rawurlencode($mid)) ?>" class="d-inline"
			onsubmit="return confirm('确定删除该私信？');">
			<?= Auth::csrfField() ?>
			<input type="hidden" name="side" value="<?= htmlspecialchars($side) ?>">
			<button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none px-0">删除</button>
		</form>
	</li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>

<?= $pageLinks ?>
