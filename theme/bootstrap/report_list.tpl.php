<?php defined('FLOO') or die('FlooBB');
/** @var array $rows @var string $tab open|done */
?>
<nav aria-label="breadcrumb">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="<?= Router::url() ?>">首页</a></li>
		<li class="breadcrumb-item active" aria-current="page">举报队列</li>
	</ol>
</nav>

<h1 class="h3 mb-3">举报队列</h1>

<ul class="nav nav-pills mb-3">
	<li class="nav-item"><a class="nav-link py-1 <?= $tab === 'open' ? 'active' : '' ?>" href="<?= Router::url('report/list') ?>?tab=open">待处理</a></li>
	<li class="nav-item"><a class="nav-link py-1 <?= $tab === 'done' ? 'active' : '' ?>" href="<?= Router::url('report/list') ?>?tab=done">已处理</a></li>
</ul>

<?php if (empty($rows)): ?>
<div class="alert alert-secondary" role="status"><?= $tab === 'open' ? '暂无待处理举报。' : '暂无处理记录。' ?></div>
<?php else: ?>
<?php foreach ($rows as $row): $r = $row['report']; ?>
<div class="card mb-2">
	<div class="card-body py-2">
		<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
			<div>
				<span class="badge text-bg-warning"><?= htmlspecialchars(Report::reasons()[(string) ($r['reason'] ?? '')] ?? (string) ($r['reason'] ?? '')) ?></span>
				<span class="badge text-bg-secondary"><?= (string) ($r['type'] ?? '') === 'post' ? '回帖' : '主题' ?></span>
				<?php if (!empty($row['link'])): ?>
				<a href="<?= $row['link'] ?>" class="fw-bold"><?= htmlspecialchars($row['summary'] ?: '查看目标') ?></a>
				<?php else: ?>
				<span class="text-body-secondary"><?= htmlspecialchars($row['summary']) ?></span>
				<?php endif; ?>
			</div>
			<small class="text-body-secondary"><?= date('m-d H:i', (int) ($r['created'] ?? 0)) ?></small>
		</div>
		<p class="small text-body-secondary mb-1">
			举报人：<a href="<?= Router::url('user/view/' . rawurlencode((string) ($r['reporter'] ?? ''))) ?>"><?= htmlspecialchars((string) ($r['reporter'] ?? '')) ?></a>
			<?php if ((string) ($r['detail'] ?? '') !== ''): ?>· 说明：<?= htmlspecialchars((string) $r['detail']) ?><?php endif; ?>
		</p>
		<?php if ($tab === 'open'): ?>
		<form method="post" action="<?= Router::url('report/handle/' . rawurlencode((string) ($r['id'] ?? ''))) ?>" class="d-flex gap-2 flex-wrap align-items-center">
			<?= Auth::csrfField() ?>
			<?php if ((string) ($r['type'] ?? '') === 'user'): ?>
			<select class="form-select form-select-sm" name="duration" aria-label="封禁时长" required style="width:auto;">
				<option value="1d">封禁 1 天</option>
				<option value="7d">封禁 7 天</option>
				<option value="30d">封禁 30 天</option>
				<option value="permanent">永久封禁</option>
			</select>
			<button type="submit" name="action" value="ban" class="btn btn-sm btn-danger"
				onclick="return confirm('确定按所选时长封禁该用户？将私信通知其封禁时长。');">封禁</button>
			<button type="submit" name="action" value="dismiss" class="btn btn-sm btn-outline-secondary"
				onclick="return confirm('确定忽略该举报？将私信告知举报者。');">忽略</button>
			<?php else: ?>
			<button type="submit" name="action" value="delete" class="btn btn-sm btn-danger"
				onclick="return confirm('确定删除该目标？将同时私信通知举报者与被举报者。');">删除</button>
			<button type="submit" name="action" value="dismiss" class="btn btn-sm btn-outline-secondary"
				onclick="return confirm('确定忽略该举报？将私信告知举报者。');">忽略</button>
			<?php endif; ?>
		</form>
		<?php else: ?>
		<p class="small mb-0">
			<?php $resolution = (string) ($r['resolution'] ?? ''); ?>
			<span class="badge <?= $resolution === 'deleted' ? 'text-bg-danger' : ($resolution === 'banned' ? 'text-bg-dark' : 'text-bg-light') ?>">
				<?= $resolution === 'deleted' ? '已删除' : ($resolution === 'banned' ? '已封禁' : '已忽略') ?>
			</span>
			处理人：<?= htmlspecialchars($row['resolved_by_name']) ?>
		</p>
		<?php endif; ?>
	</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
