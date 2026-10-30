<?php defined('FLOO') or die('potato'); // NOOOO
/** @var string|null $error @var string $username */
?>
<div class="row justify-content-center">
	<div class="col-12 col-md-6 col-lg-5">
		<h1 class="h3 mb-2">重置密码</h1>
		<p class="text-body-secondary">输入注册时生成的恢复码（一次性）。恢复码用尽将无法自助重置，请妥善保存。</p>

		<?php if (!empty($error)): ?>
		<div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
		<?php endif; ?>

		<form method="post" action="<?= Router::url('auth/reset') ?>">
			<?= Auth::csrfField() ?>
			<div class="mb-3">
				<label class="form-label" for="username">用户名</label>
				<input class="form-control" type="text" id="username" name="username"
					value="<?= htmlspecialchars($username ?? '') ?>" required autofocus autocomplete="username">
			</div>
			<div class="mb-3">
				<label class="form-label" for="code">恢复码</label>
				<input class="form-control" type="text" id="code" name="code" required autocomplete="off">
			</div>
			<div class="mb-3">
				<label class="form-label" for="password">新密码</label>
				<input class="form-control" type="password" id="password" name="password" required autocomplete="new-password">
				<div class="form-text">至少 8 位，须同时包含字母和数字。</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="confirm">确认新密码</label>
				<input class="form-control" type="password" id="confirm" name="confirm" required autocomplete="new-password">
			</div>
			<button class="btn btn-primary w-100" type="submit">重置</button>
		</form>

		<p class="mt-3 mb-0 text-body-secondary"><a href="<?= Router::url('auth/login') ?>">返回登录</a></p>
	</div>
</div>
