<?php defined('FLOO') or die('FlooBB'); ?>
<nav aria-label="breadcrumb">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="<?= Router::url() ?>">首页</a></li>
		<?php if ($forum !== null): ?>
		<li class="breadcrumb-item"><a href="<?= Router::url('forum/view/' . $forum['id']) ?>"><?= htmlspecialchars($forum['name'] ?? '') ?></a></li>
		<?php endif; ?>
		<li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($topic['title'] ?? '') ?></li>
	</ol>
</nav>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
	<h1 class="h4 mb-0">
		<?php if (!empty($topic['sticky'])): ?><span class="badge text-bg-warning me-1">置顶</span><?php endif; ?>
		<?php if (!empty($topic['locked'])): ?><span class="badge text-bg-secondary me-1">已锁定</span><?php endif; ?>
		<?= htmlspecialchars($topic['title'] ?? '') ?>
	</h1>
	<div class="d-flex gap-1 align-items-center">
		<ul class="nav nav-pills" aria-label="帖子排序与筛选">
			<li class="nav-item"><a class="nav-link py-1 <?= $sort === 'oldest' ? 'active' : '' ?>" href="<?= Router::url('topic/view/' .$topic['id']) ?><?= $filter === 'author' ? '?filter=author' : '' ?>">正序</a></li>
			<li class="nav-item"><a class="nav-link py-1 <?= $sort === 'latest' ? 'active' : '' ?>" href="<?= Router::url('topic/view/' .$topic['id']) ?>?sort=latest<?= $filter === 'author' ? '&filter=author' : '' ?>">倒序</a></li>
			<li class="nav-item"><a class="nav-link py-1 <?= $filter === 'author' ? 'active' : '' ?>" href="<?= Router::url('topic/view/' .$topic['id']) ?>?filter=author<?= $sort === 'latest' ? '&sort=latest' : '' ?>">只看楼主</a></li>
		</ul>
	</div>
</div>
<p class="text-body-secondary small">
	楼主 <a href="<?= Router::url('user/view/' . rawurlencode((string) ($topic['author'] ?? ''))) ?>"><?= htmlspecialchars($topic['author'] ?? '') ?></a>
	· <?= date('Y-m-d H:i', (int) ($topic['created'] ?? 0)) ?>
	· <?= count((array) ($topic['post_ids'] ?? array())) ?> 帖
</p>

<?php if (Auth::level() >= 2): ?>
<div class="btn-group btn-group-sm mb-3" role="group" aria-label="管理操作">
	<form method="post" action="<?= Router::url('topic/lock/' . $topic['id']) ?>" class="d-inline">
		<?= Auth::csrfField() ?>
		<button type="submit" class="btn btn-outline-secondary"><?= empty($topic['locked']) ? '锁定' : '解锁' ?></button>
	</form>
	<form method="post" action="<?= Router::url('topic/sticky/' . $topic['id']) ?>" class="d-inline">
		<?= Auth::csrfField() ?>
		<button type="submit" class="btn btn-outline-secondary"><?= empty($topic['sticky']) ? '置顶' : '取消置顶' ?></button>
	</form>
	<form method="post" action="<?= Router::url('topic/move/' . $topic['id']) ?>" class="input-group input-group-sm" style="width: 13rem;">
		<?= Auth::csrfField() ?>
		<select name="fid" class="form-select" aria-label="移动到版块" required>
			<?php foreach (($allForums ?? array()) as $fId => $fName): ?>
			<option value="<?= htmlspecialchars($fId) ?>" <?= $fId === (string) ($topic['forum_id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($fName) ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="btn btn-outline-secondary">移动</button>
	</form>
	<form method="post" action="<?= Router::url('topic/delete/' . $topic['id']) ?>" class="d-inline" onsubmit="return confirm('确定删除该主题及其全部帖子？');">
		<?= Auth::csrfField() ?>
		<button type="submit" class="btn btn-outline-danger">删除主题</button>
	</form>
</div>
<?php endif; ?>

<?php $report_type = 'topic'; $report_target = (string) $topic['id']; $report_label = '举报主题'; ?>
<?php require __DIR__ . DS . '_report_btn.tpl.php'; ?>

<?php require __DIR__ . DS . '_poll.tpl.php'; ?>

<?php foreach ($posts as $row): $p = $row['post']; ?>
<article class="card mb-2" id="p<?= $p['id'] ?>">
	<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
		<span class="d-inline-flex align-items-center gap-1">
			<?php if (!empty($row['author_avatar'])): ?>
			<img class="rounded" width="24" height="24"
				src="<?= Router::url('uploads/avatars/' . rawurlencode((string) $row['author_avatar'])) ?>" alt="">
			<?php endif; ?>
			<a href="<?= Router::url('user/view/' . rawurlencode((string) ($p['author'] ?? ''))) ?>"><?= htmlspecialchars($row['author_nickname']) ?></a>
			<?php if ((string) ($row['author_bio'] ?? '') !== ''): ?>
			<small class="text-body-secondary"><?= htmlspecialchars((string) $row['author_bio']) ?></small>
			<?php endif; ?>
		</span>
		<small class="text-body-secondary">
			<a href="#p<?= $p['id'] ?>">#<?= substr($p['id'], -5) ?></a>
			· <?= date('Y-m-d H:i', (int) ($p['created'] ?? 0)) ?>
			<?php if (!empty($p['edited'])): ?>· 已编辑（<?= date('m-d H:i', (int) $p['edited']) ?>）<?php endif; ?>
		</small>
	</div>
	<div class="card-body post-content"><?= $p['content_html'] ?? '' ?></div>
	<?php if ($row['can_edit'] || $row['can_delete']): ?>
	<div class="card-footer bg-transparent d-flex gap-2 py-1 align-items-center">
		<?php $report_type = 'post'; $report_target = (string) $p['id']; $report_label = '举报'; ?>
		<?php require __DIR__ . DS . '_report_btn.tpl.php'; ?>
		<?php if ($row['can_edit']): ?>
		<a class="btn btn-sm btn-link text-decoration-none px-0" href="<?= Router::url('post/edit/' . $p['id']) ?>">编辑</a>
		<?php endif; ?>
		<?php if ($row['can_delete']): ?>
		<form method="post" action="<?= Router::url('post/delete/' . $p['id']) ?>" onsubmit="return confirm('确定删除该帖子？');">
			<?= Auth::csrfField() ?>
			<button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none px-0">删除</button>
		</form>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</article>
<?php endforeach; ?>

<?= $pageLinks ?>

<?php if (!empty($topic['locked'])): ?>
<div class="alert alert-secondary" role="status">主题已锁定，无法回帖。</div>
<?php elseif (Auth::isLoggedIn()): ?>
<h2 class="h6 mt-4">回复</h2>
<form method="post" action="<?= Router::url('post/create/' .$topic['id']) ?>">
	<?= Auth::csrfField() ?>
	<div class="mb-2">
		<label for="reply-content" class="form-label visually-hidden">回帖内容（Markdown）</label>
		<textarea class="form-control" id="reply-content" name="content" rows="4" required placeholder="Markdown 回帖…"></textarea>
	</div>
	<button type="submit" class="btn btn-primary">回帖</button>
</form>
<?php else: ?>
<div class="alert alert-info" role="status"><a href="<?= Router::url('auth/login') ?>">登录</a> 后回帖。</div>
<?php endif; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/google-code-prettify@1.0.5/bin/prettify.min.css">
<script src="https://cdn.jsdelivr.net/npm/google-code-prettify@1.0.5/bin/prettify.min.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () { if (window.PR) PR.prettyPrint(); });</script>
