<?php defined('FLOO') or die('FlooBB');
/** @var array|null $pollView 组装好的投票视图数据（topic.controller view()）*/
if (empty($pollView)) return;
$poll = $pollView['poll'];
$isMulti = (int) ($poll['max_choices'] ?? 1) > 1;
?>
<div class="card mb-4 poll-card">
	<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
		<strong><?= htmlspecialchars((string) ($poll['question'] ?? '')) ?></strong>
		<span class="text-body-secondary small">
			<?= $isMulti ? '多选（最多 ' . (int) $poll['max_choices'] . ' 项）' : '单选' ?>
			· 截止 <?= date('Y-m-d H:i', (int) ($poll['deadline'] ?? 0)) ?>
			<?php if (!empty($poll['closed'])): ?>· 已关闭<?php elseif ($pollView['expired']): ?>· 已过期<?php endif; ?>
		</span>
	</div>
	<div class="card-body">
		<?php if (!$pollView['show_result']): ?>
		<?php /* 先投后见（用户决议）：未投者只见表单，不得出现任何票数/百分比 */ ?>
		<?php $viewer = Auth::user(); ?>
		<?php if ($viewer !== null): ?>
		<form method="post" action="<?= Router::url('poll/vote/' . rawurlencode((string) ($topic['id'] ?? ''))) ?>">
			<?= Auth::csrfField() ?>
			<?php foreach ($pollView['options'] as $opt): ?>
			<div class="form-check">
				<input class="form-check-input" type="<?= $isMulti ? 'checkbox' : 'radio' ?>"
					name="choices[]" id="poll-opt-<?= (int) $opt['index'] ?>" value="<?= (int) $opt['index'] ?>">
				<label class="form-check-label" for="poll-opt-<?= (int) $opt['index'] ?>"><?= htmlspecialchars($opt['text']) ?></label>
			</div>
			<?php endforeach; ?>
			<button type="submit" class="btn btn-primary btn-sm mt-2">投票</button>
			<?php if ($isMulti): ?><div class="form-text">可选 <?= (int) $poll['max_choices'] ?> 项。</div><?php endif; ?>
		</form>
		<?php else: ?>
		<p class="text-body-secondary mb-0">请先 <a href="<?= Router::url('auth/login') ?>">登录</a> 后参与投票（投票后可见结果）。</p>
		<?php endif; ?>

		<?php else: ?>
		<?php /* 已投 / 已关闭 / 已过期 → 结果视图 */ ?>
		<?php foreach ($pollView['options'] as $opt): ?>
		<div class="mb-2">
			<div class="d-flex justify-content-between small">
				<span><?= $opt['checked'] ? '<span class="badge text-bg-success me-1">已投</span>' : '' ?><?= htmlspecialchars($opt['text']) ?></span>
				<span class="text-body-secondary"><?= (int) $opt['votes'] ?> 票（<?= (int) $opt['pct'] ?>%）</span>
			</div>
			<div class="progress" role="progressbar" aria-label="<?= htmlspecialchars($opt['text']) ?>"
				aria-valuenow="<?= (int) $opt['pct'] ?>" aria-valuemin="0" aria-valuemax="100" style="height: 8px;">
				<div class="progress-bar<?= $opt['checked'] ? ' bg-success' : '' ?>" style="width: <?= (int) $opt['pct'] ?>%;"></div>
			</div>
		</div>
		<?php endforeach; ?>
		<p class="text-body-secondary small mb-2">共 <?= (int) $pollView['voters_total'] ?> 人参与（<?= (int) $pollView['total'] ?> 票）</p>

		<?php if (!empty($pollView['voters'])): ?>
		<details>
			<summary class="small text-body-secondary">投票人名单</summary>
			<p class="small mb-0 mt-1">
				<?php foreach ($pollView['voters'] as $i => $v): ?><?= $i > 0 ? '、' : '' ?><a href="<?= Router::url('user/view/' . rawurlencode($v['username'])) ?>"><?= htmlspecialchars($v['nickname']) ?></a><?php endforeach; ?>
			</p>
		</details>
		<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php if (!empty($pollView['can_close'])): ?>
	<div class="card-footer bg-transparent">
		<form method="post" action="<?= Router::url('poll/close/' . rawurlencode((string) ($topic['id'] ?? ''))) ?>"
			onsubmit="return confirm('确定关闭该投票？关闭后不能再投。');">
			<?= Auth::csrfField() ?>
			<button type="submit" class="btn btn-sm btn-outline-danger">关闭投票</button>
		</form>
	</div>
	<?php endif; ?>
</div>
