<?php defined('FLOO') or die('potato'); // NOOOO
/** @var array $user 用户关联数组 @var array $stats 主题/帖子计数 @var array $recentTopics @var array $recentPosts */
$nickname = (string) ($user['nickname'] ?? $user['username']);
$username = (string) $user['username'];
$levelNames = array(1 => '注册用户', 2 => '版主', 3 => '管理员');
?>
<div class="d-flex align-items-center gap-3 mb-3">
	<?php if (!empty($user['avatar'])): ?>
	<img class="rounded" width="64" height="64"
		src="<?= Router::url('uploads/avatars/' . rawurlencode((string) $user['avatar'])) ?>"
		alt="<?= htmlspecialchars($nickname) ?> 的头像">
	<?php endif; ?>
	<h1 class="h3 mb-0"><?= htmlspecialchars($nickname) ?></h1>
</div>

<?php if ((string) ($user['bio'] ?? '') !== ''): ?>
<p class="text-body-secondary mb-3"><?= htmlspecialchars((string) $user['bio']) ?></p>
<?php endif; ?>

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

<h2 class="h6">近期主题</h2>
<?php if (empty($recentTopics)): ?>
<p class="text-body-secondary small">暂无主题。</p>
<?php else: ?>
<ul class="list-group list-group-flush mb-4">
	<?php foreach ($recentTopics as $rt): ?>
	<li class="list-group-item px-0 py-2">
		<a href="<?= Router::url('topic/view/' . rawurlencode((string) ($rt['id'] ?? ''))) ?>"><?= htmlspecialchars((string) ($rt['title'] ?? '')) ?></a>
		<small class="text-body-secondary ms-1"><?= date('Y-m-d', strtotime((string) ($rt['created'] ?? ''))) ?></small>
	</li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>

<h2 class="h6">近期回帖</h2>
<?php if (empty($recentPosts)): ?>
<p class="text-body-secondary small">暂无回帖。</p>
<?php else: ?>
<ul class="list-group list-group-flush mb-4">
	<?php foreach ($recentPosts as $row): $rp = $row['post']; ?>
	<li class="list-group-item px-0 py-2">
		<a href="<?= $row['url'] ?>">回帖：<?= htmlspecialchars($row['topic_title']) ?></a>
		<small class="text-body-secondary ms-1"><?= date('Y-m-d', strtotime((string) ($rp['created'] ?? ''))) ?></small>
	</li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>
