<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = isset($pageTitle) && is_string($pageTitle) ? trim($pageTitle) : 'Admin Panel';
$pageHeading = isset($pageHeading) && is_string($pageHeading) ? trim($pageHeading) : $pageTitle;
$pageDescription = isset($pageDescription) && is_string($pageDescription) ? trim($pageDescription) : 'Manage products, inventory details, and admin activity securely.';
$currentPage = isset($currentPage) && is_string($currentPage) ? trim($currentPage) : '';
$bodyClass = isset($bodyClass) && is_string($bodyClass) ? trim($bodyClass) : '';
$showSidebar = isset($showSidebar) ? (bool) $showSidebar : true;
$showTopbar = isset($showTopbar) ? (bool) $showTopbar : $showSidebar;
$headerActions = $headerActions ?? '';
$isLoggedIn = isset($_SESSION['admin_user_id']);
$adminName = isset($_SESSION['admin_full_name']) && is_string($_SESSION['admin_full_name']) && $_SESSION['admin_full_name'] !== ''
    ? $_SESSION['admin_full_name']
    : 'Administrator';

$manageLinkClass = $currentPage === 'manage' ? 'sidebar-link active' : 'sidebar-link';
$addLinkClass = $currentPage === 'add' ? 'sidebar-link active' : 'sidebar-link';
$logoutLinkClass = $currentPage === 'logout' ? 'sidebar-link active' : 'sidebar-link';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> | Online Furniture Store</title>
    <link rel="stylesheet" href="../assets/icons/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($showSidebar): ?>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="brand-block fade-up">
                <div class="brand-logo">
                    <i class="fa-solid fa-couch"></i>
                </div>
                <div class="brand-copy">
                    <h1>Furniture Admin</h1>
                    <p>Elegant inventory control panel</p>
                </div>
            </div>

            <nav class="sidebar-nav fade-up delay-1">
                <a class="<?php echo $manageLinkClass; ?>" href="../inventory/manage.php">
                    <i class="fa-solid fa-table-list"></i>
                    <span>Manage Inventory</span>
                </a>
                <a class="<?php echo $addLinkClass; ?>" href="../inventory/add.php">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add New Item</span>
                </a>
                <a class="<?php echo $logoutLinkClass; ?>" href="../auth/logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </nav>

            <div class="sidebar-footer fade-up delay-2">
                <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <p>Signed in to keep the catalog polished, stocked, and ready for shoppers.</p>
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

            <?php
            if (is_string($headerActions) && trim($headerActions) !== '') {
                echo $headerActions;
            }
            ?>

            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Toggle theme" aria-pressed="false">
                <i class="fa-solid fa-moon" data-theme-icon></i>
            </button>
        </div>
    </header>
<?php endif; ?>
