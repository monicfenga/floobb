<?php defined('FLOO') or die('FlooBB'); ?>
<h1 class="mb-4">新建主题</h1>

<?php if (empty($forums)): ?>
<div class="alert">还没有版块，无法发主题。</div>
<?php else: ?>

<?php if (!empty($error)): ?>
<div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Router::url('topic/create') ?>" class="form" style="max-width:48rem;">
    <?= Auth::csrfField() ?>
    <label class="form-group">
        <select class="form-control" id="t-forum" name="fid" required>
            <?php foreach ($forums as $f): ?>
            <option value="<?= $f['id'] ?>" <?= (string) ($values['fid'] ?? '') === $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name'] ?? '') ?></option>
            <?php endforeach; ?>
        </select>
        <span class="form-label">版块</span>
    </label>
    <label class="form-group">
        <input type="text" class="form-control" id="t-title" name="title" maxlength="120" required value="<?= htmlspecialchars($values['title'] ?? '') ?>">
        <span class="form-label">标题</span>
    </label>
    <label class="form-group">
        <textarea class="form-control" id="t-content" name="content" rows="10" required><?= htmlspecialchars($values['content'] ?? '') ?></textarea>
        <span class="form-label">正文（Markdown）</span>
        <div class="help-block">支持 Markdown；预览仅供参考，以服务端渲染为准。</div>
    </label>
    <div class="mb-3">
        <span class="form-label d-block">预览</span>
        <div class="card"><div class="card-body post-content" id="preview" aria-live="polite"></div></div>
    </div>

    <?php /* 附带投票（可选；建票只在发主题时一笔提交） */ ?>
    <div class="card mb-3">
        <div class="card-header">
            <label class="flex items-center gap-2">
                <input type="checkbox" id="t-isvote" name="is_vote" value="1" <?= ($values['is_vote'] ?? '') === '1' ? 'checked' : '' ?> onchange="document.getElementById('poll-fields').hidden = !this.checked;">
                附带投票
            </label>
        </div>
        <div class="card-body" id="poll-fields" <?= ($values['is_vote'] ?? '') === '1' ? '' : 'hidden' ?>>
            <label class="form-group">
                <input type="text" class="form-control" id="t-poll-q" name="poll_question" maxlength="120" value="<?= htmlspecialchars($values['poll_question'] ?? '') ?>">
                <span class="form-label">投票问题</span>
            </label>
            <label class="form-group">
                <textarea class="form-control" id="t-poll-opts" name="poll_options" rows="4"><?= htmlspecialchars($values['poll_options'] ?? '') ?></textarea>
                <span class="form-label">投票选项</span>
                <div class="help-block">（每行一个，忽略空行和重复选项）</div>
            </label>
            <div class="flex gap-3 wrap form-vertical">
                <div>
                    <span class="form-label d-block">类型</span>
                    <label class="flex items-center gap-1"><input type="radio" name="poll_mode" value="single" <?= ($values['poll_mode'] ?? 'single') !== 'multi' ? 'checked' : '' ?>> 单选</label>
                    <label class="flex items-center gap-1"><input type="radio" name="poll_mode" value="multi" <?= ($values['poll_mode'] ?? '') === 'multi' ? 'checked' : '' ?>> 多选</label>
                </div>
                <label class="form-group">
                    <input type="number" class="form-control" id="t-poll-max" name="poll_max" min="2" max="8" value="<?= (int) ($values['poll_max'] ?? 2) ?>" style="width:5rem;">
                    <span class="form-label">最多可选（多选时生效）</span>
                </label>
                <label class="form-group">
                    <input type="number" class="form-control" id="t-poll-days" name="poll_days" min="1" max="365" value="<?= (int) ($values['poll_days'] ?? 7) ?>" style="width:5rem;">
                    <span class="form-label">投票将在几天后结束（1-365）</span>
                </label>
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
