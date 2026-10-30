<?php defined('FLOO') or die('FlooBB'); ?>
<nav aria-label="breadcrumb">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="<?= Router::url() ?>">首页</a></li>
		<li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($forum['name'] ?? '') ?></li>
	</ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-1">
	<h1 class="h3 mb-0"><?= htmlspecialchars($forum['name'] ?? '') ?></h1>
	<?php if (Auth::isLoggedIn()): ?>
	<a class="btn btn-primary" href="<?= Router::url('topic/create/' . rawurlencode((string) ($forum['id'] ?? ''))) ?>">新建主题</a>
	<?php else: ?>
	<a class="btn btn-outline-primary" href="<?= Router::url('auth/login') ?>">登录后发帖</a>
	<?php endif; ?>
</div>
<p class="text-body-secondary">
	<?= htmlspecialchars($forum['description'] ?? '') ?>
	<span class="ms-2">主题 <?= (int) ($forum['topic_count'] ?? 0) ?> · 帖子 <?= (int) ($forum['post_count'] ?? 0) ?></span>
</p>

<ul class="nav nav-pills mb-2" aria-label="排序">
	<li class="nav-item">
		<a class="nav-link <?= $sort === 'latest' ? 'active' : '' ?>" href="<?= Router::url('forum/view/' . rawurlencode((string) ($forum['id'] ?? ''))) ?>?sort=latest">最新</a>
	</li>
	<li class="nav-item">
		<a class="nav-link <?= $sort === 'oldest' ? 'active' : '' ?>" href="<?= Router::url('forum/view/' . rawurlencode((string) ($forum['id'] ?? ''))) ?>?sort=oldest">最早</a>
	</li>
</ul>

<?php if (!empty($stickyTopics)): ?>
<h2 class="h6 text-body-secondary mt-3">置顶主题</h2>
<ul class="list-group mb-3">
	<?php foreach ($stickyTopics as $t): ?>
	<li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
		<div>
			<span class="badge text-bg-warning me-1">置顶</span>
			<?php if (!empty($t['locked'])): ?><span class="badge text-bg-secondary me-1">已锁定</span><?php endif; ?>
			<a href="<?= Router::url('topic/view/' . rawurlencode((string) $t['id'])) ?>"><?= htmlspecialchars($t['title'] ?? '') ?></a>
			<small class="text-body-secondary ms-2"><?= htmlspecialchars($t['author'] ?? '') ?> · <?= date('Y-m-d H:i', strtotime((string) ($t['created'] ?? ''))) ?></small>
		</div>
		<?php require THEME_DIR . $conf['theme'] . DS . '_topic_actions.tpl.php'; ?>
	</li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>

<h2 class="h6 text-body-secondary">全部主题</h2>
<?php if (empty($topics) && empty($stickyTopics)): ?>
<div class="alert alert-info" role="status">还没有主题，来发第一帖。</div>
<?php else: ?>
<ul class="list-group">
	<?php foreach ($topics as $t): ?>
	<li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
		<div>
			<?php if (!empty($t['locked'])): ?><span class="badge text-bg-secondary me-1">已锁定</span><?php endif; ?>
			<a href="<?= Router::url('topic/view/' . rawurlencode((string) $t['id'])) ?>"><?= htmlspecialchars($t['title'] ?? '') ?></a>
			<small class="text-body-secondary ms-2"><?= htmlspecialchars($t['author'] ?? '') ?> · <?= date('Y-m-d H:i', strtotime((string) ($t['created'] ?? ''))) ?> · <?= count((array) ($t['post_ids'] ?? array())) ?> 帖</small>
		</div>
		<?php require THEME_DIR . $conf['theme'] . DS . '_topic_actions.tpl.php'; ?>
	</li>
	<?php endforeach; ?>
</ul>
<?= $pageLinks ?>
<?php endif; ?>
