document.addEventListener('DOMContentLoaded', () => {
    // Keep the footer year current.
    document.querySelectorAll('[data-current-year]').forEach((element) => {
        element.textContent = String(new Date().getFullYear());
    });

    // Reveal page sections when they enter the screen.
    const revealItems = document.querySelectorAll('.reveal-on-scroll');

    if (!('IntersectionObserver' in window)) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.16 });

    revealItems.forEach((item) => observer.observe(item));

    // Close flash messages after one second.
    document.querySelectorAll('.flash-stack .alert').forEach((alertElement) => {
        window.setTimeout(() => {
            if (alertElement.isConnected && typeof bootstrap !== 'undefined') {
                bootstrap.Alert.getOrCreateInstance(alertElement).close();
            }
        }, 1000);
    });
    // Submit filter forms when the selected course changes.
    document.querySelectorAll('[data-submit-on-change]').forEach((select) => {
        select.addEventListener('change', () => select.form?.submit());
    });
});
