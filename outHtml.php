<?php
/**
 * Header
 */
	function outHtml1($title)
	{
		echo implode(array(
			'<!DOCTYPE html>',
			'<html>',
			'<head>',
			'<title>'.$title.' - '. BOARDNAME .'</title>',
			'<link rel="stylesheet" href="http://maxbeier.github.io/tawian-frontend/tawian-frontend.css">',
			'<link rel="stylesheet" type="text/css" href="app.css" />',
			'</head>',
			'<body>',
			'<main id="container" class="container">'
		));
	}

	/**
	 * Main content
	 */
	function outHtml2($title, $url="",$includeHeader=true)
	{
		$loginHeader = '';
		$backBtn = (isset($url) && !empty($url)) ? '<li><a href="'.$url.'"class="btn btn-link tooltip-right" data-tooltip="Back">&#65308;</a></li>' : '';
		if ($includeHeader)
		{
			include("loginHeader.php");
		}
		echo implode(array(
			'<header class="site-header">'
			,$backBtn
			,'<span class="site-title">'
			, BOARDNAME
			, (empty($url) ? " - " . BOARDDESCRIPTION : '')
			,'</span>'
			,'<nav class="responsive-nav">'
			,'<label for="navigation-toggle">&#9776;</label>'
			,'</nav>'
			,'<input type="checkbox" id="navigation-toggle">'
			,'<nav class="site-nav nav-separated">'
			,'<ul>'
			, $loginHeader
			,'</ul>'
			,'</nav>'
			,'</header>'
			,'<h1>'
			, $title
			,'</h1>'
		));
		

	}
	
	/**
	 * Footer
	 */
	function outHtml3()
	{
		echo implode(array(
			'<footer id="copyright" class="site-footer">flooBB 1.2.0</footer>',
			'</main>',
		'</body>',
		'</html>',
		));
	}

	/**
	 * @deprecated
	 */
function call_editor() {
	
/* echo <<<THEEDIT
<!-- TinyMCE -->
<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript" src="tiny_mce_init.js"></script>
THEEDIT; */

}
