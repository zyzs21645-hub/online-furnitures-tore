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

$searchTerm = trim((string) ($_GET['search'] ?? ''));
$flashMessage = trim((string) ($_GET['message'] ?? ''));
$flashType = trim((string) ($_GET['type'] ?? 'success'));

$metricsStatement = $pdo->query(
    'SELECT
        COUNT(*) AS total_items,
        COALESCE(SUM(stock_quantity), 0) AS total_units,
        COALESCE(SUM(price * stock_quantity), 0) AS inventory_value,
        SUM(CASE WHEN stock_quantity <= 5 THEN 1 ELSE 0 END) AS low_stock_items
     FROM furniture_items'
);
$metrics = $metricsStatement->fetch() ?: [
    'total_items' => 0,
    'total_units' => 0,
    'inventory_value' => 0,
    'low_stock_items' => 0,
];

if ($searchTerm !== '') {
    $itemsStatement = $pdo->prepare(
        'SELECT fi.item_id, fi.item_name, fi.description, fi.price, fi.stock_quantity, fi.image, fi.created_at,
                c.category_name
         FROM furniture_items fi
         LEFT JOIN categories c ON fi.category_id = c.category_id
         WHERE fi.item_name LIKE :search
            OR fi.description LIKE :search
            OR c.category_name LIKE :search
         ORDER BY fi.created_at DESC, fi.item_id DESC'
    );
    $itemsStatement->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
    $itemsStatement->execute();
} else {
    $itemsStatement = $pdo->query(
        'SELECT fi.item_id, fi.item_name, fi.description, fi.price, fi.stock_quantity, fi.image, fi.created_at,
                c.category_name
         FROM furniture_items fi
         LEFT JOIN categories c ON fi.category_id = c.category_id
         ORDER BY fi.created_at DESC, fi.item_id DESC'
    );
}

$items = $itemsStatement->fetchAll();

if (!function_exists('adminPreviewText')) {
    function adminPreviewText(string $text, int $limit = 68): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($normalized === '') {
            return 'No description available.';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($normalized) > $limit ? mb_substr($normalized, 0, $limit - 3) . '...' : $normalized;
        }

        return strlen($normalized) > $limit ? substr($normalized, 0, $limit - 3) . '...' : $normalized;
    }
}

$alertClass = 'alert-success';
if ($flashType === 'error') {
    $alertClass = 'alert-error';
} elseif ($flashType === 'warning') {
    $alertClass = 'alert-warning';
}

$pageTitle = 'Inventory Dashboard';
$pageHeading = 'Inventory Dashboard';
$pageDescription = 'Track furniture listings, stock movement, and product readiness from one premium workspace.';
$currentPage = 'manage';
$headerActions = '<a class="btn btn-primary" href="../inventory/add.php"><i class="fa-solid fa-plus"></i>Add Furniture</a>';

require_once __DIR__ . '/../includes/header.php';
?>
<section class="hero-banner glass-card fade-up">
    <span class="badge">
        <i class="fa-solid fa-warehouse"></i>
        Admin Inventory Control
    </span>
    <h2 style="margin-top: 14px;">Maintain a curated catalog with precise stock visibility.</h2>
    <p style="margin-top: 12px;">
        Review every furniture item, identify low-stock products quickly, and keep your storefront polished with fast edit and delete actions.
    </p>
    <div class="hero-actions">
        <a class="btn btn-primary" href="../inventory/add.php">
            <i class="fa-solid fa-plus"></i>
            Add New Item
        </a>
        <a class="btn btn-secondary" href="manage.php">
            <i class="fa-solid fa-rotate-right"></i>
            Refresh View
        </a>
    </div>
</section>

<?php if ($flashMessage !== ''): ?>
    <div class="alert <?php echo htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8'); ?> fade-up" data-auto-dismiss="4500">
        <i class="fa-solid fa-circle-info"></i>
        <div><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
<?php endif; ?>

<section class="metrics-grid page-section">
    <article class="metric-card fade-up">
        <p>Total Furniture Items</p>
        <strong><?php echo number_format((int) $metrics['total_items']); ?></strong>
    </article>
    <article class="metric-card fade-up delay-1">
        <p>Total Units in Stock</p>
        <strong><?php echo number_format((int) $metrics['total_units']); ?></strong>
    </article>
    <article class="metric-card fade-up delay-2">
        <p>Estimated Inventory Value</p>
        <strong>$<?php echo number_format((float) $metrics['inventory_value'], 2); ?></strong>
    </article>
    <article class="metric-card fade-up delay-3">
        <p>Low Stock Alerts</p>
        <strong><?php echo number_format((int) $metrics['low_stock_items']); ?></strong>
    </article>
</section>

<section class="table-card page-section fade-up">
    <div class="table-header">
        <div>
            <h2>Furniture Collection</h2>
            <p class="text-muted">Browse all products, filter the list, and take action instantly.</p>
        </div>
        <form action="manage.php" method="get" class="search-bar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
                type="search"
                name="search"
                placeholder="Search by name, description, or category"
                value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>"
            >
        </form>
    </div>

    <?php if ($items !== []): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Furniture Item</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $imagePath = trim((string) ($item['image'] ?? ''));
                        $fallbackImage = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 120'><rect width='120' height='120' rx='24' fill='%23efe8df'/><path d='M35 77l16-18 11 13 17-21 16 26H35z' fill='%23d4a373'/><circle cx='48' cy='44' r='8' fill='%238b5a2b'/></svg>";
                        $imageSource = $imagePath !== '' ? htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') : $fallbackImage;
                        $stockQuantity = (int) $item['stock_quantity'];
                        $stockStatusClass = 'success';

                        if ($stockQuantity <= 5) {
                            $stockStatusClass = 'danger';
                        } elseif ($stockQuantity <= 15) {
                            $stockStatusClass = 'warning';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="table-item">
                                    <img class="table-item-image" src="<?php echo $imageSource; ?>" alt="<?php echo htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="table-item-copy">
                                        <strong><?php echo htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span>
                                            <?php
                                            $description = trim((string) ($item['description'] ?? ''));
                                            echo htmlspecialchars(adminPreviewText($description), ENT_QUOTES, 'UTF-8');
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge">
                                    <i class="fa-solid fa-tags"></i>
                                    <?php echo htmlspecialchars((string) ($item['category_name'] ?? 'Uncategorized'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="price-text">$<?php echo number_format((float) $item['price'], 2); ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars($stockStatusClass, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa-solid fa-cubes-stacked"></i>
                                    <?php echo number_format($stockQuantity); ?> units
                                </span>
                            </td>
                            <td>
                                <?php
                                $createdAt = strtotime((string) $item['created_at']);
                                echo htmlspecialchars($createdAt ? date('M d, Y', $createdAt) : 'N/A', ENT_QUOTES, 'UTF-8');
                                ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-secondary" href="../inventory/edit.php?id=<?php echo (int) $item['item_id']; ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </a>
                                    <a
                                        class="btn btn-danger"
                                        href="../inventory/delete.php?id=<?php echo (int) $item['item_id']; ?>"
                                        data-confirm="Delete this furniture item permanently?"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-box-open"></i>
            <h3 style="margin: 0 0 8px;">No furniture items found</h3>
            <p style="margin: 0;">
                <?php if ($searchTerm !== ''): ?>
                    No results matched your search. Try a different keyword or clear the filter.
                <?php else: ?>
                    Your inventory is currently empty. Add the first furniture item to start managing the catalog.
                <?php endif; ?>
            </p>
            <div class="hero-actions" style="justify-content: center;">
                <a class="btn btn-primary" href="../inventory/add.php">
                    <i class="fa-solid fa-plus"></i>
                    Add Furniture
                </a>
                <?php if ($searchTerm !== ''): ?>
                    <a class="btn btn-secondary" href="manage.php">
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Clear Search
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
