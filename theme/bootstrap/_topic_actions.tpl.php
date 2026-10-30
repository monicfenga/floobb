<?php defined('FLOO') or die('FlooBB'); ?>
<?php /* 局部片段：单主题行管理操作（须在 foreach 中以 $t 在场时 include），level≥2 可见 */ ?>
<?php if (Auth::level() >= 2): ?>
<div class="btn-group btn-group-sm" role="group" aria-label="管理操作">
	<form method="post" action="<?= Router::url('topic/lock/' . rawurlencode((string) $t['id'])) ?>" class="d-inline">
		<?= Auth::csrfField() ?>
		<button type="submit" class="btn btn-outline-secondary"><?= empty($t['locked']) ? '锁定' : '解锁' ?></button>
	</form>
	<form method="post" action="<?= Router::url('topic/sticky/' . rawurlencode((string) $t['id'])) ?>" class="d-inline">
		<?= Auth::csrfField() ?>
		<button type="submit" class="btn btn-outline-secondary"><?= empty($t['sticky']) ? '置顶' : '取消置顶' ?></button>
	</form>
	<form method="post" action="<?= Router::url('topic/move/' . rawurlencode((string) $t['id'])) ?>" class="input-group input-group-sm" style="width: 11rem;">
		<?= Auth::csrfField() ?>
		<select name="fid" class="form-select" aria-label="移动到版块" required>
			<?php foreach (($allForums ?? array()) as $fId => $fName): ?>
			<option value="<?= htmlspecialchars($fId) ?>" <?= $fId === (string) ($t['forum_id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($fName) ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="btn btn-outline-secondary">移动</button>
	</form>
	<form method="post" action="<?= Router::url('topic/delete/' . rawurlencode((string) $t['id'])) ?>" class="d-inline" onsubmit="return confirm('确定删除该主题及其全部帖子？');">
		<?= Auth::csrfField() ?>
		<button type="submit" class="btn btn-outline-danger">删除</button>
	</form>
</div>
<?php endif; ?>
