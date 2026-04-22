<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_user_id']) || strtolower((string) ($_SESSION['admin_role'] ?? '')) !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/db_connect.php';

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($itemId === false || $itemId === null || $itemId < 1) {
    header('Location: ../inventory/manage.php?message=' . urlencode('Invalid furniture item selected for deletion.') . '&type=error');
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
    header('Location: ../inventory/manage.php?message=' . urlencode('The furniture item you tried to delete was not found.') . '&type=error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedItemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);

    if ($postedItemId === false || $postedItemId === null || $postedItemId !== $itemId) {
        header('Location: ../inventory/manage.php?message=' . urlencode('Delete request could not be verified.') . '&type=error');
        exit;
    }

    $deleteStatement = $pdo->prepare(
        'DELETE FROM furniture_items
         WHERE item_id = :item_id
         LIMIT 1'
    );
    $deleteStatement->bindValue(':item_id', $itemId, PDO::PARAM_INT);
    $deleteStatement->execute();

    header('Location: ../inventory/manage.php?message=' . urlencode('Furniture item deleted successfully.') . '&type=success');
    exit;
}

$imagePath = trim((string) ($item['image'] ?? ''));
$fallbackImage = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 120'><rect width='120' height='120' rx='24' fill='%23efe8df'/><path d='M35 77l16-18 11 13 17-21 16 26H35z' fill='%23d4a373'/><circle cx='48' cy='44' r='8' fill='%238b5a2b'/></svg>";
$imageSource = $imagePath !== '' ? htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') : $fallbackImage;

$pageTitle = 'Delete Furniture';
$pageHeading = 'Delete Furniture Item';
$pageDescription = 'Review the selected item carefully before permanently removing it from the inventory.';
$currentPage = 'manage';
$headerActions = '<a class="btn btn-secondary" href="../inventory/manage.php"><i class="fa-solid fa-arrow-left"></i>Back to Inventory</a>';

require_once __DIR__ . '/../includes/header.php';
?>
<section class="hero-banner glass-card fade-up">
    <span class="badge status-badge danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Permanent Action
    </span>
    <h2 style="margin-top: 14px;">Confirm before removing this furniture item from the catalog.</h2>
    <p style="margin-top: 12px;">
        This action permanently deletes the selected product record from the inventory dashboard. Review the details below before continuing.
    </p>
</section>

<section class="form-card fade-up">
    <div class="form-header">
        <h2>Item Ready for Deletion</h2>
        <p>If you continue, this furniture item will no longer appear in the admin inventory list.</p>
    </div>

    <div class="table-item" style="margin-bottom: 24px;">
        <img class="table-item-image" src="<?php echo $imageSource; ?>" alt="<?php echo htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="table-item-copy">
            <strong><?php echo htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span><?php echo htmlspecialchars((string) ($item['description'] ?: 'No description available.'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>

    <div class="meta-row" style="margin-bottom: 28px;">
        <span class="badge">
            <i class="fa-solid fa-tags"></i>
            <?php echo htmlspecialchars((string) ($item['category_name'] ?? 'Uncategorized'), ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <span class="meta-divider"></span>
        <span class="price-text">$<?php echo number_format((float) $item['price'], 2); ?></span>
        <span class="meta-divider"></span>
        <span class="status-badge <?php echo (int) $item['stock_quantity'] <= 5 ? 'danger' : ((int) $item['stock_quantity'] <= 15 ? 'warning' : 'success'); ?>">
            <i class="fa-solid fa-cubes-stacked"></i>
            <?php echo number_format((int) $item['stock_quantity']); ?> units
        </span>
    </div>

    <div class="alert alert-warning">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div>This change cannot be undone from the admin panel.</div>
    </div>

    <form action="delete.php?id=<?php echo $itemId; ?>" method="post">
        <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
        <div class="action-row">
            <button class="btn btn-danger" type="submit">
                <i class="fa-solid fa-trash-can"></i>
                Delete Permanently
            </button>
            <a class="btn btn-secondary" href="../inventory/manage.php">
                <i class="fa-solid fa-xmark"></i>
                Cancel
            </a>
        </div>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
