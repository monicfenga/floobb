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
        <textarea class="form-control" id="p-content" name="content" rows="10" required data-md-editor><?= htmlspecialchars($post['content_md'] ?? '') ?></textarea>
        <span class="form-label">正文（Markdown）</span>
    </label>
    <button type="submit" class="btn btn-primary">保存</button>
    <a class="btn btn-link" href="<?= Router::url('topic/view/' . $post['topic_id']) ?>">取消</a>
</form>

<script src="https://cdn.jsdelivr.net/npm/overtype@2/dist/overtype.min.js"></script>
<script src="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/js/md-editor.js') ?>"></script>
