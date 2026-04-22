<?php
declare(strict_types=1);

require_once __DIR__ . '/localization.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = isset($pageTitle) && is_string($pageTitle) ? trim($pageTitle) : adminTrans('admin_panel');
$pageHeading = isset($pageHeading) && is_string($pageHeading) ? trim($pageHeading) : $pageTitle;
$pageDescription = isset($pageDescription) && is_string($pageDescription) ? trim($pageDescription) : adminTrans('manage_products_securely');
$currentPage = isset($currentPage) && is_string($currentPage) ? trim($currentPage) : '';
$bodyClass = isset($bodyClass) && is_string($bodyClass) ? trim($bodyClass) : '';
$showSidebar = isset($showSidebar) ? (bool) $showSidebar : true;
$showTopbar = isset($showTopbar) ? (bool) $showTopbar : $showSidebar;
$headerActions = $headerActions ?? '';
$isLoggedIn = isset($_SESSION['admin_user_id']);
$currentLanguage = adminCurrentLanguage();
$direction = adminDirection();
$adminName = isset($_SESSION['admin_full_name']) && is_string($_SESSION['admin_full_name']) && $_SESSION['admin_full_name'] !== ''
    ? $_SESSION['admin_full_name']
    : adminTrans('administrator');

$manageLinkClass = $currentPage === 'manage' ? 'sidebar-link active' : 'sidebar-link';
$addLinkClass = $currentPage === 'add' ? 'sidebar-link active' : 'sidebar-link';
$logoutLinkClass = $currentPage === 'logout' ? 'sidebar-link active' : 'sidebar-link';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLanguage, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($direction, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars(adminTrans('site_name'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../assets/icons/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body class="<?php echo htmlspecialchars(trim($bodyClass . ($direction === 'rtl' ? ' rtl-layout' : '')), ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($showSidebar): ?>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="brand-block fade-up">
                <div class="brand-logo">
                    <i class="fa-solid fa-couch"></i>
                </div>
                <div class="brand-copy">
                    <h1><?php echo htmlspecialchars(adminTrans('furniture_admin'), ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p><?php echo htmlspecialchars(adminTrans('elegant_inventory_control_panel'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>

            <nav class="sidebar-nav fade-up delay-1">
                <a class="<?php echo $manageLinkClass; ?>" href="../inventory/manage.php">
                    <i class="fa-solid fa-table-list"></i>
                    <span><?php echo htmlspecialchars(adminTrans('manage_inventory'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
                <a class="<?php echo $addLinkClass; ?>" href="../inventory/add.php">
                    <i class="fa-solid fa-plus"></i>
                    <span><?php echo htmlspecialchars(adminTrans('add_new_item'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
                <a class="<?php echo $logoutLinkClass; ?>" href="../auth/logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span><?php echo htmlspecialchars(adminTrans('logout'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            </nav>

            <div class="sidebar-footer fade-up delay-2">
                <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <p><?php echo htmlspecialchars(adminTrans('signed_in_catalog_note'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </aside>

        <main class="admin-main">
<?php endif; ?>

<?php if ($showTopbar): ?>
    <header class="topbar fade-up">
        <div class="topbar-title">
            <h1><?php echo htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="topbar-actions">
            <?php if ($isLoggedIn): ?>
                <span class="badge">
                    <i class="fa-solid fa-user-shield"></i>
                    <?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            <?php endif; ?>

            <div class="lang-switch" aria-label="<?php echo htmlspecialchars(adminTrans('toggle_theme'), ENT_QUOTES, 'UTF-8'); ?>">
                <a
                    class="icon-btn lang-btn <?php echo $currentLanguage === 'en' ? 'active' : ''; ?>"
                    href="<?php echo htmlspecialchars(adminUrlWithLang('en'), ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="<?php echo htmlspecialchars(adminTrans('switch_to_english'), ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <?php echo htmlspecialchars(adminTrans('language_en'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <a
                    class="icon-btn lang-btn <?php echo $currentLanguage === 'ar' ? 'active' : ''; ?>"
                    href="<?php echo htmlspecialchars(adminUrlWithLang('ar'), ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="<?php echo htmlspecialchars(adminTrans('switch_to_arabic'), ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <?php echo htmlspecialchars(adminTrans('language_ar'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>

            <?php
            if (is_string($headerActions) && trim($headerActions) !== '') {
                echo $headerActions;
            }
            ?>

            <button
                class="theme-toggle"
                type="button"
                data-theme-toggle
                data-title-light="<?php echo htmlspecialchars(adminTrans('theme_light'), ENT_QUOTES, 'UTF-8'); ?>"
                data-title-dark="<?php echo htmlspecialchars(adminTrans('theme_dark'), ENT_QUOTES, 'UTF-8'); ?>"
                aria-label="<?php echo htmlspecialchars(adminTrans('toggle_theme'), ENT_QUOTES, 'UTF-8'); ?>"
                aria-pressed="false"
            >
                <i class="fa-solid fa-moon" data-theme-icon></i>
            </button>
        </div>
    </header>
<?php endif; ?>
