<?php defined('FLOO') or die('FlooBB'); ?>
</main>
<footer class="border-top mt-5 py-4 text-center">
	<div><?= htmlspecialchars($conf['footer_text'] ? $conf['footer_text'] . ' &middot; ' : '') ?> 
		Powered by <a href="https://github.com/monicfenga/floobb" rel="noopener">FlooBB</a> (GPLv3+)
	</div>
</footer>
<script src="<?= Router::url('theme/' . rawurlencode((string) $conf['theme']) . '/assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
