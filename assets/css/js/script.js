// Simple helper scripts

document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Confirm before revoke / deactivate actions
    const dangerousForms = document.querySelectorAll('form[onsubmit*="confirm"]');
    // (already handled by inline onsubmit, this is just backup)
});