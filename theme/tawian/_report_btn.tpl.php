<?php defined('FLOO') or die('FlooBB');
/**
 * 举报按钮 + 内联折叠表单（零 JS，details 元素）
 * 调用处须先备好：$report_type ('post'|'topic'|'user')、$report_target（id 或用户名）、$report_label（按钮文字，缺省「举报」）
 */
if (!isset($report_type, $report_target)) return;
$viewer = Auth::user();
if ($viewer === null) return; // 未登录不出举报入口
?>
<details class="report-box inline">
    <summary class="btn btn-link btn-sm" style="cursor:pointer;"><?= htmlspecialchars($report_label ?? '举报') ?></summary>
    <form method="post" action="<?= Router::url('report/create/' . rawurlencode((string) $report_type) . '/' . rawurlencode((string) $report_target)) ?>" class="card" style="min-width:240px;">
        <?= Auth::csrfField() ?>
        <div class="card-body">
            <label class="form-group">
                <select class="form-control" name="reason" required>
                    <?php foreach (Report::reasons() as $rk => $rt): ?>
                    <option value="<?= $rk ?>"><?= htmlspecialchars($rt) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="form-label">原因</span>
            </label>
            <label class="form-group">
                <input type="text" class="form-control" name="detail" maxlength="200" placeholder="补充说明（选填）">
                <span class="form-label">补充说明</span>
            </label>
            <button type="submit" class="btn btn-error btn-sm">提交举报</button>
        </div>
    </form>
</details>
