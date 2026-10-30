<?php defined('FLOO') or die('FlooBB'); ?>
<?php /* Discourse 风双栏：左最新主题 / 右版块列表 */ ?>
<div class="row g-4">
	<div class="col-lg-6">
		<h1 class="h5 mb-3">最新主题</h1>
		<?php if (empty($latestTopics)): ?>
		<div class="alert alert-info" role="status">还没有主题。</div>
		<?php else: ?>
		<ul class="list-group">
			<?php foreach ($latestTopics as $row): $t = $row['topic']; ?>
			<li class="list-group-item">
				<div>
					<?php if (!empty($t['sticky'])): ?><span class="badge text-bg-warning me-1">置顶</span><?php endif; ?>
					<?php if (!empty($t['locked'])): ?><span class="badge text-bg-secondary me-1">已锁定</span><?php endif; ?>
					<a href="<?= Router::url('topic/view/' . $t['id']) ?>"><?= htmlspecialchars($t['title'] ?? '') ?></a>
				</div>
				<small class="text-body-secondary">
					<?php if ($row['forum_name'] !== ''): ?><a class="text-decoration-none" href="<?= Router::url('forum/view/' . rawurlencode($row['forum_id'])) ?>"><?= htmlspecialchars($row['forum_name']) ?></a> · <?php endif; ?>
					<?= htmlspecialchars($t['author'] ?? '') ?> · <?= date('Y-m-d H:i', ($t['last_reply_at'] ?? 0) ?: ($t['created'] ?? 0)) ?> · <?= count((array) ($t['post_ids'] ?? array())) ?> 帖
				</small>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
	<div class="col-lg-6">
		<div class="d-flex justify-content-between align-items-center mb-3">
			<h1 class="h5 mb-0">版块</h1>
			<?php if (Auth::level() >= 3): ?>
			<a class="btn btn-sm btn-primary" href="<?= Router::url('forum/add') ?>">新建版块</a>
			<?php endif; ?>
		</div>
		<?php if (empty($forums)): ?>
		<div class="alert alert-info" role="status">还没有版块。安装管理员登录后可在 Phase 1 创建第一个版块。</div>
		<?php else: ?>
		<div class="list-group">
			<?php foreach ($forums as $forum): ?>
			<a class="list-group-item list-group-item-action" href="<?= Router::url('forum/view/' . $forum['id']) ?>">
				<div class="d-flex justify-content-between align-items-baseline">
					<span class="fw-bold"><?= htmlspecialchars($forum['name'] ?? '') ?></span>
					<small class="text-body-secondary"><?= (int) ($forum['topic_count'] ?? 0) ?> 主题 · <?= (int) ($forum['post_count'] ?? 0) ?> 帖</small>
				</div>
				<div class="text-body-secondary small">
					<?= htmlspecialchars($forum['description'] ?? '') ?>
					<?php if (!empty($forum['last_active'])): ?>
					<span class="ms-1">· 最后活跃 <?= date('m-d H:i', $forum['last_active']) ?></span>
					<?php endif; ?>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
</div>
