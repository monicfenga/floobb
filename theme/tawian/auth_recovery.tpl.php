<?php defined('FLOO') or die('FlooBB');
/** @var array $codes 明文恢复码，仅此一次展示 @var string $username */
?>
<div class="container" style="max-width:40rem;">
    <h1 class="mb-2">注册成功，欢迎 <?= htmlspecialchars($username) ?>！</h1>
    <p class="muted">你已自动登录。下面是你的<b>账户恢复码</b>——忘记密码且无法登录时的唯一自助重置手段（本站不发邮件）。</p>

    <div class="alert alert-warning" role="alert">
        <strong>请立即保存：</strong>每个恢复码只能使用一次，且此后不再显示。
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <ul class="m-0 list-plain" id="recovery-codes" style="list-style:none;padding:0;font-size:1.1rem;line-height:1.9;">
                <?php foreach ($codes as $code): ?>
                <li><code><?= htmlspecialchars($code) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <a class="btn btn-primary" href="<?= Router::url() ?>">返回首页</a>
</div>
