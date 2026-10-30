<?php defined('FLOO') or die('FlooBB');
/**
 * 举报按钮 + dialog 弹窗表单（modal.js data-target 触发）
 * 调用处须先备好：$report_type ('post'|'topic')、$report_target（id）、$report_label（按钮文字，缺省「举报」）
 */
if (!isset($report_type, $report_target)) return;
$viewer = Auth::user();
if ($viewer === null) return; // 未登录不出举报入口
$dialogId = 'report-modal-' . md5((string) $report_type . ':' . (string) $report_target);
?>
<button type="button" class="btn btn-sm btn-link text-body-secondary text-decoration-none px-1" data-target="<?= $dialogId ?>"><?= htmlspecialchars($report_label ?? '举报') ?></button>
<dialog id="<?= $dialogId ?>" aria-label="举报">
	<article>
		<header>
			<h5><?= htmlspecialchars($report_label ?? '举报') ?></h5>
			<button type="button" class="close" aria-label="关闭" data-target="<?= $dialogId ?>">✕</button>
		</header>
		<main>
			<form id="<?= $dialogId ?>-form" method="post" action="<?= Router::url('report/create/' . rawurlencode((string) $report_type) . '/' . rawurlencode((string) $report_target)) ?>">
				<?= Auth::csrfField() ?>
				<div class="mb-2">
					<label class="form-label small mb-1" for="rep-reason-<?= $dialogId ?>">原因</label>
					<select class="form-select" id="rep-reason-<?= $dialogId ?>" name="reason" required>
						<?php foreach (Report::reasons() as $rk => $rt): ?>
						<option value="<?= $rk ?>"><?= htmlspecialchars($rt) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label class="form-label small mb-1" for="rep-detail-<?= $dialogId ?>">补充说明（选填）</label>
					<input type="text" class="form-control" id="rep-detail-<?= $dialogId ?>" name="detail" maxlength="200">
				</div>
			</form>
		</main>
		<footer>
			<button type="button" class="btn btn-outline-secondary btn-sm" data-target="<?= $dialogId ?>">取消</button>
			<button type="submit" class="btn btn-danger btn-sm" form="<?= $dialogId ?>-form">提交举报</button>
		</footer>
	</article>
</dialog>
