<?php
declare(strict_types=1);

require_once __DIR__ . 'admin/includes/localization.php';

// إذا فيه جلسة مشرف شغالة فعلًا، نوديه مباشرة على صفحة إدارة المخزون.
if (isset($_SESSION['admin_user_id']) && strtolower((string) ($_SESSION['admin_role'] ?? '')) === 'admin') {
    header('Location: admin/inventory/manage.php');
    exit;
}

// إذا ما فيه تسجيل دخول، نحوله على صفحة الدخول.
header('Location: admin/auth/login.php');
exit;
