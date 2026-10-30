<?php defined('FLOO') or die('potato'); // NOOOO
/** @var string|null $error @var array $values */
?>
<div class="row justify-content-center">
	<div class="col-12 col-md-6 col-lg-5">
		<h1 class="h3 mb-4">注册</h1>

		<?php if (!empty($error)): ?>
		<div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
		<?php endif; ?>

		<form method="post" action="<?= Router::url('auth/register') ?>">
			<?= Auth::csrfField() ?>
			<div class="mb-3">
				<label class="form-label" for="username">用户名</label>
				<input class="form-control" type="text" id="username" name="username"
					value="<?= htmlspecialchars($values['username'] ?? '') ?>" required autofocus autocomplete="username">
				<div class="form-text">字母开头，仅字母/数字/下划线，3-24 位。</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="email">邮箱（选填）</label>
				<input class="form-control" type="email" id="email" name="email"
					value="<?= htmlspecialchars($values['email'] ?? '') ?>" autocomplete="email">
			</div>
			<div class="mb-3">
				<label class="form-label" for="password">密码</label>
				<input class="form-control" type="password" id="password" name="password" required autocomplete="new-password">
				<div class="form-text">至少 8 位，须同时包含字母和数字。</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="confirm">确认密码</label>
				<input class="form-control" type="password" id="confirm" name="confirm" required autocomplete="new-password">
			</div>
			<?= Captcha::createCaptcha() ?>
			<button class="btn btn-primary w-100" type="submit">注册</button>
		</form>

		<p class="mt-3 mb-0 text-body-secondary">
			已有账号？<a href="<?= Router::url('auth/login') ?>">登录</a>
		</p>
	</div>
</div>
