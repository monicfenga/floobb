<?php defined('FLOO') or die('potato'); // NOOOO ?>
<h1 class="h3 mb-4">版块列表</h1>

<?php if (Auth::level() >= 3): ?>
<div class="mb-3">
	<a class="btn btn-primary" href="<?= Router::url('forum/add') ?>">新建版块</a>
</div>
<?php endif; ?>

<?php if (empty($forums)): ?>
<div class="alert alert-info" role="status">还没有版块。安装管理员登录后可在 Phase 1 创建第一个版块。</div>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-2 g-3">
	<?php foreach ($forums as $forum): ?>
	<div class="col">
		<div class="card h-100">
			<div class="card-body">
				<h2 class="card-title h5">
					<a class="stretched-link text-decoration-none" href="<?= Router::url('forum/view/' . urlencode($forum['id'] ?? '')) ?>"><?= htmlspecialchars($forum['name'] ?? '') ?></a>
				</h2>
				<p class="card-text text-body-secondary"><?= htmlspecialchars($forum['description'] ?? '') ?></p>
			</div>
			<div class="card-footer text-body-secondary d-flex gap-3">
				<span>主题 <?= (int) ($forum['topic_count'] ?? 0) ?></span>
				<span>帖子 <?= (int) ($forum['post_count'] ?? 0) ?></span>
			</div>
		</div>
	</div>
	<?php endforeach; ?>
</div>
<?php endif; ?>
