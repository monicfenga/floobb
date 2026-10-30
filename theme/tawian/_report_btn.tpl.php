<?php defined('FLOO') or die('FlooBB');
/**
 * 举报按钮 + dialog 弹窗表单（modal.js data-target 触发）
 * 调用处须先备好：$report_type ('post'|'topic'|'user')、$report_target（id 或用户名）、$report_label（按钮文字，缺省「举报」）
 */
if (!isset($report_type, $report_target)) return;
$viewer = Auth::user();
if ($viewer === null) return; // 未登录不出举报入口
$dialogId = 'report-modal-' . md5((string) $report_type . ':' . (string) $report_target);
?>
<button type="button" class="btn btn-link btn-sm" data-target="<?= $dialogId ?>"><?= htmlspecialchars($report_label ?? '举报') ?></button>
<dialog id="<?= $dialogId ?>" aria-label="举报">
    <article>
        <header>
            <h5><?= htmlspecialchars($report_label ?? '举报') ?></h5>
            <button type="button" class="close" aria-label="关闭" data-target="<?= $dialogId ?>">✕</button>
        </header>
        <main>
            <form id="<?= $dialogId ?>-form" method="post" action="<?= Router::url('report/create/' . rawurlencode((string) $report_type) . '/' . rawurlencode((string) $report_target)) ?>">
                <?= Auth::csrfField() ?>
                <div class="form-group full-width mb-2">
                    <select class="form-control" name="reason" required>
                        <?php foreach (Report::reasons() as $rk => $rt): ?>
                        <option value="<?= $rk ?>"><?= htmlspecialchars($rt) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label">原因</label>
                </div>
                <div class="form-group full-width">
                    <input type="text" class="form-control" name="detail" maxlength="200" placeholder="补充说明（选填）">
                    <label class="form-label">补充说明</label>
                </div>
            </form>
        </main>
        <footer>
            <button type="button" class="btn btn-link" data-target="<?= $dialogId ?>">取消</button>
            <button type="submit" class="btn btn-error btn-sm" form="<?= $dialogId ?>-form">提交举报</button>
        </footer>
    </article>
</dialog>
