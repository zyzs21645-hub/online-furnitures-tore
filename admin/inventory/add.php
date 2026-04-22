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

$categoriesStatement = $pdo->query(
    'SELECT category_id, category_name
     FROM categories
     ORDER BY category_name ASC'
);
$categories = $categoriesStatement->fetchAll();

$formData = [
    'item_name' => '',
    'description' => '',
    'price' => '',
    'stock_quantity' => '',
    'image' => '',
    'category_id' => '',
];

$errors = [];

if (!function_exists('adminTextLength')) {
    function adminTextLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['item_name'] = trim((string) ($_POST['item_name'] ?? ''));
    $formData['description'] = trim((string) ($_POST['description'] ?? ''));
    $formData['price'] = trim((string) ($_POST['price'] ?? ''));
    $formData['stock_quantity'] = trim((string) ($_POST['stock_quantity'] ?? ''));
    $formData['image'] = trim((string) ($_POST['image'] ?? ''));
    $formData['category_id'] = trim((string) ($_POST['category_id'] ?? ''));

    if ($formData['item_name'] === '') {
        $errors['item_name'] = adminTrans('item_name_required');
    } elseif (adminTextLength($formData['item_name']) > 100) {
        $errors['item_name'] = adminTrans('item_name_too_long');
    }

    if ($formData['description'] === '') {
        $errors['description'] = adminTrans('description_required');
    }

    if ($formData['price'] === '' || !is_numeric($formData['price']) || (float) $formData['price'] <= 0) {
        $errors['price'] = adminTrans('price_required');
    }

    if ($formData['stock_quantity'] === '' || filter_var($formData['stock_quantity'], FILTER_VALIDATE_INT) === false || (int) $formData['stock_quantity'] < 0) {
        $errors['stock_quantity'] = adminTrans('stock_required');
    }

    if ($formData['image'] !== '' && filter_var($formData['image'], FILTER_VALIDATE_URL) === false) {
        $errors['image'] = adminTrans('image_invalid');
    }

    if ($formData['category_id'] === '' || filter_var($formData['category_id'], FILTER_VALIDATE_INT) === false) {
        $errors['category_id'] = adminTrans('category_required');
    } else {
        $categoryCheck = $pdo->prepare(
            'SELECT category_id
             FROM categories
             WHERE category_id = :category_id
             LIMIT 1'
        );
        $categoryCheck->bindValue(':category_id', (int) $formData['category_id'], PDO::PARAM_INT);
        $categoryCheck->execute();

        if (!$categoryCheck->fetch()) {
            $errors['category_id'] = adminTrans('category_missing');
        }
    }

    if ($errors === []) {
        $insertStatement = $pdo->prepare(
            'INSERT INTO furniture_items (item_name, description, price, stock_quantity, image, category_id)
             VALUES (:item_name, :description, :price, :stock_quantity, :image, :category_id)'
        );
        $insertStatement->bindValue(':item_name', $formData['item_name'], PDO::PARAM_STR);
        $insertStatement->bindValue(':description', $formData['description'], PDO::PARAM_STR);
        $insertStatement->bindValue(':price', number_format((float) $formData['price'], 2, '.', ''), PDO::PARAM_STR);
        $insertStatement->bindValue(':stock_quantity', (int) $formData['stock_quantity'], PDO::PARAM_INT);
        $insertStatement->bindValue(':image', $formData['image'] !== '' ? $formData['image'] : null, $formData['image'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $insertStatement->bindValue(':category_id', (int) $formData['category_id'], PDO::PARAM_INT);
        $insertStatement->execute();

        header('Location: ../inventory/manage.php?message_key=item_added_success&type=success');
        exit;
    }
}

$pageTitle = adminTrans('add_furniture');
$pageHeading = adminTrans('add_furniture');
$pageDescription = adminTrans('furniture_details_desc');
$currentPage = 'add';
$headerActions = '<a class="btn btn-secondary" href="../inventory/manage.php"><i class="fa-solid fa-arrow-left"></i>' . htmlspecialchars(adminTrans('back_to_inventory'), ENT_QUOTES, 'UTF-8') . '</a>';

require_once __DIR__ . '/../includes/header.php';
?>
<section class="hero-banner glass-card fade-up">
    <span class="badge">
        <i class="fa-solid fa-chair"></i>
        <?php echo htmlspecialchars(adminTrans('new_inventory_entry'), ENT_QUOTES, 'UTF-8'); ?>
    </span>
    <h2 style="margin-top: 14px;"><?php echo htmlspecialchars(adminTrans('add_showroom_ready_item'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <p style="margin-top: 12px;">
        <?php echo htmlspecialchars(adminTrans('add_showroom_ready_item_desc'), ENT_QUOTES, 'UTF-8'); ?>
    </p>
</section>

<?php if ($errors !== []): ?>
    <div class="alert alert-error fade-up">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?php echo htmlspecialchars(adminTrans('review_highlighted_fields'), ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
<?php endif; ?>

<section class="form-card fade-up">
    <div class="form-header">
        <h2><?php echo htmlspecialchars(adminTrans('furniture_details'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars(adminTrans('furniture_details_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <form action="add.php" method="post" novalidate>
        <div class="form-grid">
            <div class="form-group">
                <label for="item_name"><?php echo htmlspecialchars(adminTrans('item_name'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input
                    class="<?php echo isset($errors['item_name']) ? 'input-error' : ''; ?>"
                    id="item_name"
                    name="item_name"
                    type="text"
                    maxlength="100"
                    placeholder="<?php echo htmlspecialchars(adminTrans('item_name_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                    value="<?php echo htmlspecialchars($formData['item_name'], ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
                <?php if (isset($errors['item_name'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['item_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="helper-text"><?php echo htmlspecialchars(adminTrans('item_name_hint'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="category_id"><?php echo htmlspecialchars(adminTrans('category'), ENT_QUOTES, 'UTF-8'); ?></label>
                <select
                    class="<?php echo isset($errors['category_id']) ? 'input-error' : ''; ?>"
                    id="category_id"
                    name="category_id"
                    required
                >
                    <option value=""><?php echo htmlspecialchars(adminTrans('select_category'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?php echo (int) $category['category_id']; ?>"
                            <?php echo $formData['category_id'] === (string) $category['category_id'] ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars((string) $category['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['category_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="helper-text"><?php echo htmlspecialchars(adminTrans('category_hint'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="price"><?php echo htmlspecialchars(adminTrans('price'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input
                    class="<?php echo isset($errors['price']) ? 'input-error' : ''; ?>"
                    id="price"
                    name="price"
                    type="number"
                    min="0.01"
                    step="0.01"
                    placeholder="1299.99"
                    value="<?php echo htmlspecialchars($formData['price'], ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
                <?php if (isset($errors['price'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['price'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="helper-text"><?php echo htmlspecialchars(adminTrans('price_hint'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="stock_quantity"><?php echo htmlspecialchars(adminTrans('stock'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input
                    class="<?php echo isset($errors['stock_quantity']) ? 'input-error' : ''; ?>"
                    id="stock_quantity"
                    name="stock_quantity"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="12"
                    value="<?php echo htmlspecialchars($formData['stock_quantity'], ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
                <?php if (isset($errors['stock_quantity'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['stock_quantity'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="helper-text"><?php echo htmlspecialchars(adminTrans('stock_hint'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group full-width">
                <label for="description"><?php echo htmlspecialchars(adminTrans('description'), ENT_QUOTES, 'UTF-8'); ?></label>
                <textarea
                    class="<?php echo isset($errors['description']) ? 'input-error' : ''; ?>"
                    id="description"
                    name="description"
                    placeholder="<?php echo htmlspecialchars(adminTrans('description_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                    required
                ><?php echo htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['description'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="helper-text"><?php echo htmlspecialchars(adminTrans('description_hint'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group full-width">
                <label for="image"><?php echo htmlspecialchars(adminTrans('image_url'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input
                    class="<?php echo isset($errors['image']) ? 'input-error' : ''; ?>"
                    id="image"
                    name="image"
                    type="url"
                    placeholder="<?php echo htmlspecialchars(adminTrans('image_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                    value="<?php echo htmlspecialchars($formData['image'], ENT_QUOTES, 'UTF-8'); ?>"
                >
                <?php if (isset($errors['image'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['image'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="helper-text"><?php echo htmlspecialchars(adminTrans('image_hint'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group full-width">
                <label><?php echo htmlspecialchars(adminTrans('preview'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="image-preview" id="imagePreview">
                    <?php if ($formData['image'] !== ''): ?>
                        <img src="<?php echo htmlspecialchars($formData['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Furniture preview">
                    <?php else: ?>
                        <div class="image-preview-placeholder">
                            <i class="fa-solid fa-image"></i>
                            <strong><?php echo htmlspecialchars(adminTrans('image_preview_placeholder_title'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <p style="margin: 8px 0 0;"><?php echo htmlspecialchars(adminTrans('image_preview_placeholder_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="action-row" style="margin-top: 24px;">
            <button class="btn btn-primary" type="submit">
                <i class="fa-solid fa-floppy-disk"></i>
                <?php echo htmlspecialchars(adminTrans('save_furniture_item'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <a class="btn btn-secondary" href="../inventory/manage.php">
                <i class="fa-solid fa-xmark"></i>
                <?php echo htmlspecialchars(adminTrans('cancel'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var imageInput = document.getElementById('image');
    var preview = document.getElementById('imagePreview');

    if (!imageInput || !preview) {
        return;
    }

    var placeholderMarkup = '' +
        '<div class="image-preview-placeholder">' +
            '<i class="fa-solid fa-image"></i>' +
            '<strong>' + previewTitle + '</strong>' +
            '<p style="margin: 8px 0 0;">' + previewDescription + '</p>' +
        '</div>';

    function renderPreview() {
        var value = imageInput.value.trim();

        if (value === '') {
            preview.innerHTML = placeholderMarkup;
            return;
        }

        preview.innerHTML = '<img src="' + value.replace(/"/g, '&quot;') + '" alt="Furniture preview">';
    }

    imageInput.addEventListener('input', renderPreview);
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
var previewTitle = <?php echo json_encode(adminTrans('image_preview_placeholder_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
var previewDescription = <?php echo json_encode(adminTrans('image_preview_placeholder_desc'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
