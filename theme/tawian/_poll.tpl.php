<?php defined('FLOO') or die('FlooBB');
/** @var array|null $pollView 组装好的投票视图数据（topic.controller view()）*/
if (empty($pollView)) return;
$poll = $pollView['poll'];
$isMulti = ($poll['max_choices'] ?? 1) > 1;
?>
<div class="card mb-4 poll-card">
    <div class="card-header flex between items-center wrap gap-2">
        <strong><?= htmlspecialchars((string) ($poll['question'] ?? '')) ?></strong>
        <span class="muted small">
            <?= $isMulti ? '多选（最多 ' . $poll['max_choices'] . ' 项）' : '单选' ?>
            · 截止 <?= date('Y-m-d H:i', $poll['deadline'] ?? 0) ?>
            <?php if (!empty($poll['closed'])): ?>· 已关闭<?php elseif ($pollView['expired']): ?>· 已过期<?php endif; ?>
        </span>
    </div>
    <div class="card-body">
        <?php if (!$pollView['show_result']): ?>
        <?php /* 先投后见（用户决议）：未投者只见表单，不得出现任何票数/百分比 */ ?>
        <?php $viewer = Auth::user(); ?>
        <?php if ($viewer !== null): ?>
        <form method="post" action="<?= Router::url('poll/vote/' . rawurlencode((string) ($topic['id'] ?? ''))) ?>">
            <?= Auth::csrfField() ?>
            <?php foreach ($pollView['options'] as $opt): ?>
            <label class="flex items-center gap-2">
                <input type="<?= $isMulti ? 'checkbox' : 'radio' ?>" name="choices[]" value="<?= $opt['index'] ?>">
                <?= htmlspecialchars($opt['text']) ?>
            </label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary btn-sm mt-2">投票</button>
            <?php if ($isMulti): ?><div class="help-block">可选 <?= $poll['max_choices'] ?> 项。</div><?php endif; ?>
        </form>
        <?php else: ?>
        <p class="muted mb-0">请先 <a href="<?= Router::url('auth/login') ?>">登录</a> 后参与投票（投票后可见结果）。</p>
        <?php endif; ?>
        <?php else: ?>
        <?php /* 已投 / 已关闭 / 已过期 → 结果视图 */ ?>
        <?php foreach ($pollView['options'] as $opt): ?>
        <div class="mb-2">
            <div class="flex between small">
                <span><?= $opt['checked'] ? '<span class="tag tag-success me-1">已投</span>' : '' ?><?= htmlspecialchars($opt['text']) ?></span>
                <span class="muted"><?= (int) $opt['votes'] ?> 票（<?= (int) $opt['pct'] ?>%）</span>
            </div>
            <div class="progress-bar<?= $opt['checked'] ? ' progress-success' : '' ?>">
                <div style="width: <?= (int) $opt['pct'] ?>%;"></div>
            </div>
        </div>
        <?php endforeach; ?>
        <p class="muted small mb-2">共 <?= (int) $pollView['voters_total'] ?> 人参与（<?= (int) $pollView['total'] ?> 票）</p>
        <?php if (!empty($pollView['voters'])): ?>
        <details>
            <summary class="small muted">投票人名单</summary>
            <p class="small mb-0 mt-1">
                <?php foreach ($pollView['voters'] as $i => $v): ?><?= $i > 0 ? '、' : '' ?><a href="<?= Router::url('user/view/' . rawurlencode($v['username'])) ?>"><?= htmlspecialchars($v['nickname']) ?></a><?php endforeach; ?>
            </p>
        </details>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php if (!empty($pollView['can_close'])): ?>
    <div class="card-footer">
        <form method="post" action="<?= Router::url('poll/close/' . rawurlencode((string) ($topic['id'] ?? ''))) ?>" onsubmit="return confirm('确定关闭该投票？关闭后不能再投。');">
            <?= Auth::csrfField() ?>
            <button type="submit" class="btn btn-error btn-ghost btn-sm">关闭投票</button>
        </form>
    </div>
    <?php endif; ?>
</div>
