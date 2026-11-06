/*
 * OverType 编辑器引导脚本（两主题共用，内容一致）
 * 依赖：页面需先引入 overtype.min.js（IIFE 构建，jsdelivr CDN，无构建步骤）
 * 约定：
 *   - 页面中带 data-md-editor 属性的 <textarea> 会被升级为 OverType 编辑器
 *   - 原 textarea 保留在 DOM 中作为表单值载体（name 不变，JS 失效时原样降级为普通 textarea）
 *   - OverType 的 onChange 回写 textarea.value，表单提交走原字段
 *   - 空值校验由本脚本在 submit 时拦截（hidden + required 会阻断原生校验弹窗）
 */
(function () {
	'use strict';

	var mountSeq = 0;

	function enhance(ta) {
		if (ta.dataset.mdReady) return;
		ta.dataset.mdReady = '1';

		var mount = document.createElement('div');
		mount.id = 'md-mount-' + (++mountSeq);
		var rows = parseInt(ta.getAttribute('rows'), 10) || 10;
		mount.style.minHeight = Math.max(rows * 24 + 16, 120) + 'px';
		mount.style.marginBottom = '0.5rem';
		ta.parentNode.insertBefore(mount, ta.nextSibling);

		var instances = window.OverType.init('#' + mount.id, {
			value: ta.value,
			toolbar: true,
			onChange: function (value) { ta.value = value; }
		});
		var editor = instances[0];

		ta.hidden = true;
		ta.required = false;

		var form = ta.form;
		if (form) {
			form.addEventListener('submit', function (e) {
				if (!ta.value.trim()) {
					e.preventDefault();
					editor.textarea.focus();
				}
			});
		}
	}

	function boot() {
		if (!window.OverType) return;
		var list = document.querySelectorAll('textarea[data-md-editor]');
		for (var i = 0; i < list.length; i++) enhance(list[i]);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
