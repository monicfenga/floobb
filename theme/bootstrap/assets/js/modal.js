/*
 * Modal（Pico.css Dialog 配套 JS，MIT License）
 * 用法：触发元素加 data-target="modalId" 即可开关弹窗
 *
 * a11y 改造：
 * - 改用原生 <dialog>.showModal()：获得焦点陷阱、背景 inert、原生 Esc，
 *   不再用 setAttribute('open') 这种「假模态」（键盘可 Tab 到背景内容）
 * - 打开时聚焦弹窗内首个控件；关闭后把焦点还给触发按钮
 * - 点击遮罩关闭 / Esc 走同一条带动画的关闭路径
 * - 不支持 showModal 的浏览器降级为原行为，仍补键盘 Esc 与焦点回退
 */
(function () {
    'use strict';

    var isOpenClass = 'modal-is-open';
    var openingClass = 'modal-is-opening';
    var closingClass = 'modal-is-closing';
    var animationDuration = 400; // ms
    var visibleModal = null;
    var lastTrigger = null;
    var nativeModal = typeof HTMLDialogElement !== 'undefined' && typeof HTMLDialogElement.prototype.showModal === 'function';

    function isModalOpen(modal) {
        return modal.hasAttribute('open') && !modal.hasAttribute('data-closing');
    }

    function focusFirst(modal) {
        var target = modal.querySelector('[autofocus]') ||
            modal.querySelector('input:not([type="hidden"]):not([disabled]), select, textarea, button:not([disabled])');
        if (target) { try { target.focus(); } catch (e) {} }
    }

    function openModal(modal, trigger) {
        if (isModalOpen(modal)) return;
        lastTrigger = trigger || (document.activeElement && document.activeElement !== document.body ? document.activeElement : null);
        if (isScrollbarVisible()) {
            document.documentElement.style.setProperty('--scrollbar-width', getScrollbarWidth() + 'px');
        }
        document.documentElement.classList.add(isOpenClass, openingClass);
        visibleModal = modal;
        if (nativeModal) modal.showModal();
        else modal.setAttribute('open', '');
        focusFirst(modal);
        setTimeout(function () {
            document.documentElement.classList.remove(openingClass);
        }, animationDuration);
    }

    function closeModal(modal) {
        if (!modal || modal.hasAttribute('data-closing')) return;
        modal.setAttribute('data-closing', '');
        visibleModal = null;
        document.documentElement.classList.add(closingClass);
        setTimeout(function () {
            document.documentElement.classList.remove(closingClass, isOpenClass);
            document.documentElement.style.removeProperty('--scrollbar-width');
            if (nativeModal && modal.open) modal.close();
            else modal.removeAttribute('open');
            modal.removeAttribute('data-closing');
            if (lastTrigger && document.contains(lastTrigger)) {
                try { lastTrigger.focus(); } catch (e) {}
            }
            lastTrigger = null;
        }, animationDuration);
    }

    // 点击遮罩关闭
    document.addEventListener('click', function (event) {
        if (visibleModal == null) return;
        var modalContent = visibleModal.querySelector('article');
        var isClickInside = modalContent && modalContent.contains(event.target);
        if (!isClickInside) closeModal(visibleModal);
    });

    // Esc：原生模态走 cancel 事件（阻止默认关闭，保持我们的退场动画）
    document.addEventListener('DOMContentLoaded', function () {
        var dialogs = document.querySelectorAll('dialog');
        for (var i = 0; i < dialogs.length; i++) {
            dialogs[i].addEventListener('cancel', function (event) {
                event.preventDefault();
                closeModal(this);
            });
        }
    });

    // 降级路径的 Esc（无 showModal 时没有 cancel 事件）
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && visibleModal != null && !nativeModal) {
            closeModal(visibleModal);
        }
    });

    // data-target 触发绑定
    document.addEventListener('click', function (event) {
        var trigger = event.target.closest ? event.target.closest('[data-target]') : null;
        if (!trigger) return;
        event.preventDefault();
        var modal = document.getElementById(trigger.getAttribute('data-target'));
        if (modal) {
            isModalOpen(modal) ? closeModal(modal) : openModal(modal, trigger);
        }
    });

    function getScrollbarWidth() {
        var outer = document.createElement('div');
        outer.style.visibility = 'hidden';
        outer.style.overflow = 'scroll';
        outer.style.msOverflowStyle = 'scrollbar';
        document.body.appendChild(outer);
        var inner = document.createElement('div');
        outer.appendChild(inner);
        var scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
        outer.parentNode.removeChild(outer);
        return scrollbarWidth;
    }

    function isScrollbarVisible() {
        return document.body.scrollHeight > screen.height;
    }
})();
