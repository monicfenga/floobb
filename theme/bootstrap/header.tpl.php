<?php defined('FLOO') or die('FlooBB'); ?>
<!doctype html>
<html lang="<?= htmlspecialchars($conf['lang'] ?? 'zh-CN') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($header['title'] ?? $conf['title']) ?></title>
<meta name="description" content="<?= htmlspecialchars($header['description'] ?? '') ?>">
<link href="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/css/bootswatch.zephyr.css') ?>" rel="stylesheet">
<link href="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<header class="bg-body-tertiary border-bottom mb-4">
	<nav class="navbar navbar-expand-lg container">
		<a class="navbar-brand fw-bold" href="<?= Router::url() ?>"><?= htmlspecialchars($conf['title'] ?? 'FlooBB') ?></a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="切换导航">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="mainNav">
			<ul class="navbar-nav ms-auto">
				<li class="nav-item"><a class="nav-link" href="<?= Router::url() ?>">首页</a></li>
				<?php if (Auth::isLoggedIn()): $navUser = Auth::user(); ?>
				<li class="nav-item"><a class="nav-link" href="<?= Router::url('user/view/' . urlencode((string) $navUser['username'])) ?>"><?= htmlspecialchars((string) ($navUser['nickname'] ?? $navUser['username'])) ?></a></li>
				<li class="nav-item"><a class="nav-link" href="<?= Router::url('user/settings') ?>">设置</a></li>
				<li class="nav-item">
					<form method="post" action="<?= Router::url('auth/logout') ?>" class="d-inline">
						<?= Auth::csrfField() ?>
						<button class="btn btn-link nav-link p-0 lh-lg" type="submit">登出</button>
					</form>
				</li>
				<?php else: ?>
				<li class="nav-item"><a class="nav-link" href="<?= Router::url('auth/login') ?>">登录</a></li>
				<li class="nav-item"><a class="nav-link" href="<?= Router::url('auth/register') ?>">注册</a></li>
				<?php endif; ?>
			</ul>
		</div>
	</nav>
</header>
<main class="container" role="main">
