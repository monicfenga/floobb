<?php defined('FLOO') or die('FlooBB');
/** @var array $user 当前登录用户 @var bool $saved @var array $errors @var array $values */ ?>
<h1 class="h3 mb-3">个人设置</h1>

<?php if ($saved): ?>
<div class="alert alert-success" role="alert">设置已保存。</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" role="alert">
	<ul class="mb-0">
		<?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<form method="post" action="<?= Router::url('user/settings') ?>" enctype="multipart/form-data">
	<?= Auth::csrfField() ?>

	<h2 class="h6 mt-2 mb-2">资料</h2>
	<div class="card mb-3"><div class="card-body">
		<div class="mb-3">
			<label for="set-nickname" class="form-label">昵称</label>
			<input type="text" class="form-control" id="set-nickname" name="nickname" required
				maxlength="30" value="<?= htmlspecialchars($values['nickname']) ?>">
			<div class="form-text">显示名，1-30 字符；用户名（登录标识）不可修改。</div>
		</div>
		<div class="mb-3">
			<label for="set-email" class="form-label">邮箱（选填）</label>
			<input type="email" class="form-control" id="set-email" name="email"
				value="<?= htmlspecialchars($values['email']) ?>">
			<div class="form-text">本站不发送邮件，仅作资料记录。</div>
		</div>
		<div class="mb-0">
			<label for="set-bio" class="form-label">一句话介绍</label>
			<input type="text" class="form-control" id="set-bio" name="bio" maxlength="200"
				value="<?= htmlspecialchars($values['bio']) ?>" placeholder="展示在帖子楼层与个人主页">
		</div>
	</div></div>

	<h2 class="h6 mb-2">头像</h2>
	<div class="card mb-3"><div class="card-body">
		<?php if (!empty($user['avatar'])): ?>
		<div class="d-flex align-items-center gap-2 mb-2">
			<img class="rounded" width="48" height="48"
				src="<?= Router::url('uploads/avatars/' . rawurlencode((string) $user['avatar'])) ?>"
				alt="当前头像">
			<div class="form-check mb-0">
				<input class="form-check-input" type="checkbox" id="set-remove-avatar" name="remove_avatar" value="1">
				<label class="form-check-label" for="set-remove-avatar">移除当前头像</label>
			</div>
		</div>
		<?php endif; ?>
		<label for="set-avatar" class="form-label">上传新头像</label>
		<input type="file" class="form-control" id="set-avatar" name="avatar" accept="image/*">
		<div class="form-text">jpg / png / gif / webp，最大 2MB。不选择则保留现状。</div>
	</div></div>

	<h2 class="h6 mb-2">修改密码（全部留空则不改）</h2>
	<div class="card mb-3"><div class="card-body">
		<div class="mb-3">
			<label for="set-oldpass" class="form-label">当前密码</label>
			<input type="password" class="form-control" id="set-oldpass" name="old_password" autocomplete="current-password">
		</div>
		<div class="mb-3">
			<label for="set-newpass" class="form-label">新密码</label>
			<input type="password" class="form-control" id="set-newpass" name="new_password" autocomplete="new-password">
			<div class="form-text">至少 8 位，同时包含字母与数字。修改后其他设备的记住登录将失效。</div>
		</div>
		<div class="mb-0">
			<label for="set-confirm" class="form-label">确认新密码</label>
			<input type="password" class="form-control" id="set-confirm" name="confirm_password" autocomplete="new-password">
		</div>
	</div></div>

	<button type="submit" class="btn btn-primary">保存设置</button>
</form>
