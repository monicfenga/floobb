/*
 * FlooBB · 无障碍运行时增强（两主题共用，原生 JS，无依赖、无构建）
 *
 * 只负责静态 HTML 表达不了、必须随交互同步的 ARIA 状态：
 *   1. 当前项 aria-current="page"（排序 / 标签页 / 分页 / 面包屑）
 *   2. 折叠控件 aria-expanded（移动端导航、附带投票选区）
 *   3. 表单错误：关联 aria-describedby / aria-invalid，并把焦点送到错误块
 *   4. 「跳转到主内容」把焦点真正交给 <main>
 * 静态语义（landmark / label / alt / 标题层级 / skip link）一律写在模板里。
 */
(function () {
	'use strict';

	/* 1. 当前项不能只靠颜色或字重表达 */
	function markCurrent() {
		var nodes = document.querySelectorAll('.active');
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			if (!el.closest('nav, .nav, .pagination, .breadcrumb, .list-group')) continue;
			var target = el.tagName === 'LI' ? (el.querySelector('a') || el) : el;
			if (!target.hasAttribute('aria-current')) target.setAttribute('aria-current', 'page');
		}
	}

	/* 2a. tawian 汉堡菜单：真按钮驱动隐藏的 checkbox，同步展开状态 */
	function syncNavToggle() {
		var btn = document.getElementById('navigation-toggle-btn');
		var box = document.getElementById('navigation-toggle');
		if (!btn || !box) return;
		function update() {
			var open = box.checked;
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			btn.setAttribute('aria-label', open ? '收起导航菜单' : '展开导航菜单');
		}
		btn.addEventListener('click', function () {
			box.checked = !box.checked;
			update();
		});
		box.addEventListener('change', update);
		update();
	}

	/* 2b. 新建主题的「附带投票」折叠区 */
	function syncPollToggle() {
		var box = document.getElementById('t-isvote');
		if (!box) return;
		function update() { box.setAttribute('aria-expanded', box.checked ? 'true' : 'false'); }
		box.addEventListener('change', update);
		update();
	}

	/* 3. 提交失败后的错误块：让每个字段都知道错在哪，并把焦点送到错误 */
	function linkErrors() {
		var boxes = document.querySelectorAll('[role="alert"].alert-danger, [role="alert"].alert-error');
		for (var i = 0; i < boxes.length; i++) {
			var box = boxes[i];
			if (!box.id) box.id = 'floobb-error-' + (i + 1);
			box.setAttribute('tabindex', '-1');

			// 错误块通常紧跟在目标表单之前；绝不退回到「页面第一个 form」（可能是导航里的登出表单）
			var form = null, next = box.nextElementSibling;
			while (next && !form) {
				if (next.tagName === 'FORM') form = next;
				next = next.nextElementSibling;
			}
			if (!form) continue;
			var fields = form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]), select, textarea');
			for (var j = 0; j < fields.length; j++) {
				var f = fields[j];
				f.setAttribute('aria-invalid', 'true');
				var desc = (f.getAttribute('aria-describedby') || '').trim();
				if (desc.indexOf(box.id) === -1) f.setAttribute('aria-describedby', (desc + ' ' + box.id).trim());
			}
		}
		if (boxes.length) { try { boxes[0].focus(); } catch (e) {} }
	}

	/* 4. skip link：锚点跳转之外，显式把焦点交给主内容 */
	function wireSkipLinks() {
		var links = document.querySelectorAll('.skip-link');
		for (var i = 0; i < links.length; i++) {
			links[i].addEventListener('click', function () {
				var id = (this.getAttribute('href') || '').slice(1);
				var target = id && document.getElementById(id);
				if (target) { try { target.focus(); } catch (e) {} }
			});
		}
	}

	/* 5. 深浅色切换按钮：aria-pressed 表达当前状态，不只靠图标/颜色 */
	function watchTheme() {
		var btn = document.getElementById('themeToggle');
		if (!btn) return;
		function sync() {
			var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark' ||
				document.documentElement.classList.contains('dark');
			btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
		}
		sync();
		btn.addEventListener('click', sync);
		if (window.MutationObserver) {
			new MutationObserver(sync).observe(document.documentElement, {
				attributes: true, attributeFilter: ['data-bs-theme', 'class']
			});
		}
	}

	function boot() {
		watchTheme();
		markCurrent();
		syncNavToggle();
		syncPollToggle();
		linkErrors();
		wireSkipLinks();
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
	else boot();
})();
