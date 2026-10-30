<?php defined('FLOO') or die('FlooBB');
/** @var string|null $error @var array $values */
?>
<nav class="breadcrumb">
    <li><a href="<?= Router::url() ?>">首页</a></li>
    <li><a href="<?= Router::url('pm/inbox') ?>">私信</a></li>
    <li class="active">写私信</li>
</nav>

<h1 class="mb-3">写私信</h1>

<?php if (!empty($error)): ?>
<div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Router::url('pm/send') ?>" class="form" style="max-width:40rem;">
    <?= Auth::csrfField() ?>
    <label class="form-group">
        <input type="text" class="form-control" id="pm-to" name="to" required value="<?= htmlspecialchars($values['to']) ?>" placeholder="登录用户名（非昵称）">
        <span class="form-label">收件人用户名</span>
    </label>
    <label class="form-group">
        <input type="text" class="form-control" id="pm-subject" name="subject" required maxlength="120" value="<?= htmlspecialchars($values['subject']) ?>">
        <span class="form-label">标题</span>
    </label>
    <label class="form-group">
        <textarea class="form-control" id="pm-body" name="body" rows="6" required><?= htmlspecialchars($values['body']) ?></textarea>
        <span class="form-label">正文</span>
        <div class="help-block">纯文本，不解析 Markdown。</div>
    </label>
    <button type="submit" class="btn btn-primary">发送</button>
</form>
