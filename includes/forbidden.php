<?php
// Show a friendly page when a role is not allowed.
$pageTitle = 'Access denied';
$activePage = '';
require __DIR__ . '/header.php';
?>
<section class="app-shell">
    <div class="container py-5">
        <div class="empty-state text-center mx-auto">
            <span class="status-code">403</span>
            <h1>You do not have access to this page.</h1>
            <p>Your account role does not permit this action.</p>
            <a class="btn btn-ink" href="<?= e(url(dashboard_path(require_login()))) ?>">Return to dashboard</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
