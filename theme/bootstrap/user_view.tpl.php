<?php defined('FLOO') or die('potato'); // NOOOO
/** @var array $user 用户关联数组 @var array $stats 主题/帖子计数 */
$nickname = (string) ($user['nickname'] ?? $user['username']);
$username = (string) $user['username'];
$levelNames = array(1 => '注册用户', 2 => '版主', 3 => '管理员');
?>
<h1 class="h3 mb-3"><?= htmlspecialchars($nickname) ?></h1>

<div class="card mb-4">
	<div class="card-body">
		<dl class="row mb-0">
			<dt class="col-sm-3">用户名</dt>
			<dd class="col-sm-9"><code><?= htmlspecialchars($username) ?></code></dd>
			<dt class="col-sm-3">身份</dt>
			<dd class="col-sm-9"><?= htmlspecialchars($levelNames[(int) ($user['level'] ?? 1)] ?? '注册用户') ?>（level <?= (int) ($user['level'] ?? 1) ?>）</dd>
			<dt class="col-sm-3">注册时间</dt>
			<dd class="col-sm-9"><?= htmlspecialchars((string) ($user['registered'] ?? '')) ?></dd>
			<dt class="col-sm-3">最近活跃</dt>
			<dd class="col-sm-9"><?= htmlspecialchars((string) ($user['last_seen'] ?? '')) ?></dd>
			<dt class="col-sm-3">发帖统计</dt>
			<dd class="col-sm-9">主题 <?= (int) $stats['topics'] ?> · 帖子 <?= (int) $stats['posts'] ?></dd>
			<?php if ((string) ($user['signature'] ?? '') !== ''): ?>
			<dt class="col-sm-3">签名</dt>
			<dd class="col-sm-9"><?= htmlspecialchars((string) $user['signature']) ?></dd>
			<?php endif; ?>
		</dl>
	</div>
</div>
