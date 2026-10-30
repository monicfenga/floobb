<?php defined('FLOO') or die('FlooBB'); ?>
<h1 class="h3 mb-4">新建版块</h1>

<?php if (!empty($error)): ?>
<div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Router::url('forum/add') ?>" class="col-md-6">
	<?= Auth::csrfField() ?>
	<div class="mb-3">
		<label for="f-name" class="form-label">版块名</label>
		<input type="text" class="form-control" id="f-name" name="name" maxlength="50" required
			value="<?= htmlspecialchars($values['name'] ?? '') ?>">
	</div>
	<div class="mb-3">
		<label for="f-desc" class="form-label">描述（选填）</label>
		<textarea class="form-control" id="f-desc" name="description" rows="3" maxlength="200"><?= htmlspecialchars($values['description'] ?? '') ?></textarea>
	</div>
	<button type="submit" class="btn btn-primary">创建版块</button>
	<a class="btn btn-link" href="<?= Router::url() ?>">取消</a>
</form>
