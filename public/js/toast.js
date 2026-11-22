(function () {
    'use strict';

    if (window.EcoToast) return;

    var DURATIONS = { success: 4000, info: 4000, warning: 5000, error: 6000 };
    var ICONS = { success: '✓', info: 'i', warning: '!', error: '!' };
    var LABELS = { success: 'Success', info: 'Information', warning: 'Warning', error: 'Error' };
    var recent = {};
    var DEDUPE_WINDOW_MS = 1200;
    var container = null;

    function ensureContainer() {
        if (container && container.isConnected) return container;
        container = document.getElementById('ecToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'ecToastContainer';
            container.className = 'ec-toast-container';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'false');
            document.body.appendChild(container);
        }
        return container;
    }

    function dismiss(toast) {
        if (!toast || !toast.parentNode) return;
        toast.classList.remove('is-visible');
        window.setTimeout(function () {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 220);
    }

    function show(type, message, options) {
        options = options || {};
        type = DURATIONS[type] ? type : 'info';
        message = String(message == null ? '' : message).trim();
        if (!message) return null;

        var key = options.key || (type + '|' + message);
        var now = Date.now();
        if (recent[key] && (now - recent[key]) < DEDUPE_WINDOW_MS) {
            return null;
        }
        recent[key] = now;

        var host = ensureContainer();
        // A repeated event replaces the previous toast instead of stacking.
        Array.prototype.forEach.call(host.querySelectorAll('[data-key]'), function (existing) {
            if (existing.getAttribute('data-key') === key) dismiss(existing);
        });

        var toast = document.createElement('div');
        toast.className = 'ec-toast ec-toast--' + type;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-label', LABELS[type] + ': ' + message);
        toast.setAttribute('data-key', key);

        var icon = document.createElement('span');
        icon.className = 'ec-toast-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = ICONS[type];

        var text = document.createElement('span');
        text.className = 'ec-toast-message';
        text.textContent = message;

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'ec-toast-close';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.innerHTML = '&times;';
        close.addEventListener('click', function () { dismiss(toast); });

        toast.appendChild(icon);
        toast.appendChild(text);
        toast.appendChild(close);
        host.appendChild(toast);

        window.requestAnimationFrame(function () {
            toast.classList.add('is-visible');
        });

        var duration = options.duration || DURATIONS[type];
        if (duration > 0) {
            window.setTimeout(function () { dismiss(toast); }, duration);
        }

        return toast;
    }

    function showFromFlash() {
        var node = document.getElementById('ecFlashMessages');
        if (!node) return;
        try {
            var messages = JSON.parse(node.textContent || '[]');
            messages.forEach(function (item, index) {
                show(item.type || 'info', item.message || '', { key: 'flash-' + index + '-' + (item.message || '') });
            });
        } catch (e) {
            /* Malformed flash data is ignored; no notification is shown. */
        }
    }

    window.EcoToast = {
        show: show,
        dismissAll: function () {
            var host = ensureContainer();
            Array.prototype.forEach.call(host.children, dismiss);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showFromFlash);
    } else {
        showFromFlash();
    }
})();
