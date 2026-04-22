<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin_user_id'])) {
    header('Location: ../inventory/manage.php');
    exit;
}

require_once __DIR__ . '/../config/db_connect.php';

$email = '';
$errorMessage = '';
$successMessage = trim((string) ($_GET['message'] ?? ''));
$messageType = trim((string) ($_GET['type'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errorMessage = 'Please enter both your admin email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
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

        $errorMessage = 'Incorrect admin credentials. Please try again.';
    }
}

$pageTitle = 'Admin Login';
$pageHeading = 'Admin Login';
$pageDescription = 'Secure access for catalog and inventory management.';
$showSidebar = false;
$showTopbar = false;
$bodyClass = 'login-page';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="login-layout">
    <section class="login-showcase fade-up">
        <span class="badge">
            <i class="fa-solid fa-gem"></i>
            Premium Furniture Store
        </span>
        <h1>Shape the showroom behind every elegant order.</h1>
        <p>
            Access the admin console to keep stock levels accurate, refine furniture details, and maintain a polished shopping experience for every customer.
        </p>

        <div class="login-features">
            <div class="login-feature">
                <i class="fa-solid fa-layer-group"></i>
                <div>
                    <strong>Curated inventory control</strong>
                    <p style="margin: 4px 0 0;">Update products, categories, pricing, and availability from one refined workspace.</p>
                </div>
            </div>
            <div class="login-feature">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <strong>Protected admin access</strong>
                    <p style="margin: 4px 0 0;">Session-based authentication with secure password verification and guarded redirects.</p>
                </div>
            </div>
            <div class="login-feature">
                <i class="fa-solid fa-swatchbook"></i>
                <div>
                    <strong>Designed for clarity</strong>
                    <p style="margin: 4px 0 0;">A luxury-inspired control panel with dark and light themes for focused work.</p>
                </div>
            </div>
        </div>

        <ul class="trust-list">
            <li>
                <i class="fa-solid fa-lock"></i>
                Secure admin-only sign in
            </li>
            <li>
                <i class="fa-solid fa-warehouse"></i>
                Inventory-ready workflow
            </li>
            <li>
                <i class="fa-solid fa-moon"></i>
                Built-in theme preference memory
            </li>
        </ul>
    </section>

    <section class="login-card fade-up delay-1">
        <span class="badge">
            <i class="fa-solid fa-user-shield"></i>
            Admin Portal
        </span>
        <h2>Welcome back</h2>
        <p>Sign in with your administrator account to manage the furniture catalog.</p>

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
                <label for="email">Admin Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    inputmode="email"
                    autocomplete="username"
                    placeholder="admin@example.com"
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        required
                    >
                    <button
                        class="password-toggle"
                        type="button"
                        data-password-toggle
                        data-target="#password"
                        aria-label="Show password"
                    >
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="action-row">
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    Sign In
                </button>
                <button class="theme-toggle" type="button" data-theme-toggle aria-label="Toggle theme" aria-pressed="false">
                    <i class="fa-solid fa-moon" data-theme-icon></i>
                </button>
            </div>
        </form>
    </section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
