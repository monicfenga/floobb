<?php defined('FLOO') or die('FlooBB'); 
/** @var array $codes 明文恢复码，仅此一次展示 @var string $username */
?>
<div class="row justify-content-center">
	<div class="col-12 col-md-8 col-lg-6">
		<h1 class="h3 mb-2">注册成功，欢迎 <?= htmlspecialchars($username) ?>！</h1>
		<p class="text-body-secondary">你已自动登录。下面是你的<b>账户恢复码</b>——忘记密码且无法登录时的唯一自助重置手段（本站不发邮件）。</p>

		<div class="alert alert-warning" role="alert">
			<strong>请立即保存：</strong>每个恢复码只能使用一次，且此后不再显示。
		</div>

		<div class="card mb-4">
			<div class="card-body">
				<ul class="list-unstyled mb-0 font-monospace fs-5" id="recovery-codes">
					<?php foreach ($codes as $code): ?>
					<li><code><?= htmlspecialchars($code) ?></code></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<a class="btn btn-primary" href="<?= Router::url() ?>">返回首页</a>
	</div>
</div>
