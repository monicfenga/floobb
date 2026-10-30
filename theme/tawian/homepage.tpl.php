<?php defined('FLOO') or die('FlooBB'); ?>
<?php /* Discourse 风双栏：左最新主题 / 右版块列表（tawian 版） */ ?>
<div class="grid">
    <div class="cell">
        <h1 class="mb-3">最新主题</h1>
        <?php if (empty($latestTopics)): ?>
        <div class="alert">还没有主题。</div>
        <?php else: ?>
        <ul class="list-group">
            <?php foreach ($latestTopics as $row): $t = $row['topic']; ?>
            <li>
                <div>
                    <?php if (!empty($t['sticky'])): ?><span class="tag tag-warning me-1">置顶</span><?php endif; ?>
                    <?php if (!empty($t['locked'])): ?><span class="tag me-1">已锁定</span><?php endif; ?>
                    <a href="<?= Router::url('topic/view/' . $t['id']) ?>"><?= htmlspecialchars($t['title'] ?? '') ?></a>
                </div>
                <small class="muted">
                    <?php if (($row['forum_name'] ?? '') !== ''): ?><a href="<?= Router::url('forum/view/' . rawurlencode($row['forum_id'])) ?>"><?= htmlspecialchars($row['forum_name']) ?></a> · <?php endif; ?>
                    <?= htmlspecialchars($t['author'] ?? '') ?> · <?= date('Y-m-d H:i', ($t['last_reply_at'] ?? 0) ?: ($t['created'] ?? 0)) ?> · <?= count((array) ($t['post_ids'] ?? array())) ?> 帖
                </small>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <div class="cell">
        <div class="flex between items-center mb-3">
            <h1 class="mb-0">版块</h1>
            <?php if (Auth::level() >= 3): ?>
            <a class="btn btn-primary" href="<?= Router::url('forum/add') ?>">新建版块</a>
            <?php endif; ?>
        </div>
        <?php if (empty($forums)): ?>
        <div class="alert">还没有版块。安装管理员登录后可在 Phase 1 创建第一个版块。</div>
        <?php else: ?>
        <ul class="list-group">
            <?php foreach ($forums as $forum): ?>
            <li>
                <a href="<?= Router::url('forum/view/' . $forum['id']) ?>">
                    <div class="flex between items-baseline">
                        <span><strong><?= htmlspecialchars($forum['name'] ?? '') ?></strong></span>
                        <small class="muted"><?= (int) ($forum['topic_count'] ?? 0) ?> 主题 · <?= (int) ($forum['post_count'] ?? 0) ?> 帖</small>
                    </div>
                    <div class="muted small">
                        <?= htmlspecialchars($forum['description'] ?? '') ?>
                        <?php if (!empty($forum['last_active'])): ?>
                        <span class="ms-1">· 最后活跃 <?= date('m-d H:i', $forum['last_active']) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
