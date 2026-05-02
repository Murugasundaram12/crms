document.addEventListener('DOMContentLoaded', function () {
    // Extend success alert duration to 15 seconds
    const alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(function (alert) {
        const closeBtn = alert.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.style.display = 'none'; // Hide close button initially
        }

        // Show close button after 12 seconds
        setTimeout(function () {
            if (closeBtn) {
                closeBtn.style.display = 'block';
            }
        }, 12000);
    });
});
