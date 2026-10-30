<?php defined('FLOO') or die('FlooBB'); ?>
<div class="row justify-content-center">
	<div class="col-12 col-md-6 col-lg-5">
		<h1 class="h3 mb-4">登录</h1>

		<?php if (!empty($success)): ?>
		<div class="alert alert-success" role="status"><?= htmlspecialchars($success) ?></div>
		<?php endif; ?>
		<?php if (!empty($error)): ?>
		<div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
		<?php endif; ?>

		<form method="post" action="<?= Router::url('auth/login') ?>">
			<?= Auth::csrfField() ?>
			<div class="mb-3">
				<label class="form-label" for="username">用户名</label>
				<input class="form-control" type="text" id="username" name="username"
					value="<?= htmlspecialchars($username ?? '') ?>" required autofocus autocomplete="username">
			</div>
			<div class="mb-3">
				<label class="form-label" for="password">密码</label>
				<input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
			</div>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
				<label class="form-check-label" for="remember">记住我（14 天）</label>
			</div>
			<button class="btn btn-primary w-100" type="submit">登录</button>
		</form>

		<p class="mt-3 mb-0 text-body-secondary">
			还没有账号？<a href="<?= Router::url('auth/register') ?>">注册</a> ·
			<a href="<?= Router::url('auth/reset') ?>">用恢复码重置密码</a>
		</p>
	</div>
</div>
