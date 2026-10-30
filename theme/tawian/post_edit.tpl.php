<?php defined('FLOO') or die('FlooBB'); ?>
<nav class="breadcrumb">
    <li><a href="<?= Router::url() ?>">首页</a></li>
    <?php if ($topic !== null): ?>
    <li><a href="<?= Router::url('topic/view/' . $topic['id']) ?>"><?= htmlspecialchars($topic['title'] ?? '') ?></a></li>
    <?php endif; ?>
    <li class="active">编辑帖子</li>
</nav>
<h1 class="mb-4">编辑帖子</h1>

<?php if (!empty($error)): ?>
<div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Router::url('post/edit/' . $post['id']) ?>" class="form" style="max-width:48rem;">
    <?= Auth::csrfField() ?>
    <label class="form-group">
        <textarea class="form-control" id="p-content" name="content" rows="10" required><?= htmlspecialchars($post['content_md'] ?? '') ?></textarea>
        <span class="form-label">正文（Markdown）</span>
    </label>
    <div class="mb-3">
        <span class="form-label d-block">预览</span>
        <div class="card"><div class="card-body post-content" id="preview" aria-live="polite"></div></div>
    </div>
    <button type="submit" class="btn btn-primary">保存</button>
    <a class="btn btn-link" href="<?= Router::url('topic/view/' . $post['topic_id']) ?>">取消</a>
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
