<?php defined('FLOO') or die('FlooBB');
/** @var string|null $error @var array $values */
?>
<div class="container" style="max-width:30rem;">
    <h1 class="mb-4">注册</h1>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= Router::url('auth/register') ?>">
        <?= Auth::csrfField() ?>
        <label class="form-group">
            <input class="form-control" type="text" id="username" name="username" value="<?= htmlspecialchars($values['username'] ?? '') ?>" required autofocus autocomplete="username">
            <span class="form-label">用户名</span>
            <div class="help-block">字母开头，仅字母/数字/下划线，3-24 位。</div>
        </label>
        <label class="form-group">
            <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($values['email'] ?? '') ?>" autocomplete="email">
            <span class="form-label">邮箱（选填）</span>
        </label>
        <label class="form-group">
            <input class="form-control" type="password" id="password" name="password" required autocomplete="new-password">
            <span class="form-label">密码</span>
            <div class="help-block">至少 8 位，须同时包含字母和数字。</div>
        </label>
        <label class="form-group">
            <input class="form-control" type="password" id="confirm" name="confirm" required autocomplete="new-password">
            <span class="form-label">确认密码</span>
        </label>
        <?= Captcha::createCaptcha() ?>
        <button class="btn btn-primary w-100" type="submit">注册</button>
    </form>

    <p class="mt-3 mb-0 muted">
        已有账号？<a href="<?= Router::url('auth/login') ?>">登录</a>
    </p>
</div>
