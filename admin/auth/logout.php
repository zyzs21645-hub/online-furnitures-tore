<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$language = isset($_SESSION['admin_lang']) && is_string($_SESSION['admin_lang']) ? $_SESSION['admin_lang'] : 'en';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: login.php?lang=' . urlencode($language) . '&message_key=logout_success&type=success');
exit;
