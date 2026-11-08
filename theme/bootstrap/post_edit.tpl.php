<?php defined('FLOO') or die('FlooBB'); ?>
<nav aria-label="面包屑导航">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="<?= Router::url() ?>">首页</a></li>
		<?php if ($topic !== null): ?>
		<li class="breadcrumb-item"><a href="<?= Router::url('topic/view/' . $topic['id']) ?>"><?= htmlspecialchars($topic['title'] ?? '') ?></a></li>
		<?php endif; ?>
		<li class="breadcrumb-item active" aria-current="page">编辑帖子</li>
	</ol>
</nav>
<h1 class="h3 mb-4">编辑帖子</h1>

<?php if (!empty($error)): ?>
<div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Router::url('post/edit/' . $post['id']) ?>" class="col-md-8">
	<?= Auth::csrfField() ?>
	<div class="mb-3">
		<label for="p-content" class="form-label">正文（Markdown）</label>
		<textarea class="form-control font-monospace" id="p-content" name="content" rows="10" required data-md-editor><?= htmlspecialchars($post['content_md'] ?? '') ?></textarea>
	</div>
	<button type="submit" class="btn btn-primary">保存</button>
	<a class="btn btn-link" href="<?= Router::url('topic/view/' . $post['topic_id']) ?>">取消</a>
</form>

<script src="https://cdn.jsdelivr.net/npm/overtype@2/dist/overtype.min.js"></script>
<script src="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/js/md-editor.js') ?>"></script>
