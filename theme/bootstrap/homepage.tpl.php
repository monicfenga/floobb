<?php defined('FLOO') or die('FlooBB'); ?>
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
			<div class="card-footer text-body-secondary d-flex gap-3 flex-wrap">
				<span>主题 <?= (int) ($forum['topic_count'] ?? 0) ?></span>
				<span>帖子 <?= (int) ($forum['post_count'] ?? 0) ?></span>
				<?php if (!empty($forum['last_active'])): ?>
				<span>最后活跃 <?= date('Y-m-d H:i', strtotime((string) $forum['last_active'])) ?></span>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($latestTopics)): ?>
<h2 class="h5 mt-4 mb-2">最新主题</h2>
<ul class="list-group">
	<?php foreach ($latestTopics as $row): $t = $row['topic']; ?>
	<li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
		<div>
			<?php if (!empty($t['sticky'])): ?><span class="badge text-bg-warning me-1">置顶</span><?php endif; ?>
			<?php if (!empty($t['locked'])): ?><span class="badge text-bg-secondary me-1">已锁定</span><?php endif; ?>
			<a href="<?= Router::url('topic/view/' . rawurlencode((string) $t['id'])) ?>"><?= htmlspecialchars($t['title'] ?? '') ?></a>
			<small class="text-body-secondary ms-2">
				<?php if ($row['forum_name'] !== ''): ?><a class="text-decoration-none" href="<?= Router::url('forum/view/' . rawurlencode($row['forum_id'])) ?>"><?= htmlspecialchars($row['forum_name']) ?></a> · <?php endif; ?>
				<?= htmlspecialchars($t['author'] ?? '') ?> · <?= date('Y-m-d H:i', strtotime((string) (($t['last_reply_at'] ?? '') !== '' ? $t['last_reply_at'] : ($t['created'] ?? '')))) ?> · <?= count((array) ($t['post_ids'] ?? array())) ?> 帖
			</small>
		</div>
	</li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>
