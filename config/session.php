<?php
declare(strict_types=1);

// Start a secure session when the application needs authentication.

if (session_status() !== PHP_SESSION_ACTIVE) {
    $configuredSessionPath = getenv('SESSION_SAVE_PATH');
    if (is_string($configuredSessionPath) && $configuredSessionPath !== '') {
        session_save_path($configuredSessionPath);
    } elseif (PHP_SAPI === 'cli-server') {
        session_save_path(sys_get_temp_dir());
    }

    session_name('ccp_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
