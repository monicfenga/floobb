<?php defined('FLOO') or die('FlooBB'); ?>
<nav aria-label="breadcrumb">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="<?= Router::url() ?>">首页</a></li>
		<?php if ($topic !== null): ?>
		<li class="breadcrumb-item"><a href="<?= Router::url('topic/view/' . rawurlencode((string) ($topic['id'] ?? ''))) ?>"><?= htmlspecialchars($topic['title'] ?? '') ?></a></li>
		<?php endif; ?>
		<li class="breadcrumb-item active" aria-current="page">编辑帖子</li>
	</ol>
</nav>
<h1 class="h3 mb-4">编辑帖子</h1>

<?php if (!empty($error)): ?>
<div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Router::url('post/edit/' . rawurlencode((string) ($post['id'] ?? ''))) ?>" class="col-md-8">
	<?= Auth::csrfField() ?>
	<div class="mb-3">
		<label for="p-content" class="form-label">正文（Markdown）</label>
		<textarea class="form-control font-monospace" id="p-content" name="content" rows="10" required><?= htmlspecialchars($post['content_md'] ?? '') ?></textarea>
	</div>
	<div class="mb-3">
		<span id="previewHelp" class="form-label d-block">预览</span>
		<div class="border rounded p-3 post-content" id="preview" aria-describedby="previewHelp" aria-live="polite"></div>
	</div>
	<button type="submit" class="btn btn-primary">保存</button>
	<a class="btn btn-link" href="<?= Router::url('topic/view/' . rawurlencode((string) ($post['topic_id'] ?? ''))) ?>">取消</a>
</form>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/google-code-prettify@1.0.5/bin/prettify.min.css">
<script src="https://cdn.jsdelivr.net/npm/marked@12/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var md = document.getElementById('p-content');
	var pv = document.getElementById('preview');
	function upd() { pv.innerHTML = DOMPurify.sanitize(marked.parse(md.value)); }
	md.addEventListener('input', upd);
	upd();
	if (window.PR) PR.prettyPrint();
});
</script>
