/*
 * Modal（Pico.css Dialog 配套 JS，MIT License）
 * 用法：触发元素加 data-target="modalId" 即可开关弹窗
 * 点击遮罩 / Esc 关闭；开关时给 html 挂 modal-is-open/-opening/-closing 类配合 CSS 动画
 */
(function () {
    'use strict';

    var isOpenClass = 'modal-is-open';
    var openingClass = 'modal-is-opening';
    var closingClass = 'modal-is-closing';
    var animationDuration = 400; // ms
    var visibleModal = null;

    function toggleModal(event) {
        event.preventDefault();
        var modal = document.getElementById(event.currentTarget.getAttribute('data-target'));
        if (!modal) return;
        isModalOpen(modal) ? closeModal(modal) : openModal(modal);
    }

    function isModalOpen(modal) {
        return modal.hasAttribute('open') && modal.getAttribute('open') !== 'false';
    }

    function openModal(modal) {
        if (isScrollbarVisible()) {
            document.documentElement.style.setProperty('--scrollbar-width', getScrollbarWidth() + 'px');
        }
        document.documentElement.classList.add(isOpenClass, openingClass);
        setTimeout(function () {
            visibleModal = modal;
            document.documentElement.classList.remove(openingClass);
        }, animationDuration);
        modal.setAttribute('open', true);
    }

    function closeModal(modal) {
        visibleModal = null;
        document.documentElement.classList.add(closingClass);
        setTimeout(function () {
            document.documentElement.classList.remove(closingClass, isOpenClass);
            document.documentElement.style.removeProperty('--scrollbar-width');
            modal.removeAttribute('open');
        }, animationDuration);
    }

    // 点击遮罩关闭
    document.addEventListener('click', function (event) {
        if (visibleModal != null) {
            var modalContent = visibleModal.querySelector('article');
            var isClickInside = modalContent && modalContent.contains(event.target);
            if (!isClickInside) closeModal(visibleModal);
        }
    });

    // Esc 关闭
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && visibleModal != null) {
            closeModal(visibleModal);
        }
    });

    // data-target 触发绑定
    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-target]');
        if (trigger) {
            event.preventDefault();
            var modal = document.getElementById(trigger.getAttribute('data-target'));
            if (modal) {
                isModalOpen(modal) ? closeModal(modal) : openModal(modal);
            }
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
