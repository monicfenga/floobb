<!DOCTYPE html>
<html>
<head>
<title><?= $header['subtitle'] . '-' . $header['title'] ?></title>
<link rel="stylesheet" href="http://maxbeier.github.io/tawian-frontend/tawian-frontend.css">
<link rel="stylesheet" type="text/css" href="app.css" />
</head>
<body>
<main id="container" class="container">
<header class="site-header">
$backBtn
<span class="site-title">
<?= $header['title'] ?>
</span>
<nav class="responsive-nav">
<label for="navigation-toggle">&#9776;</label>
</nav>
<input type="checkbox" id="navigation-toggle">
<nav class="site-nav nav-separated">
<ul>
$loginHeader
</ul>
</nav>
</header>
<h1>
<?= $header['subtitle'] ?>
</h1>