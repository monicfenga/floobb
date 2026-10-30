<?php defined('FLOO') or die('FlooBB');
/** @var array $user 用户关联数组 @var array $stats 主题/帖子计数 @var array $recentTopics @var array $recentPosts */
$nickname = (string) ($user['nickname'] ?? $user['username']);
$username = (string) $user['username'];
$levelNames = array(1 => '注册用户', 2 => '版主', 3 => '管理员');
?>
<div class="flex items-center gap-3 mb-3">
    <?php if (!empty($user['avatar'])): ?>
    <img class="rounded" width="64" height="64" src="<?= Router::url('uploads/avatars/' . rawurlencode((string) $user['avatar'])) ?>" alt="<?= htmlspecialchars($nickname) ?> 的头像">
    <?php endif; ?>
    <h1 class="mb-0"><?= htmlspecialchars($nickname) ?></h1>
    <?php if (User::isBanned($user)): $bForever = $user['banned_until'] >= User::BAN_FOREVER; ?>
    <span class="tag tag-error">已封禁<?= $bForever ? '（永久）' : '至 ' . date('Y-m-d H:i', $user['banned_until']) ?></span>
    <?php endif; ?>
</div>

<?php if ((string) ($user['bio'] ?? '') !== ''): ?>
<p class="muted mb-3"><?= htmlspecialchars((string) $user['bio']) ?></p>
<?php endif; ?>

<?php $viewer = Auth::user(); ?>
<?php if ($viewer !== null && $viewer['username'] !== $username): ?>
<p class="mb-3 flex gap-2">
    <a class="btn btn-ghost btn-sm" href="<?= Router::url('pm/compose') ?>?to=<?= rawurlencode($username) ?>">发私信</a>
    <?php $report_type = 'user'; $report_target = $username; $report_label = '举报此用户'; ?>
    <?php require __DIR__ . DS . '_report_btn.tpl.php'; ?>
</p>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <dl>
            <dt>用户名</dt><dd><code><?= htmlspecialchars($username) ?></code></dd>
            <dt>身份</dt><dd><?= htmlspecialchars($levelNames[$user['level'] ?? 1] ?? '注册用户') ?>（level <?= $user['level'] ?? 1 ?>）</dd>
            <dt>注册时间</dt><dd><?= date('Y-m-d H:i', $user['registered'] ?? 0) ?></dd>
            <dt>最近活跃</dt><dd><?= date('Y-m-d H:i', $user['last_seen'] ?? 0) ?></dd>
            <dt>发帖统计</dt><dd>主题 <?= (int) $stats['topics'] ?> · 帖子 <?= (int) $stats['posts'] ?></dd>
            <?php if ((string) ($user['signature'] ?? '') !== ''): ?>
            <dt>签名</dt><dd><?= htmlspecialchars((string) $user['signature']) ?></dd>
            <?php endif; ?>
            <?php if (User::isBanned($user)): ?>
            <dt>封禁状态</dt>
            <dd style="color:var(--accent)">
                封禁至 <?= $user['banned_until'] >= User::BAN_FOREVER ? '永久' : date('Y-m-d H:i', $user['banned_until']) ?>
                <?php if ((string) ($user['banned_by'] ?? '') !== ''): ?>· 操作：<?= htmlspecialchars((string) $user['banned_by']) ?><?php endif; ?>
                <?php if ((string) ($user['banned_reason'] ?? '') !== ''): ?>· 原因：<?= htmlspecialchars((string) $user['banned_reason']) ?><?php endif; ?>
            </dd>
            <?php endif; ?>
        </dl>
    </div>
</div>

<?php if ($viewer !== null && ($viewer['level'] ?? 1) >= 2 && $viewer['username'] !== $username): ?>
<div class="card mb-4">
    <div class="card-header">管理：封禁/解封</div>
    <div class="card-body">
        <?php if (User::isBanned($user)): ?>
        <p class="small muted">当前封禁至 <?= $user['banned_until'] >= User::BAN_FOREVER ? '永久' : date('Y-m-d H:i', $user['banned_until']) ?>。</p>
        <form method="post" action="<?= Router::url('user/ban/' . rawurlencode($username)) ?>" class="flex gap-2">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="duration" value="0">
            <button type="submit" class="btn btn-success btn-sm">立即解封</button>
        </form>
        <?php else: ?>
        <form method="post" action="<?= Router::url('user/ban/' . rawurlencode($username)) ?>" class="flex gap-2 wrap items-center">
            <?= Auth::csrfField() ?>
            <label class="flex items-center gap-1">
                <span class="form-label">时长</span>
                <select class="form-control" name="duration" required style="width:auto;">
                    <option value="1d">1 天</option>
                    <option value="7d">7 天</option>
                    <option value="30d">30 天</option>
                    <option value="permanent">永久</option>
                    <option value="custom">自定义（输入时间戳）</option>
                </select>
            </label>
            <label class="flex items-center gap-1">
                <span class="form-label">自定义解封时间</span>
                <input type="text" class="form-control" name="until" placeholder="留空则仅固定档位生效">
            </label>
            <label class="flex items-center gap-1">
                <span class="form-label">原因</span>
                <input type="text" class="form-control" name="reason" maxlength="200">
            </label>
            <button type="submit" class="btn btn-error btn-sm" onclick="return confirm('确定封禁该用户？将私信通知其封禁时长与原因。');">封禁</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<h2 class="small">近期主题</h2>
<?php if (empty($recentTopics)): ?>
<p class="muted small">暂无主题。</p>
<?php else: ?>
<ul class="list-group mb-4">
    <?php foreach ($recentTopics as $rt): ?>
    <li>
        <a href="<?= Router::url('topic/view/' . $rt['id']) ?>"><?= htmlspecialchars((string) ($rt['title'] ?? '')) ?></a>
        <small class="muted ms-1"><?= date('Y-m-d', $rt['created'] ?? 0) ?></small>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<h2 class="small">近期回帖</h2>
<?php if (empty($recentPosts)): ?>
<p class="muted small">暂无回帖。</p>
<?php else: ?>
<ul class="list-group mb-4">
    <?php foreach ($recentPosts as $row): $rp = $row['post']; ?>
    <li>
        <a href="<?= $row['url'] ?>">回帖：<?= htmlspecialchars($row['topic_title']) ?></a>
        <small class="muted ms-1"><?= date('Y-m-d', $rp['created'] ?? 0) ?></small>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
