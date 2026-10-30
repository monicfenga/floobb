<?php defined('FLOO') or die('FlooBB'); ?>
<?php /* 局部片段：单主题行管理操作（须在 foreach 中以 $t 在场时 include），level≥2 可见 */ ?>
<?php if (Auth::level() >= 2): ?>
<div class="btn-group" role="group" aria-label="管理操作">
    <form method="post" action="<?= Router::url('topic/lock/' . $t['id']) ?>" class="inline">
        <?= Auth::csrfField() ?>
        <button type="submit" class="btn btn-ghost btn-sm"><?= empty($t['locked']) ? '锁定' : '解锁' ?></button>
    </form>
    <form method="post" action="<?= Router::url('topic/sticky/' . $t['id']) ?>" class="inline">
        <?= Auth::csrfField() ?>
        <button type="submit" class="btn btn-ghost btn-sm"><?= empty($t['sticky']) ? '置顶' : '取消置顶' ?></button>
    </form>
    <form method="post" action="<?= Router::url('topic/move/' . $t['id']) ?>" class="inline">
        <?= Auth::csrfField() ?>
        <select name="fid" aria-label="移动到版块" required>
            <?php foreach (($allForums ?? array()) as $fId => $fName): ?>
            <option value="<?= htmlspecialchars($fId) ?>" <?= $fId === (string) ($t['forum_id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($fName) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ghost btn-sm">移动</button>
    </form>
    <form method="post" action="<?= Router::url('topic/delete/' . $t['id']) ?>" class="inline" onsubmit="return confirm('确定删除该主题及其全部帖子？');">
        <?= Auth::csrfField() ?>
        <button type="submit" class="btn btn-error btn-ghost btn-sm">删除</button>
    </form>
</div>
<?php endif; ?>
