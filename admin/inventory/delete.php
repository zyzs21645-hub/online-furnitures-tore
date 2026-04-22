<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/localization.php';

if (!isset($_SESSION['admin_user_id']) || strtolower((string) ($_SESSION['admin_role'] ?? '')) !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/db_connect.php';

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($itemId === false || $itemId === null || $itemId < 1) {
    header('Location: ../inventory/manage.php?message_key=invalid_item_delete&type=error');
    exit;
}

$itemStatement = $pdo->prepare(
    'SELECT fi.item_id, fi.item_name, fi.description, fi.price, fi.stock_quantity, fi.image, c.category_name
     FROM furniture_items fi
     LEFT JOIN categories c ON fi.category_id = c.category_id
     WHERE fi.item_id = :item_id
     LIMIT 1'
);
$itemStatement->bindValue(':item_id', $itemId, PDO::PARAM_INT);
$itemStatement->execute();
$item = $itemStatement->fetch();

if (!$item) {
    header('Location: ../inventory/manage.php?message_key=item_not_found_delete&type=error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedItemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);

    if ($postedItemId === false || $postedItemId === null || $postedItemId !== $itemId) {
        header('Location: ../inventory/manage.php?message_key=delete_request_not_verified&type=error');
        exit;
    }

    $deleteStatement = $pdo->prepare(
        'DELETE FROM furniture_items
         WHERE item_id = :item_id
         LIMIT 1'
    );
    $deleteStatement->bindValue(':item_id', $itemId, PDO::PARAM_INT);
    $deleteStatement->execute();

    header('Location: ../inventory/manage.php?message_key=item_deleted_success&type=success');
    exit;
}

$imagePath = trim((string) ($item['image'] ?? ''));
$fallbackImage = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 120'><rect width='120' height='120' rx='24' fill='%23efe8df'/><path d='M35 77l16-18 11 13 17-21 16 26H35z' fill='%23d4a373'/><circle cx='48' cy='44' r='8' fill='%238b5a2b'/></svg>";
$imageSource = $imagePath !== '' ? htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') : $fallbackImage;

$pageTitle = adminTrans('delete_furniture');
$pageHeading = adminTrans('delete_furniture_item');
$pageDescription = adminTrans('delete_furniture_desc');
$currentPage = 'manage';
$headerActions = '<a class="btn btn-secondary" href="../inventory/manage.php"><i class="fa-solid fa-arrow-left"></i>' . htmlspecialchars(adminTrans('back_to_inventory'), ENT_QUOTES, 'UTF-8') . '</a>';

require_once __DIR__ . '/../includes/header.php';
?>
<section class="hero-banner glass-card fade-up">
    <span class="badge status-badge danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?php echo htmlspecialchars(adminTrans('permanent_action'), ENT_QUOTES, 'UTF-8'); ?>
    </span>
    <h2 style="margin-top: 14px;"><?php echo htmlspecialchars(adminTrans('confirm_delete_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <p style="margin-top: 12px;">
        <?php echo htmlspecialchars(adminTrans('confirm_delete_desc'), ENT_QUOTES, 'UTF-8'); ?>
    </p>
</section>

<section class="form-card fade-up">
    <div class="form-header">
        <h2><?php echo htmlspecialchars(adminTrans('item_ready_for_deletion'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars(adminTrans('item_ready_for_deletion_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <div class="table-item" style="margin-bottom: 24px;">
        <img class="table-item-image" src="<?php echo $imageSource; ?>" alt="<?php echo htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="table-item-copy">
            <strong><?php echo htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span><?php echo htmlspecialchars((string) ($item['description'] ?: adminTrans('no_description_available')), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>

    <div class="meta-row" style="margin-bottom: 28px;">
        <span class="badge">
            <i class="fa-solid fa-tags"></i>
            <?php echo htmlspecialchars((string) ($item['category_name'] ?? adminTrans('uncategorized')), ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <span class="meta-divider"></span>
        <span class="price-text">$<?php echo number_format((float) $item['price'], 2); ?></span>
        <span class="meta-divider"></span>
        <span class="status-badge <?php echo (int) $item['stock_quantity'] <= 5 ? 'danger' : ((int) $item['stock_quantity'] <= 15 ? 'warning' : 'success'); ?>">
            <i class="fa-solid fa-cubes-stacked"></i>
            <?php echo number_format((int) $item['stock_quantity']); ?> <?php echo htmlspecialchars(adminTrans('units'), ENT_QUOTES, 'UTF-8'); ?>
        </span>
    </div>

    <div class="alert alert-warning">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?php echo htmlspecialchars(adminTrans('delete_warning'), ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <form action="delete.php?id=<?php echo $itemId; ?>" method="post">
        <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
        <div class="action-row">
            <button class="btn btn-danger" type="submit">
                <i class="fa-solid fa-trash-can"></i>
                <?php echo htmlspecialchars(adminTrans('delete_permanently'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <a class="btn btn-secondary" href="../inventory/manage.php">
                <i class="fa-solid fa-xmark"></i>
                <?php echo htmlspecialchars(adminTrans('cancel'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
