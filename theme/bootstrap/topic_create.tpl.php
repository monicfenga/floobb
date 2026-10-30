<?php defined('FLOO') or die('FlooBB'); ?>
<h1 class="h3 mb-4">新建主题</h1>

<?php if (empty($forums)): ?>
<div class="alert alert-info" role="status">还没有版块，无法发主题。</div>
<?php else: ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Router::url('topic/create') ?>" class="col-md-8">
	<?= Auth::csrfField() ?>
	<div class="mb-3">
		<label for="t-forum" class="form-label">版块</label>
		<select class="form-select" id="t-forum" name="fid" required>
			<?php foreach ($forums as $f): ?>
			<option value="<?= $f['id'] ?>" <?= (string) ($values['fid'] ?? '') === $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name'] ?? '') ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="mb-3">
		<label for="t-title" class="form-label">标题</label>
		<input type="text" class="form-control" id="t-title" name="title" maxlength="120" required
			value="<?= htmlspecialchars($values['title'] ?? '') ?>">
	</div>
	<div class="mb-3">
		<label for="t-content" class="form-label">正文（Markdown）</label>
		<textarea class="form-control font-monospace" id="t-content" name="content" rows="10" required><?= htmlspecialchars($values['content'] ?? '') ?></textarea>
		<div class="form-text">支持 Markdown；预览仅供参考，以服务端渲染为准。</div>
	</div>
	<div class="mb-3">
		<span id="previewHelp" class="form-label d-block">预览</span>
		<div class="border rounded p-3 post-content" id="preview" aria-describedby="previewHelp" aria-live="polite"></div>
	</div>

	<?php /* 附带投票（可选；建票只在发主题时一笔提交） */ ?>
	<div class="card mb-3">
		<div class="card-header">
			<div class="form-check mb-0">
				<input class="form-check-input" type="checkbox" id="t-isvote" name="is_vote" value="1"
					<?= ($values['is_vote'] ?? '') === '1' ? 'checked' : '' ?>
					onchange="document.getElementById('poll-fields').hidden = !this.checked;">
				<label class="form-check-label" for="t-isvote">附带投票</label>
			</div>
		</div>
		<div class="card-body" id="poll-fields" <?= ($values['is_vote'] ?? '') === '1' ? '' : 'hidden' ?>>
			<div class="mb-3">
				<label for="t-poll-q" class="form-label">投票问题</label>
				<input type="text" class="form-control" id="t-poll-q" name="poll_question" maxlength="120"
					value="<?= htmlspecialchars($values['poll_question'] ?? '') ?>">
			</div>
			<div class="mb-3">
				<label for="t-poll-opts" class="form-label">投票选项（每行一个，忽略空行和重复选项）</label>
				<textarea class="form-control" id="t-poll-opts" name="poll_options" rows="4"><?= htmlspecialchars($values['poll_options'] ?? '') ?></textarea>
			</div>
			<div class="row g-3">
				<div class="col-sm-4">
					<label class="form-label d-block">类型</label>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="poll_mode" id="t-poll-single" value="single"
							<?= ($values['poll_mode'] ?? 'single') !== 'multi' ? 'checked' : '' ?>>
						<label class="form-check-label" for="t-poll-single">单选</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="poll_mode" id="t-poll-multi" value="multi"
							<?= ($values['poll_mode'] ?? '') === 'multi' ? 'checked' : '' ?>>
						<label class="form-check-label" for="t-poll-multi">多选</label>
					</div>
				</div>
				<div class="col-sm-4">
					<label for="t-poll-max" class="form-label">最多可选</label>
					<input type="number" class="form-control" id="t-poll-max" name="poll_max" min="2" max="8"
						value="<?= (int) ($values['poll_max'] ?? 2) ?>">
						<span class="form-text">若投票类型为多选时，本项生效</span>
				</div>
				<div class="col-sm-4">
					<label for="t-poll-days" class="form-label">投票将在几天后结束（从今天算起，1-365 天）</label>
					<input type="number" class="form-control" id="t-poll-days" name="poll_days" min="1" max="365"
						value="<?= (int) ($values['poll_days'] ?? 7) ?>">
				</div>
			</div>
		</div>
	</div>

	<button type="submit" class="btn btn-primary">发布主题</button>
</form>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/google-code-prettify@1.0.5/bin/prettify.min.css">
<script src="https://cdn.jsdelivr.net/npm/marked@12/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var md = document.getElementById('t-content');
	var pv = document.getElementById('preview');
	function upd() { pv.innerHTML = DOMPurify.sanitize(marked.parse(md.value)); }
	md.addEventListener('input', upd);
	upd();
	if (window.PR) PR.prettyPrint();
});
</script>
<?php endif; ?>
