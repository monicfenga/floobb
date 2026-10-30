<?php defined('FLOO') or die('FlooBB'); ?>
<h1 class="mb-4">新建版块</h1>

<?php if (!empty($error)): ?>
<div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Router::url('forum/add') ?>" class="form" style="max-width:32rem;">
    <?= Auth::csrfField() ?>
    <label class="form-group">
        <input type="text" class="form-control" id="f-name" name="name" maxlength="50" required value="<?= htmlspecialchars($values['name'] ?? '') ?>">
        <span class="form-label">版块名</span>
    </label>
    <label class="form-group">
        <input type="text" class="form-control" id="f-slug" name="slug" maxlength="50" pattern="[A-Za-z0-9_-]{1,50}" value="<?= htmlspecialchars($values['slug'] ?? '') ?>" placeholder="留空则使用版块名">
        <span class="form-label">slug（选填）</span>
        <div class="help-block">仅英文字母、数字、横线、下划线。留空则使用版块名。</div>
    </label>
    <label class="form-group">
        <textarea class="form-control" id="f-desc" name="description" rows="3" maxlength="200"><?= htmlspecialchars($values['description'] ?? '') ?></textarea>
        <span class="form-label">描述（选填）</span>
    </label>
    <button type="submit" class="btn btn-primary">创建版块</button>
    <a class="btn btn-link" href="<?= Router::url() ?>">取消</a>
</form>
