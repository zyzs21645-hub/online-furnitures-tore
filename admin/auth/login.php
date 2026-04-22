<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/localization.php';

if (isset($_SESSION['admin_user_id'])) {
    header('Location: ../inventory/manage.php');
    exit;
}

require_once __DIR__ . '/../config/db_connect.php';

$email = '';
$errorMessage = '';
$successMessage = '';
$successMessageKey = trim((string) ($_GET['message_key'] ?? ''));
$messageType = trim((string) ($_GET['type'] ?? ''));

if ($successMessageKey !== '' && adminHasTranslation($successMessageKey)) {
    $successMessage = adminTrans($successMessageKey);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errorMessage = adminTrans('please_enter_email_and_password');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = adminTrans('please_enter_valid_email');
    } else {
        $statement = $pdo->prepare(
            'SELECT user_id, full_name, email, password, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->bindValue(':email', $email, PDO::PARAM_STR);
        $statement->execute();
        $adminUser = $statement->fetch();

        if ($adminUser && strtolower((string) $adminUser['role']) === 'admin') {
            $storedPassword = (string) $adminUser['password'];
            $passwordMatches = password_verify($password, $storedPassword);
            $isLegacyPlainText = hash_equals($storedPassword, $password);

            if ($passwordMatches || $isLegacyPlainText) {
                if ($isLegacyPlainText && !$passwordMatches) {
                    $rehashStatement = $pdo->prepare(
                        'UPDATE users
                         SET password = :password
                         WHERE user_id = :user_id'
                    );
                    $rehashStatement->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
                    $rehashStatement->bindValue(':user_id', (int) $adminUser['user_id'], PDO::PARAM_INT);
                    $rehashStatement->execute();
                } elseif (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                    $rehashStatement = $pdo->prepare(
                        'UPDATE users
                         SET password = :password
                         WHERE user_id = :user_id'
                    );
                    $rehashStatement->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
                    $rehashStatement->bindValue(':user_id', (int) $adminUser['user_id'], PDO::PARAM_INT);
                    $rehashStatement->execute();
                }

                session_regenerate_id(true);
                $_SESSION['admin_user_id'] = (int) $adminUser['user_id'];
                $_SESSION['admin_full_name'] = (string) $adminUser['full_name'];
                $_SESSION['admin_email'] = (string) $adminUser['email'];
                $_SESSION['admin_role'] = (string) $adminUser['role'];

                header('Location: ../inventory/manage.php');
                exit;
            }
        }

        $errorMessage = adminTrans('incorrect_admin_credentials');
    }
}

$pageTitle = adminTrans('admin_login');
$pageHeading = adminTrans('admin_login');
$pageDescription = adminTrans('admin_login_desc');
$showSidebar = false;
$showTopbar = false;
$bodyClass = 'login-page';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="login-layout">
    <section class="login-showcase fade-up">
        <span class="badge">
            <i class="fa-solid fa-gem"></i>
            <?php echo htmlspecialchars(adminTrans('premium_furniture_store'), ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <h1><?php echo htmlspecialchars(adminTrans('shape_showroom_title'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p>
            <?php echo htmlspecialchars(adminTrans('shape_showroom_desc'), ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <div class="login-features">
            <div class="login-feature">
                <i class="fa-solid fa-layer-group"></i>
                <div>
                    <strong><?php echo htmlspecialchars(adminTrans('curated_inventory_control'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p style="margin: 4px 0 0;"><?php echo htmlspecialchars(adminTrans('curated_inventory_control_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
            <div class="login-feature">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <strong><?php echo htmlspecialchars(adminTrans('protected_admin_access'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p style="margin: 4px 0 0;"><?php echo htmlspecialchars(adminTrans('protected_admin_access_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
            <div class="login-feature">
                <i class="fa-solid fa-swatchbook"></i>
                <div>
                    <strong><?php echo htmlspecialchars(adminTrans('designed_for_clarity'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p style="margin: 4px 0 0;"><?php echo htmlspecialchars(adminTrans('designed_for_clarity_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </div>

        <ul class="trust-list">
            <li>
                <i class="fa-solid fa-lock"></i>
                <?php echo htmlspecialchars(adminTrans('secure_admin_sign_in'), ENT_QUOTES, 'UTF-8'); ?>
            </li>
            <li>
                <i class="fa-solid fa-warehouse"></i>
                <?php echo htmlspecialchars(adminTrans('inventory_ready_workflow'), ENT_QUOTES, 'UTF-8'); ?>
            </li>
            <li>
                <i class="fa-solid fa-moon"></i>
                <?php echo htmlspecialchars(adminTrans('built_in_theme_memory'), ENT_QUOTES, 'UTF-8'); ?>
            </li>
        </ul>
    </section>

    <section class="login-card fade-up delay-1">
        <span class="badge">
            <i class="fa-solid fa-user-shield"></i>
            <?php echo htmlspecialchars(adminTrans('admin_portal'), ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <h2><?php echo htmlspecialchars(adminTrans('welcome_back'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars(adminTrans('sign_in_with_admin_account'), ENT_QUOTES, 'UTF-8'); ?></p>

        <div class="lang-switch" style="margin: 18px 0 22px;">
            <a class="icon-btn lang-btn <?php echo adminCurrentLanguage() === 'en' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(adminUrlWithLang('en'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(adminTrans('switch_to_english'), ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars(adminTrans('language_en'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a class="icon-btn lang-btn <?php echo adminCurrentLanguage() === 'ar' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(adminUrlWithLang('ar'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(adminTrans('switch_to_arabic'), ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars(adminTrans('language_ar'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>

        <?php if ($successMessage !== '' && $messageType === 'success'): ?>
            <div class="alert alert-success" data-auto-dismiss="4000">
                <i class="fa-solid fa-circle-check"></i>
                <div><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error" data-auto-dismiss="5000">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        <?php endif; ?>

        <form class="login-form" action="login.php" method="post" novalidate>
            <div class="form-group">
                <label for="email"><?php echo htmlspecialchars(adminTrans('admin_email'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    inputmode="email"
                    autocomplete="username"
                    placeholder="<?php echo htmlspecialchars(adminTrans('admin_email_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password"><?php echo htmlspecialchars(adminTrans('password'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="password-field">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="<?php echo htmlspecialchars(adminTrans('password_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                    <button
                        class="password-toggle"
                        type="button"
                        data-password-toggle
                        data-target="#password"
                        data-label-show="<?php echo htmlspecialchars(adminTrans('show_password'), ENT_QUOTES, 'UTF-8'); ?>"
                        data-label-hide="<?php echo htmlspecialchars(adminTrans('hide_password'), ENT_QUOTES, 'UTF-8'); ?>"
                        aria-label="<?php echo htmlspecialchars(adminTrans('show_password'), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="action-row">
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <?php echo htmlspecialchars(adminTrans('sign_in'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
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
        </form>
    </section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
