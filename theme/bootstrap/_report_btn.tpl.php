<?php defined('FLOO') or die('FlooBB');
/**
 * 举报按钮 + 内联折叠表单（零 JS，details 元素）
 * 调用处须先备好：$report_type ('post'|'topic')、$report_target（id）、$report_label（按钮文字，缺省「举报」）
 */
if (!isset($report_type, $report_target)) return;
$viewer = Auth::user();
if ($viewer === null) return; // 未登录不出举报入口
?>
<details class="report-box d-inline">
	<summary class="btn btn-sm btn-link text-body-secondary text-decoration-none px-1" style="cursor:pointer;"><?= htmlspecialchars($report_label ?? '举报') ?></summary>
	<form method="post" action="<?= Router::url('report/create/' . rawurlencode((string) $report_type) . '/' . rawurlencode((string) $report_target)) ?>"
		class="border rounded p-2 mt-1 bg-body-tertiary" style="min-width: 240px;">
		<?= Auth::csrfField() ?>
		<div class="mb-1">
			<label class="form-label small mb-0" for="rep-reason-<?= htmlspecialchars((string) $report_target) ?>">原因</label>
			<select class="form-select form-select-sm" id="rep-reason-<?= htmlspecialchars((string) $report_target) ?>" name="reason" required>
				<?php foreach (Report::reasons() as $rk => $rt): ?>
				<option value="<?= $rk ?>"><?= htmlspecialchars($rt) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="mb-2">
			<label class="form-label small mb-0" for="rep-detail-<?= htmlspecialchars((string) $report_target) ?>">补充说明（选填）</label>
			<input type="text" class="form-control form-control-sm" id="rep-detail-<?= htmlspecialchars((string) $report_target) ?>" name="detail" maxlength="200">
		</div>
		<button type="submit" class="btn btn-sm btn-outline-danger">提交举报</button>
	</form>
</details>
