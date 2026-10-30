@once
@php
    $__crmRawSuccessMessages = [];

    if (session()->has('success')) {
        $__crmFlashSuccess = session('success');
        if (is_array($__crmFlashSuccess)) {
            foreach ($__crmFlashSuccess as $__msg) {
                if ( is_scalar($__msg) && trim((string) $__msg) !== '' ) {
                    $__crmRawSuccessMessages[] = trim((string) $__msg);
                }
            }
        } elseif (is_scalar($__crmFlashSuccess) && trim((string) $__crmFlashSuccess) !== '') {
            $__crmRawSuccessMessages[] = trim((string) $__crmFlashSuccess);
        }
    }

    if (session()->has('status')) {
        $__crmFlashStatus = session('status');
        if (is_array($__crmFlashStatus)) {
            foreach ($__crmFlashStatus as $__msg) {
                if ( is_scalar($__msg) && trim((string) $__msg) !== '' ) {
                    $__crmRawSuccessMessages[] = trim((string) $__msg);
                }
            }
        } elseif (is_scalar($__crmFlashStatus) && trim((string) $__crmFlashStatus) !== '') {
            $__crmRawSuccessMessages[] = trim((string) $__crmFlashStatus);
        }
    }

    // Deduplicate identical messages within the same request cycle while preserving order
    $__crmInitialSuccessToasts = array_values(array_unique($__crmRawSuccessMessages));
@endphp

<style>
    .crm-toast-container {
        position: fixed;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 10990;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        align-items: flex-end;
        max-width: 380px;
        width: calc(100vw - 2.5rem);
        pointer-events: none;
    }

    .crm-toast {
        pointer-events: auto;
        width: 100%;
        background-color: #ffffff;
        border: 1px solid rgba(22, 163, 74, 0.18);
        border-left: 4px solid #16a34a;
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.14), 0 4px 10px -2px rgba(15, 23, 42, 0.07);
        padding: 0.875rem 1rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        color: #1e293b;
        transform: translateX(115%);
        opacity: 0;
        transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
        will-change: transform, opacity;
    }

    .crm-toast.crm-toast-visible {
        transform: translateX(0);
        opacity: 1;
    }

    .crm-toast.crm-toast-hiding {
        transform: translateX(25px);
        opacity: 0;
        transition: transform 0.22s ease, opacity 0.2s ease;
    }

    .crm-toast-icon {
        flex-shrink: 0;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: #dcfce7;
        color: #15803d;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
        line-height: 1;
        margin-top: 1px;
    }

    .crm-toast-body {
        flex: 1 1 auto;
        min-width: 0;
    }

    .crm-toast-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #15803d;
        line-height: 1.25;
        margin-bottom: 0.2rem;
    }

    .crm-toast-message {
        font-size: 0.84rem;
        color: #334155;
        line-height: 1.4;
        word-break: break-word;
        margin: 0;
    }

    .crm-toast-close {
        flex-shrink: 0;
        background: transparent;
        border: 0;
        padding: 0 0.15rem;
        margin: -0.15rem -0.1rem 0 0.25rem;
        color: #94a3b8;
        font-size: 1.25rem;
        line-height: 1;
        cursor: pointer;
        border-radius: 4px;
        transition: color 0.15s ease, background-color 0.15s ease;
    }

    .crm-toast-close:hover,
    .crm-toast-close:focus {
        color: #334155;
        background-color: #f1f5f9;
        outline: none;
    }

    @media (max-width: 575.98px) {
        .crm-toast-container {
            top: 0.75rem;
            right: 0.75rem;
            left: 0.75rem;
            width: auto;
            max-width: none;
            align-items: stretch;
        }
    }
</style>

<div id="crmGlobalToastContainer" class="crm-toast-container" aria-live="polite" aria-atomic="false"></div>

<script>
    (function () {
        'use strict';

        const DEFAULT_DURATION_MS = 4000;
        const renderedInitialKeys = new Set();

        function ensureToastContainer() {
            let container = document.getElementById('crmGlobalToastContainer');
            if (!container && document.body) {
                container = document.createElement('div');
                container.id = 'crmGlobalToastContainer';
                container.className = 'crm-toast-container';
                container.setAttribute('aria-live', 'polite');
                container.setAttribute('aria-atomic', 'false');
                document.body.appendChild(container);
            }
            return container;
        }

        function dismissToastElement(toastEl) {
            if (!toastEl || toastEl.dataset.crmDismissing === '1') {
                return;
            }
            toastEl.dataset.crmDismissing = '1';

            if (toastEl._crmAutoHideTimer) {
                clearTimeout(toastEl._crmAutoHideTimer);
                toastEl._crmAutoHideTimer = null;
            }

            toastEl.classList.remove('crm-toast-visible');
            toastEl.classList.add('crm-toast-hiding');

            const removeNode = function () {
                if (toastEl && toastEl.parentNode) {
                    toastEl.parentNode.removeChild(toastEl);
                }
            };

            toastEl.addEventListener('transitionend', removeNode, { once: true });
            setTimeout(removeNode, 260);
        }

        /**
         * Global reusable toast notification helper.
         * Usage: showToast('Record saved successfully', 'success');
         *
         * @param {string|Array<string>} message
         * @param {string} [type='success']
         * @param {Object} [options={}]
         */
        window.showToast = function (message, type, options) {
            if (Array.isArray(message)) {
                message.forEach(function (item) {
                    window.showToast(item, type, options);
                });
                return null;
            }

            if (message === null || message === undefined) {
                return null;
            }

            const normalizedMessage = String(message).trim();
            if (!normalizedMessage) {
                return null;
            }

            const resolvedType = (type || 'success').toLowerCase();
            const opts = options || {};
            const duration = typeof opts.duration === 'number' && opts.duration > 0
                ? opts.duration
                : DEFAULT_DURATION_MS;

            if (opts.dedupeKey) {
                if (renderedInitialKeys.has(opts.dedupeKey)) {
                    return null;
                }
                renderedInitialKeys.add(opts.dedupeKey);
            }

            const container = ensureToastContainer();
            if (!container) {
                return null;
            }

            const toastEl = document.createElement('div');
            toastEl.className = 'crm-toast crm-toast-' + resolvedType;
            toastEl.setAttribute('role', 'status');
            toastEl.setAttribute('aria-live', 'polite');

            // Icon [ ✓ ]
            const iconEl = document.createElement('div');
            iconEl.className = 'crm-toast-icon';
            iconEl.setAttribute('aria-hidden', 'true');
            iconEl.textContent = '✓';

            // Content body
            const bodyEl = document.createElement('div');
            bodyEl.className = 'crm-toast-body';

            const titleEl = document.createElement('div');
            titleEl.className = 'crm-toast-title';
            titleEl.textContent = opts.title || 'Success';

            const msgEl = document.createElement('p');
            msgEl.className = 'crm-toast-message';
            // Always use textContent to safely escape any user/server string and prevent XSS
            msgEl.textContent = normalizedMessage;

            bodyEl.appendChild(titleEl);
            bodyEl.appendChild(msgEl);

            // Manual close button (×)
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'crm-toast-close';
            closeBtn.setAttribute('aria-label', 'Close notification');
            closeBtn.textContent = '×';
            closeBtn.addEventListener('click', function () {
                dismissToastElement(toastEl);
            });

            toastEl.appendChild(iconEl);
            toastEl.appendChild(bodyEl);
            toastEl.appendChild(closeBtn);

            container.appendChild(toastEl);

            // Trigger smooth slide-in animation
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    toastEl.classList.add('crm-toast-visible');
                });
            });

            // Auto-dismiss after ~4 seconds
            toastEl._crmAutoHideTimer = setTimeout(function () {
                dismissToastElement(toastEl);
            }, duration);

            return toastEl;
        };

        function flushInitialToasts() {
            const initialMessages = @json($__crmInitialSuccessToasts);
            const extraQueued = Array.isArray(window.__crmQueuedToasts) ? window.__crmQueuedToasts : [];
            const combined = initialMessages.concat(extraQueued);
            window.__crmQueuedToasts = [];

            // Also check sessionStorage for any pending AJAX reload toast
            try {
                const pendingRaw = sessionStorage.getItem('crm_pending_toast');
                if (pendingRaw) {
                    sessionStorage.removeItem('crm_pending_toast');
                    const pendingObj = JSON.parse(pendingRaw);
                    if (pendingObj && pendingObj.message) {
                        combined.push(String(pendingObj.message));
                    }
                }
            } catch (e) {
                // Ignore storage access errors
            }

            combined.forEach(function (msg) {
                if (msg === null || msg === undefined) {
                    return;
                }
                const text = String(msg).trim();
                if (!text) {
                    return;
                }
                window.showToast(text, 'success', {
                    dedupeKey: 'initial:' + text
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', flushInitialToasts);
        } else {
            flushInitialToasts();
        }
    })();
</script>
@endonce
