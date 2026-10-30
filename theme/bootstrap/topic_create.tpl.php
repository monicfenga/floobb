<?php defined('FLOO') or die('FlooBB'); ?>
<h1 class="h3 mb-4">新建主题</h1>

<?php if (empty($forums)): ?>
<div class="alert alert-info" role="status">还没有版块，无法发主题。</div>
<?php else: ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Router::url('topic/create') ?>" class="col-md-8">
	<?= Auth::csrfField() ?>
	<div class="mb-3">
		<label for="t-forum" class="form-label">版块</label>
		<select class="form-select" id="t-forum" name="fid" required>
			<?php foreach ($forums as $f): ?>
			<option value="<?= htmlspecialchars((string) ($f['id'] ?? '')) ?>" <?= (string) ($values['fid'] ?? '') === (string) $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name'] ?? '') ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="mb-3">
		<label for="t-title" class="form-label">标题</label>
		<input type="text" class="form-control" id="t-title" name="title" maxlength="120" required
			value="<?= htmlspecialchars($values['title'] ?? '') ?>">
	</div>
	<div class="mb-3">
		<label for="t-content" class="form-label">正文（Markdown）</label>
		<textarea class="form-control font-monospace" id="t-content" name="content" rows="10" required><?= htmlspecialchars($values['content'] ?? '') ?></textarea>
		<div class="form-text">支持 Markdown；预览仅供参考，以服务端渲染为准。</div>
	</div>
	<div class="mb-3">
		<span id="previewHelp" class="form-label d-block">预览</span>
		<div class="border rounded p-3 post-content" id="preview" aria-describedby="previewHelp" aria-live="polite"></div>
	</div>
	<button type="submit" class="btn btn-primary">发布主题</button>
</form>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/google-code-prettify@1.0.5/bin/prettify.min.css">
<script src="https://cdn.jsdelivr.net/npm/marked@12/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var md = document.getElementById('t-content');
	var pv = document.getElementById('preview');
	function upd() { pv.innerHTML = DOMPurify.sanitize(marked.parse(md.value)); }
	md.addEventListener('input', upd);
	upd();
	if (window.PR) PR.prettyPrint();
});
</script>
<?php endif; ?>
