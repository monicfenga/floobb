<?php defined('FLOO') or die('FlooBB');
/**
 * activity_timeline.tpl.php — SNS 动态时间线（Phase 2，SPEC §3.1 / §9 #17）
 * 变量：$items（渲染行，含 comments 子行）、$total、$page、$pages、$loc、$tab、$type、$viewer、$token
 */
if (!function_exists('activity_render_card')) {
	/** 渲染单张活动卡（父卡与评论子行共用；评论子行紧凑样式） */
	function activity_render_card(array $r, $compact = false) {
		$userUrl = Router::url('user/view/' . rawurlencode($r['actor']));
		?>
		<div class="d-flex<?php echo $compact ? ' py-2 border-top' : ' mb-3'; ?>">
			<?php if ($r['avatar'] !== '') { ?>
				<img src="<?php echo htmlspecialchars($r['avatar']); ?>" alt="" class="rounded-circle flex-shrink-0 me-2" style="width:32px;height:32px;">
			<?php } else { ?>
				<span class="rounded-circle flex-shrink-0 me-2 d-inline-flex align-items-center justify-content-center bg-secondary text-white" style="width:32px;height:32px;font-size:0.8rem;"><?php echo htmlspecialchars(mb_substr($r['nickname'], 0, 1)); ?></span>
			<?php } ?>
			<div class="flex-grow-1">
				<div>
					<a class="fw-bold text-decoration-none" href="<?php echo $userUrl; ?>"><?php echo htmlspecialchars($r['nickname']); ?></a>
					<?php if ($compact) { ?><span class="text-muted small"><?php echo htmlspecialchars($r['action']); ?></span>
					<?php } else { echo htmlspecialchars($r['action']); } ?>
					<?php if ($r['link'] !== '') { ?>
						<a class="small text-decoration-none" href="<?php echo $r['link']; ?>">查看</a>
					<?php } ?>
					<span class="text-muted small ms-1"><?php echo date('Y-m-d H:i', $r['created']); ?></span>
				</div>
				<?php if ($r['text'] !== '') { ?>
					<div class="<?php echo $compact ? 'small' : ''; ?>"><?php echo nl2br(htmlspecialchars($r['text'])); ?></div>
				<?php } ?>
			</div>
		</div>
		<?php
	}
}
?>
<div class="row justify-content-center">
	<div class="col-lg-8">

		<?php if ($viewer !== null) { /* 发布框：登录态（KICKOFF 验收 4） */ ?>
		<div class="card mb-3">
			<div class="card-body">
				<form method="post" action="<?php echo Router::url('activity'); ?>">
					<input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
					<textarea class="form-control mb-2" name="text" rows="2" maxlength="200" required placeholder="有什么新鲜事？"></textarea>
					<div class="d-flex justify-content-end">
						<button type="submit" class="btn btn-primary btn-sm">发布动态</button>
					</div>
				</form>
			</div>
		</div>
		<?php } ?>

		<!-- Tab：全部 / 我关注的（登录态才有关注流）+ type 筛选（KICKOFF 验收 3） -->
		<ul class="nav nav-pills mb-3">
			<li class="nav-item"><a class="nav-link<?php echo $tab === 'all' ? ' active' : ''; ?>" href="<?php echo Router::url('activity'); ?>">全部</a></li>
			<?php if ($viewer !== null) { ?>
			<li class="nav-item"><a class="nav-link<?php echo $tab === 'following' ? ' active' : ''; ?>" href="<?php echo Router::url('activity?tab=following'); ?>">我关注的</a></li>
			<?php } ?>
		</ul>
		<div class="mb-3 small">
			<span class="text-muted me-1">筛选：</span>
			<?php foreach (array('' => '全部类型', 'status' => '动态', 'register' => '注册', 'new_topic' => '发主题', 'new_reply' => '回帖', 'follow' => '关注', 'comment' => '动态评论') as $t => $label) {
				$active = ($type ?? null) === ($t === '' ? null : $t);
				if ($active) { ?><span class="badge bg-primary me-1"><?php echo $label; ?></span><?php } else { ?>
				<a class="text-decoration-none me-1" href="<?php echo Router::url('activity' . ($t === '' ? '' : '?type=' . $t) . ($tab === 'following' && $t !== '' ? '&tab=following' : '')); ?>"><?php echo $label; ?></a>
			<?php } } ?>
		</div>

		<!-- 时间线卡片（KICKOFF：头像/昵称/动作/时间/关联链接） -->
		<div class="card">
			<div class="card-body">
				<?php if (empty($items)) { ?>
					<p class="text-muted text-center my-4">还没有任何动态。</p>
				<?php } else { foreach ($items as $r) {
					activity_render_card($r);
					// 评论挂父卡片下方（不单独占卡片，拍板 2026-11-14）
					foreach ($r['comments'] as $c) activity_render_card($c, true);
					foreach ($r['comments'] as $c) { /* 评论框只在末尾一次 */ }
					if ($viewer !== null) { ?>
					<form method="post" action="<?php echo Router::url('activity/comment/' . rawurlencode($r['id'])); ?>" class="input-group input-group-sm mb-3">
						<input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
						<input type="text" class="form-control" name="text" maxlength="200" required placeholder="评论这条动态…">
						<button type="submit" class="btn btn-outline-secondary">评论</button>
					</form>
				<?php } } } ?>
			</div>
		</div>

		<?php if ($pages > 1) echo Paginate::pageLink($page, $pages, Router::url($loc)); ?>

	</div>
</div>
