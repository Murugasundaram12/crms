/**
 * HouseFix360 CRM - Project-Wide Reusable AJAX Form Validation
 *
 * Intercepts data-submission forms to submit asynchronously via fetch/AJAX,
 * displaying Laravel 422 validation errors inline without full-page reloads.
 */
(function () {
    'use strict';

    if (window.crmAjaxFormValidatorInitialized) {
        return;
    }
    window.crmAjaxFormValidatorInitialized = true;

    /**
     * Resolve a form element from a Laravel dot-notation validation key.
     * E.g. "client_id", "items.0.quantity", "roles.0", "items.0.rows.1.description"
     */
    function findFieldElement(form, key) {
        if (!form || !key) return null;

        // 1. Direct name match: name="client_id"
        let el = form.querySelector(`[name="${CSS.escape(key)}"]`);
        if (el) return el;

        // 2. Bracket notation from dot-notation: items.0.rows.1.price -> items[0][rows][1][price]
        const parts = key.split('.');
        if (parts.length > 1) {
            const bracketName = parts[0] + parts.slice(1).map(p => `[${p}]`).join('');
            el = form.querySelector(`[name="${CSS.escape(bracketName)}"]`);
            if (el) return el;

            // Wildcard prefix match for dynamic array row
            const rowPrefix = parts[0] + `[${parts[1]}]`;
            el = form.querySelector(`[name^="${CSS.escape(rowPrefix)}"]`);
            if (el) return el;
        }

        // 3. Array notation with empty brackets: permissions -> permissions[]
        el = form.querySelector(`[name="${CSS.escape(key)}[]"]`);
        if (el) return el;

        if (parts.length > 1) {
            el = form.querySelector(`[name="${CSS.escape(parts[0])}[]"]`);
            if (el) return el;
        }

        // 4. Match by exact ID or underscored ID
        el = form.querySelector(`#${CSS.escape(key)}`);
        if (el) return el;

        const underKey = key.replace(/\./g, '_');
        el = form.querySelector(`#${CSS.escape(underKey)}`);
        if (el) return el;

        // 5. Match by partial ID inside this form (e.g., edit_client_id_5)
        el = form.querySelector(`[id*="${CSS.escape(key)}"]`);
        if (el) return el;

        return null;
    }

    /**
     * Escape HTML special characters to prevent XSS in dynamic alert banners.
     */
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    /**
     * Clear all validation errors, invalid classes, and alert banners from a form.
     */
    function clearValidationErrors(form) {
        if (!form) return;

        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
            el.removeAttribute('aria-invalid');
            el.removeAttribute('aria-describedby');
            delete el.dataset.crmClearBound;
        });

        form.querySelectorAll('.select2-selection.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        form.querySelectorAll('.crm-ajax-feedback').forEach(fb => fb.remove());

        form.querySelectorAll('.invalid-feedback.d-block').forEach(fb => {
            fb.classList.remove('d-block');
            fb.style.display = 'none';
        });

        form.querySelectorAll('.crm-form-alert').forEach(al => al.remove());
    }

    /**
     * Clear validation error for a single field when the user modifies it.
     */
    function clearSingleFieldError(el, key) {
        if (!el) return;

        el.classList.remove('is-invalid');
        el.removeAttribute('aria-invalid');
        el.removeAttribute('aria-describedby');
        delete el.dataset.crmClearBound;

        // Clear select2 invalid style
        const next = el.nextElementSibling;
        if (next && next.classList.contains('select2-container')) {
            const selection = next.querySelector('.select2-selection');
            if (selection) selection.classList.remove('is-invalid');
        }

        const form = el.closest('form');
        if (!form) return;

        const safeKey = key.replace(/[^a-zA-Z0-9_-]/g, '_');
        const feedbackList = form.querySelectorAll(
            `#${CSS.escape(safeKey)}_ajax_error, #${CSS.escape(safeKey)}_error, [data-crm-field="${CSS.escape(key)}"], #${CSS.escape(el.id)}_error`
        );

        feedbackList.forEach(fb => {
            if (fb.classList.contains('crm-ajax-feedback')) {
                fb.remove();
            } else {
                fb.classList.remove('d-block');
                fb.style.display = 'none';
            }
        });

        // If no more invalid fields in the form, clean up form-level alert
        if (!form.querySelector('.is-invalid')) {
            const alert = form.querySelector('.crm-form-alert');
            if (alert) alert.remove();
        }
    }

    /**
     * Setup event listeners to clear error state as soon as the user corrects a field.
     */
    function setupClearListener(el, key) {
        if (!el || el.dataset.crmClearBound === '1') return;
        el.dataset.crmClearBound = '1';

        const clearHandler = function () {
            clearSingleFieldError(el, key);
            el.removeEventListener('input', clearHandler);
            el.removeEventListener('change', clearHandler);
        };

        el.addEventListener('input', clearHandler);
        el.addEventListener('change', clearHandler);

        // Support Select2 change events
        if (window.jQuery && $(el).data('select2')) {
            $(el).one('select2:select select2:clear change', function () {
                clearHandler();
            });
        }
    }

    /**
     * Display a single field error message.
     */
    function displayFieldError(form, key, messages) {
        const el = findFieldElement(form, key);
        const message = Array.isArray(messages) ? messages[0] : String(messages);

        if (!el) {
            return false;
        }

        // Add standard Bootstrap invalid styles and accessibility attributes
        el.classList.add('is-invalid');
        el.setAttribute('aria-invalid', 'true');

        // Handle Select2 container if present
        let select2Container = null;
        if (el.classList.contains('select2-hidden-accessible') || (window.jQuery && $(el).data('select2'))) {
            select2Container = el.nextElementSibling && el.nextElementSibling.classList.contains('select2-container')
                ? el.nextElementSibling
                : form.querySelector(`.select2-container--default [aria-labelledby*="${CSS.escape(el.id)}"]`)?.closest('.select2-container');

            if (select2Container) {
                const selection = select2Container.querySelector('.select2-selection');
                if (selection) selection.classList.add('is-invalid');
            }
        }

        // Generate error ID
        const safeKey = key.replace(/[^a-zA-Z0-9_-]/g, '_');
        const errorId = (el.id ? el.id : safeKey) + '_ajax_error';
        el.setAttribute('aria-describedby', errorId);

        // Look for existing error container
        let feedbackEl = form.querySelector(`#${CSS.escape(errorId)}`)
            || form.querySelector(`#${CSS.escape(safeKey)}_error`)
            || (el.id ? form.querySelector(`#${CSS.escape(el.id)}_error`) : null);

        if (!feedbackEl) {
            const parent = el.closest('.input-group') || el.closest('.form-check') || el.parentElement;
            if (parent) {
                feedbackEl = parent.querySelector(`.invalid-feedback[data-crm-field="${CSS.escape(key)}"]`);
            }
        }

        if (feedbackEl) {
            feedbackEl.textContent = message;
            feedbackEl.classList.add('d-block');
            feedbackEl.style.display = 'block';
        } else {
            feedbackEl = document.createElement('div');
            feedbackEl.className = 'invalid-feedback d-block crm-ajax-feedback';
            feedbackEl.id = errorId;
            feedbackEl.setAttribute('data-crm-field', key);
            feedbackEl.textContent = message;

            // Position feedback container properly
            if (select2Container) {
                select2Container.insertAdjacentElement('afterend', feedbackEl);
            } else if (el.closest('.input-group')) {
                el.closest('.input-group').insertAdjacentElement('afterend', feedbackEl);
            } else if (el.closest('.form-check')) {
                const checkGroup = el.closest('.form-check').parentElement;
                if (checkGroup && checkGroup.classList.contains('form-check-group')) {
                    checkGroup.appendChild(feedbackEl);
                } else {
                    el.closest('.form-check').insertAdjacentElement('afterend', feedbackEl);
                }
            } else {
                el.insertAdjacentElement('afterend', feedbackEl);
            }
        }

        setupClearListener(el, key);
        return true;
    }

    /**
     * Display a top-level alert message on the form.
     */
    function showFormAlert(form, type, message) {
        if (!form || !message) return;

        form.querySelectorAll('.crm-form-alert').forEach(al => al.remove());

        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show crm-form-alert mb-3`;
        alert.setAttribute('role', 'alert');
        alert.innerHTML = `
            <span>${escapeHtml(message)}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        const targetContainer = form.closest('.modal-body')
            || form.closest('.offcanvas-body')
            || form;

        if (targetContainer === form) {
            form.insertAdjacentElement('afterbegin', alert);
        } else {
            targetContainer.insertAdjacentElement('afterbegin', alert);
        }

        alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Focus and scroll to the first invalid field.
     */
    function focusFirstInvalid(form) {
        const firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (firstInvalid.classList.contains('select2-hidden-accessible')) {
                const s2 = firstInvalid.nextElementSibling?.querySelector('.select2-selection');
                if (s2) s2.focus();
            } else {
                firstInvalid.focus();
            }
        }
    }

    /**
     * Display validation errors for a form.
     */
    function displayValidationErrors(form, errors) {
        let unmatchedErrors = [];

        Object.entries(errors).forEach(([key, messages]) => {
            const matched = displayFieldError(form, key, messages);
            if (!matched) {
                const msg = Array.isArray(messages) ? messages.join(' ') : String(messages);
                unmatchedErrors.push(msg);
            }
        });

        if (unmatchedErrors.length > 0) {
            showFormAlert(form, 'danger', unmatchedErrors.join(' | '));
        }

        focusFirstInvalid(form);
    }

    /**
     * Check whether a form should be excluded from AJAX submission.
     */
    function shouldExcludeForm(form) {
        if (!form || !(form instanceof HTMLFormElement)) {
            return true;
        }

        // 1. Exclude GET forms (search, filter, pagination)
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method === 'GET') {
            return true;
        }

        // 2. Exclude explicitly opt-out forms
        if (
            form.hasAttribute('data-no-ajax') ||
            form.getAttribute('data-ajax') === 'false' ||
            form.classList.contains('no-ajax')
        ) {
            return true;
        }

        // 3. Exclude export / file download / report sample forms
        const action = form.getAttribute('action') || '';
        if (
            action.includes('/export') ||
            action.includes('/download') ||
            action.includes('/sample')
        ) {
            return true;
        }

        // 4. Exclude delete confirmation modal form itself
        if (form.id === 'crmDeleteForm' || form.closest('#crmDeleteModal')) {
            return true;
        }

        return false;
    }

    /**
     * Main submission event handler.
     */
    async function handleFormSubmit(event) {
        // If another script already prevented submission (e.g. client validation check), respect it
        if (event.defaultPrevented) {
            return;
        }

        const form = event.target;
        if (shouldExcludeForm(form)) {
            return;
        }

        // Prevent duplicate concurrent submissions
        if (form.dataset.crmSubmitting === '1') {
            event.preventDefault();
            return;
        }

        // Intercept form submission: prevent full-page reload
        event.preventDefault();

        form.dataset.crmSubmitting = '1';

        // Collect and disable submit button(s)
        const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        if (form.id) {
            submitButtons.push(...Array.from(document.querySelectorAll(`button[type="submit"][form="${form.id}"], input[type="submit"][form="${form.id}"]`)));
        }

        submitButtons.forEach(btn => {
            btn.dataset.crmOriginalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('disabled');
        });

        // Clear existing errors
        clearValidationErrors(form);

        const actionUrl = form.getAttribute('action') || window.location.href;
        const formMethod = (form.getAttribute('method') || 'POST').toUpperCase();
        const formData = new FormData(form);

        // Ensure CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfMeta && !formData.has('_token')) {
            formData.append('_token', csrfMeta);
        }

        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CRM-Form-Ajax': '1'
        };

        if (csrfMeta) {
            headers['X-CSRF-TOKEN'] = csrfMeta;
        }

        try {
            const response = await fetch(actionUrl, {
                method: formMethod,
                headers: headers,
                body: formData,
            });

            // 1. Validation failure: HTTP 422
            if (response.status === 422) {
                const data = await response.json().catch(() => null);
                if (data && data.errors && Object.keys(data.errors).length > 0) {
                    displayValidationErrors(form, data.errors);
                } else if (data && data.message) {
                    showFormAlert(form, 'danger', data.message);
                } else {
                    showFormAlert(form, 'danger', 'Please correct the highlighted errors.');
                }
                return;
            }

            // 2. Session expired: HTTP 419
            if (response.status === 419) {
                showFormAlert(form, 'warning', 'Your session has expired. Please refresh the page and try again.');
                return;
            }

            // 3. Unauthorized / Forbidden: HTTP 401 / 403
            if (response.status === 401 || response.status === 403) {
                const data = await response.json().catch(() => null);
                showFormAlert(form, 'danger', (data && data.message) || 'You are not authorized to perform this action.');
                return;
            }

            // 4. Server Error: HTTP 500+
            if (response.status >= 500) {
                const data = await response.json().catch(() => null);
                showFormAlert(form, 'danger', (data && data.message) || 'A server error occurred. Please try again.');
                return;
            }

            // 5. Successful submission (HTTP 200..299)
            if (response.ok) {
                const data = await response.json().catch(() => null);

                // Close parent modal or offcanvas if open
                const modalEl = form.closest('.modal');
                if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modalInstance.hide();
                }

                const offcanvasEl = form.closest('.offcanvas');
                if (offcanvasEl && window.bootstrap && window.bootstrap.Offcanvas) {
                    const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
                    offcanvasInstance.hide();
                }

                // Follow destination workflow
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                } else if (response.redirected && response.url) {
                    window.location.href = response.url;
                } else {
                    window.location.reload();
                }
                return;
            }

            // Other unexpected HTTP status
            const fallbackData = await response.json().catch(() => null);
            showFormAlert(form, 'danger', (fallbackData && fallbackData.message) || `Request failed with status ${response.status}.`);

        } catch (error) {
            console.error('[CRM AJAX Validation] Request failed:', error);
            showFormAlert(form, 'danger', 'Unable to complete the request. Please check your network connection.');
        } finally {
            delete form.dataset.crmSubmitting;
            submitButtons.forEach(btn => {
                if (btn.dataset.crmOriginalHtml) {
                    btn.innerHTML = btn.dataset.crmOriginalHtml;
                    delete btn.dataset.crmOriginalHtml;
                }
                btn.disabled = false;
                btn.classList.remove('disabled');
            });
        }
    }

    // Attach delegated submit event listener
    document.addEventListener('submit', handleFormSubmit, false);

})();
