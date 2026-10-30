document.addEventListener('DOMContentLoaded', function () {
    // Convert any remaining inline .alert-success elements to global top-right toast notifications
    const legacySuccessAlerts = document.querySelectorAll('.alert.alert-success');
    legacySuccessAlerts.forEach(function (alertEl) {
        const clone = alertEl.cloneNode(true);
        clone.querySelectorAll('.btn-close, button').forEach(function (btn) {
            btn.remove();
        });
        const message = (clone.textContent || '').trim();
        alertEl.remove();

        if (message && typeof window.showToast === 'function') {
            window.showToast(message, 'success', {
                dedupeKey: 'initial:' + message
            });
        }
    });
});
