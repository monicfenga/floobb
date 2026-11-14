<?php defined('FLOO') or die('FlooBB');
/**
 * activity_timeline.tpl.php — SNS 动态时间线（Phase 2，SPEC §3.1 / §9 #17）
 * 变量：$items（渲染行，含 comments 子行）、$total、$page、$pages、$loc、$tab、$type、$viewer、$token
 *
 * 卡片结构参照 docs/参考用的单个动态卡片的HTML结构.html：
 * 一条 status 动态 = 一张卡（头部/正文/点赞行/评论气泡/卡底评论框）；
 * 系统事件（register/new_topic/new_reply/follow）= 紧凑事件卡。
 */
if (!function_exists('activity_avatar')) {
	/** 头像（有图用图，无图用首字母圆底） */
	function activity_avatar(array $r, $size = 32) {
		if ($r['avatar'] !== '') { ?>
			<img src="<?php echo htmlspecialchars($r['avatar']); ?>" alt="" class="rounded-circle flex-shrink-0 me-2" style="width:<?= $size ?>px;height:<?= $size ?>px;">
		<?php } else { ?>
			<span class="rounded-circle flex-shrink-0 me-2 d-inline-flex align-items-center justify-content-center bg-secondary text-white" style="width:<?= $size ?>px;height:<?= $size ?>px;font-size:0.8rem;"><?php echo htmlspecialchars(mb_substr($r['nickname'], 0, 1)); ?></span>
		<?php }
	}
}

if (!function_exists('activity_render_event')) {
	/** 系统事件紧凑卡（register/new_topic/new_reply/follow：无点赞无评论） */
	function activity_render_event(array $r) {
		?>
		<article class="card card-body mb-3 py-2">
			<div class="d-flex align-items-center">
				<?php activity_avatar($r, 28); ?>
				<div class="flex-grow-1 small">
					<a class="fw-bold text-decoration-none" href="<?php echo Router::url('user/view/' . rawurlencode($r['actor'])); ?>"><?php echo htmlspecialchars($r['nickname']); ?></a>
					<span class="text-muted"><?php echo htmlspecialchars($r['action']); ?></span>
					<?php if ($r['link'] !== '') { ?>
						<a class="text-decoration-none" href="<?php echo $r['link']; ?>">查看</a>
					<?php } ?>
					<span class="text-muted ms-1"><?php echo date('Y-m-d H:i', $r['created']); ?></span>
				</div>
			</div>
		</article>
		<?php
	}
}

if (!function_exists('activity_render_status')) {
	/** status 动态卡：一卡一条（点赞行 + 评论气泡 + 卡底评论框） */
	function activity_render_status(array $r, $viewer, $token) {
		$isStatusParent = $r['type'] === 'status';
		?>
		<article class="card card-body mb-3">
			<!-- 头部：头像/昵称/动作/时间/关联链接 -->
			<div class="d-flex">
				<?php activity_avatar($r); ?>
				<div class="flex-grow-1">
					<div class="d-flex align-items-center justify-content-between">
						<div>
							<a class="fw-bold text-decoration-none" href="<?php echo Router::url('user/view/' . rawurlencode($r['actor'])); ?>"><?php echo htmlspecialchars($r['nickname']); ?></a>
							<?php if (!$isStatusParent) { ?><span class="text-muted small"><?php echo htmlspecialchars($r['action']); ?></span><?php } ?>
							<span class="text-muted small ms-1"><?php echo date('Y-m-d H:i', $r['created']); ?></span>
							<?php if ($r['link'] !== '') { ?>
								<a class="small text-decoration-none ms-1" href="<?php echo $r['link']; ?>">查看</a>
							<?php } ?>
						</div>
					</div>
					<?php if ($r['text'] !== '') { ?>
						<div class="my-2<?php echo !$isStatusParent ? ' small' : ''; ?>"><?php echo nl2br(htmlspecialchars($r['text'])); ?></div>
					<?php } ?>
				</div>
			</div>
			<?php if ($isStatusParent) { ?>
			<!-- 点赞行（toggle：已点赞高亮；游客只读计数） -->
			<div class="d-flex align-items-center mb-2">
				<?php if ($viewer !== null) { ?>
				<form method="post" action="<?php echo Router::url('activity/like/' . rawurlencode($r['id'])); ?>">
					<input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
					<button type="submit" class="btn btn-sm btn-<?php echo $r['liked'] ? 'primary' : 'outline-secondary'; ?> rounded-pill">
						♥ <span class="like-count"><?php echo (int) $r['likes']; ?></span>
					</button>
				</form>
				<?php } else { ?>
				<span class="btn btn-sm btn-outline-secondary rounded-pill disabled">♥ <?php echo (int) $r['likes']; ?></span>
				<?php } ?>
			</div>
			<?php } ?>
			<!-- 评论气泡（挂卡内，不单独占卡） -->
			<?php foreach ($r['comments'] as $c) { ?>
			<div class="d-flex mb-2">
				<?php activity_avatar($c, 26); ?>
				<div class="ms-1 small">
					<div class="bg-light px-3 py-2 rounded-4">
						<a class="fw-bold text-decoration-none" href="<?php echo Router::url('user/view/' . rawurlencode($c['actor'])); ?>"><?php echo htmlspecialchars($c['nickname']); ?></a>
						<span class="text-muted ms-1"><?php echo date('Y-m-d H:i', $c['created']); ?></span>
						<div><?php echo nl2br(htmlspecialchars($c['text'])); ?></div>
					</div>
				</div>
			</div>
			<?php } ?>
			<?php if ($viewer !== null && $isStatusParent) { ?>
			<!-- 卡底评论框 -->
			<form method="post" action="<?php echo Router::url('activity/comment/' . rawurlencode($r['id'])); ?>" class="input-group input-group-sm">
				<input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
				<input type="text" class="form-control rounded-3" name="text" maxlength="200" required placeholder="写评论…">
				<button type="submit" class="btn btn-outline-primary">评论</button>
			</form>
			<?php } ?>
		</article>
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
					<div class="d-flex justify-content-end align-items-center gap-2">
						<!-- 本次发布可见度（选中项由用户设置 default_visibility 决定） -->
						<label class="small text-muted" for="post-visibility">可见性</label>
						<?php $curVis = (string) ($viewer['default_visibility'] ?? 'public'); ?>
						<select class="form-select form-select-sm" id="post-visibility" name="visibility" style="width:auto;">
							<?php foreach (array('public' => '完全公开', 'login' => '仅登录用户', 'followers' => '仅粉丝', 'private' => '仅自己') as $v => $label): ?>
							<option value="<?= $v ?>"<?= $curVis === $v ? ' selected' : '' ?>><?= $label ?></option>
							<?php endforeach; ?>
						</select>
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

		<!-- 时间线：一卡一条（status 含点赞/评论；系统事件为紧凑卡） -->
		<?php if (empty($items)) { ?>
			<p class="text-muted text-center my-4">还没有任何动态。</p>
		<?php } else { foreach ($items as $r) {
			if ($r['type'] === 'status') {
				activity_render_status($r, $viewer, $token);
			} else {
				activity_render_event($r);
			}
		} } ?>

		<?php if ($pages > 1) echo Paginate::pageLink($page, $pages, Router::url($loc)); ?>

	</div>
</div>
