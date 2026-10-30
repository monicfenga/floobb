<?php defined('FLOO') or die('FlooBB');
/** @var string|null $error @var array $values */
?>
<nav aria-label="breadcrumb">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="<?= Router::url() ?>">首页</a></li>
		<li class="breadcrumb-item"><a href="<?= Router::url('pm/inbox') ?>">私信</a></li>
		<li class="breadcrumb-item active" aria-current="page">写私信</li>
	</ol>
</nav>

<h1 class="h3 mb-3">写私信</h1>

<?php if (!empty($error)): ?>
<div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Router::url('pm/send') ?>">
	<?= Auth::csrfField() ?>
	<div class="mb-3">
		<label for="pm-to" class="form-label">收件人用户名</label>
		<input type="text" class="form-control" id="pm-to" name="to" required
			value="<?= htmlspecialchars($values['to']) ?>" placeholder="登录用户名（非昵称）">
	</div>
	<div class="mb-3">
		<label for="pm-subject" class="form-label">标题</label>
		<input type="text" class="form-control" id="pm-subject" name="subject" required maxlength="120"
			value="<?= htmlspecialchars($values['subject']) ?>">
	</div>
	<div class="mb-3">
		<label for="pm-body" class="form-label">正文</label>
		<textarea class="form-control" id="pm-body" name="body" rows="6" required><?= htmlspecialchars($values['body']) ?></textarea>
		<div class="form-text">纯文本，不解析 Markdown。</div>
	</div>
	<button type="submit" class="btn btn-primary">发送</button>
</form>
