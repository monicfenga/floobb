<?php defined('FLOO') or die('FlooBB'); 
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
<?php if (User::isBanned($user)): $bForever = (string) $user['banned_until'] === User::BAN_FOREVER; ?>
<span class="badge text-bg-danger">已封禁<?= $bForever ? '（永久）' : '至 ' . htmlspecialchars((string) $user['banned_until']) ?></span>
<?php endif; ?>
</div>

<?php if ((string) ($user['bio'] ?? '') !== ''): ?>
<p class="text-body-secondary mb-3"><?= htmlspecialchars((string) $user['bio']) ?></p>
<?php endif; ?>

<?php $viewer = Auth::user(); ?>
<?php if ($viewer !== null && $viewer['username'] !== $username): ?>
<p class="mb-3 d-flex gap-2">
	<a class="btn btn-sm btn-outline-primary" href="<?= Router::url('pm/compose') ?>?to=<?= rawurlencode($username) ?>">发私信</a>
	<?php $report_type = 'user'; $report_target = $username; $report_label = '举报此用户'; ?>
	<?php require __DIR__ . DS . '_report_btn.tpl.php'; ?>
</p>
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
			<?php if (User::isBanned($user)): ?>
			<dt class="col-sm-3">封禁状态</dt>
			<dd class="col-sm-9 text-danger">
				封禁至 <?= htmlspecialchars((string) $user['banned_until'] === User::BAN_FOREVER ? '永久' : (string) $user['banned_until']) ?>
				<?php if ((string) ($user['banned_by'] ?? '') !== ''): ?>· 操作：<?= htmlspecialchars((string) $user['banned_by']) ?><?php endif; ?>
				<?php if ((string) ($user['banned_reason'] ?? '') !== ''): ?>· 原因：<?= htmlspecialchars((string) $user['banned_reason']) ?><?php endif; ?>
			</dd>
			<?php endif; ?>
		</dl>
	</div>
</div>

<?php if ($viewer !== null && (int) ($viewer['level'] ?? 1) >= 2 && $viewer['username'] !== $username): ?>
<div class="card mb-4 border-warning">
	<div class="card-header bg-warning-subtle">管理：封禁/解封</div>
	<div class="card-body">
		<?php if (User::isBanned($user)): ?>
		<p class="small text-body-secondary">当前封禁至 <?= htmlspecialchars((string) $user['banned_until'] === User::BAN_FOREVER ? '永久' : (string) $user['banned_until']) ?>。</p>
		<form method="post" action="<?= Router::url('user/ban/' . rawurlencode($username)) ?>" class="d-flex gap-2">
			<?= Auth::csrfField() ?>
			<input type="hidden" name="duration" value="0">
			<button type="submit" class="btn btn-sm btn-success">立即解封</button>
		</form>
		<?php else: ?>
		<form method="post" action="<?= Router::url('user/ban/' . rawurlencode($username)) ?>" class="row g-2">
			<?= Auth::csrfField() ?>
			<div class="col-auto">
				<label class="form-label small mb-0" for="ban-duration">时长</label>
				<select class="form-select form-select-sm" id="ban-duration" name="duration" required>
					<option value="1d">1 天</option>
					<option value="7d">7 天</option>
					<option value="30d">30 天</option>
					<option value="permanent">永久</option>
					<option value="custom">自定义（输入时间戳）</option>
				</select>
			</div>
			<div class="col-auto">
				<label class="form-label small mb-0" for="ban-until">自定义解封时间（ISO，如 2027-01-01T00:00:00）</label>
				<input type="text" class="form-control form-control-sm" id="ban-until" name="until" placeholder="留空则仅固定档位生效">
			</div>
			<div class="col-auto">
				<label class="form-label small mb-0" for="ban-reason">原因</label>
				<input type="text" class="form-control form-control-sm" id="ban-reason" name="reason" maxlength="200">
			</div>
			<div class="col-auto align-self-end">
				<button type="submit" class="btn btn-sm btn-danger"
					onclick="return confirm('确定封禁该用户？将私信通知其封禁时长与原因。');">封禁</button>
			</div>
		</form>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

<h2 class="h6">近期主题</h2>
<?php if (empty($recentTopics)): ?>
<p class="text-body-secondary small">暂无主题。</p>
<?php else: ?>
<ul class="list-group list-group-flush mb-4">
	<?php foreach ($recentTopics as $rt): ?>
	<li class="list-group-item px-0 py-2">
		<a href="<?= Router::url('topic/view/' . $rt['id']) ?>"><?= htmlspecialchars((string) ($rt['title'] ?? '')) ?></a>
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
