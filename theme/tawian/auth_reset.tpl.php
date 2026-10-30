<?php defined('FLOO') or die('FlooBB');
/** @var string|null $error @var string $username */
?>
<div class="container" style="max-width:30rem;">
    <h1 class="mb-2">重置密码</h1>
    <p class="muted">输入注册时生成的恢复码（一次性）。恢复码用尽将无法自助重置，请妥善保存。</p>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= Router::url('auth/reset') ?>">
        <?= Auth::csrfField() ?>
        <label class="form-group">
            <input class="form-control" type="text" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required autofocus autocomplete="username">
            <span class="form-label">用户名</span>
        </label>
        <label class="form-group">
            <input class="form-control" type="text" id="code" name="code" required autocomplete="off">
            <span class="form-label">恢复码</span>
        </label>
        <label class="form-group">
            <input class="form-control" type="password" id="password" name="password" required autocomplete="new-password">
            <span class="form-label">新密码</span>
            <div class="help-block">至少 8 位，须同时包含字母和数字。</div>
        </label>
        <label class="form-group">
            <input class="form-control" type="password" id="confirm" name="confirm" required autocomplete="new-password">
            <span class="form-label">确认新密码</span>
        </label>
        <button class="btn btn-primary w-100" type="submit">重置</button>
    </form>

    <p class="mt-3 mb-0 muted"><a href="<?= Router::url('auth/login') ?>">返回登录</a></p>
</div>
