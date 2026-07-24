<?php
declare(strict_types=1);

// Sign out through a protected POST request.

require_once __DIR__ . '/../includes/auth.php';
require_login();

if (!is_post()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

verify_csrf();
logout_user();
redirect();
