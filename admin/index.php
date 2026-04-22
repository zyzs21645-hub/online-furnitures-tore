<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/localization.php';

if (isset($_SESSION['admin_user_id']) && strtolower((string) ($_SESSION['admin_role'] ?? '')) === 'admin') {
    header('Location: inventory/manage.php');
    exit;
}

header('Location: auth/login.php');
exit;
