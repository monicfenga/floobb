<?php defined('FLOO') or die('FlooBB');
/** @var string $side inbox|outbox @var array $msg @var string $peerName */
$isInbox = ($side === 'inbox');
?>
<nav aria-label="breadcrumb">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="<?= Router::url() ?>">首页</a></li>
		<li class="breadcrumb-item"><a href="<?= Router::url('pm/inbox') ?>">私信</a></li>
		<li class="breadcrumb-item active" aria-current="page">详情</li>
	</ol>
</nav>

<div class="card">
	<div class="card-header">
		<h1 class="h5 mb-0"><?= htmlspecialchars((string) ($msg['subject'] ?? '')) ?></h1>
	</div>
	<div class="card-body">
		<p class="text-body-secondary small mb-2">
			<?= $isInbox ? '来自' : '发给' ?>
			<a href="<?= Router::url('user/view/' . rawurlencode((string) ($isInbox ? ($msg['from'] ?? '') : ($msg['to'] ?? '')))) ?>"><?= htmlspecialchars($peerName) ?></a>
			· <?= htmlspecialchars((string) ($msg['time'] ?? '')) ?>
		</p>
		<p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars((string) ($msg['body'] ?? '')) ?></p>
	</div>
	<div class="card-footer bg-transparent d-flex gap-2">
		<?php if ($isInbox): ?>
		<a class="btn btn-sm btn-outline-primary"
			href="<?= Router::url('pm/compose') ?>?to=<?= rawurlencode((string) ($msg['from'] ?? '')) ?>">回复</a>
		<?php endif; ?>
		<form method="post" action="<?= Router::url('pm/delete/' . rawurlencode((string) ($msg['id'] ?? ''))) ?>"
			onsubmit="return confirm('确定删除该私信？');">
			<?= Auth::csrfField() ?>
			<input type="hidden" name="side" value="<?= htmlspecialchars($side) ?>">
			<button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
		</form>
		<a class="btn btn-sm btn-link text-decoration-none" href="<?= Router::url('pm/' . $side) ?>">返回列表</a>
	</div>
</div>
