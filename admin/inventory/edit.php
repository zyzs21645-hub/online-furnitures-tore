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
    header('Location: ../inventory/manage.php?message=' . urlencode('Invalid furniture item selected for editing.') . '&type=error');
    exit;
}

$categoriesStatement = $pdo->query(
    'SELECT category_id, category_name
     FROM categories
     ORDER BY category_name ASC'
);
$categories = $categoriesStatement->fetchAll();

$itemStatement = $pdo->prepare(
    'SELECT item_id, item_name, description, price, stock_quantity, image, category_id
     FROM furniture_items
     WHERE item_id = :item_id
     LIMIT 1'
);
$itemStatement->bindValue(':item_id', $itemId, PDO::PARAM_INT);
$itemStatement->execute();
$item = $itemStatement->fetch();

if (!$item) {
    header('Location: ../inventory/manage.php?message=' . urlencode('The furniture item you tried to edit was not found.') . '&type=error');
    exit;
}

$formData = [
    'item_name' => (string) $item['item_name'],
    'description' => (string) $item['description'],
    'price' => number_format((float) $item['price'], 2, '.', ''),
    'stock_quantity' => (string) $item['stock_quantity'],
    'image' => trim((string) ($item['image'] ?? '')),
    'category_id' => (string) $item['category_id'],
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
        $errors['item_name'] = 'Furniture item name is required.';
    } elseif (adminTextLength($formData['item_name']) > 100) {
        $errors['item_name'] = 'Item name must be 100 characters or fewer.';
    }

    if ($formData['description'] === '') {
        $errors['description'] = 'Please add a short furniture description.';
    }

    if ($formData['price'] === '' || !is_numeric($formData['price']) || (float) $formData['price'] <= 0) {
        $errors['price'] = 'Price must be a valid number greater than zero.';
    }

    if ($formData['stock_quantity'] === '' || filter_var($formData['stock_quantity'], FILTER_VALIDATE_INT) === false || (int) $formData['stock_quantity'] < 0) {
        $errors['stock_quantity'] = 'Stock quantity must be a whole number of zero or more.';
    }

    if ($formData['image'] !== '' && filter_var($formData['image'], FILTER_VALIDATE_URL) === false) {
        $errors['image'] = 'Image must be a valid URL or left empty.';
    }

    if ($formData['category_id'] === '' || filter_var($formData['category_id'], FILTER_VALIDATE_INT) === false) {
        $errors['category_id'] = 'Please choose a valid category.';
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
            $errors['category_id'] = 'The selected category does not exist.';
        }
    }

    if ($errors === []) {
        $updateStatement = $pdo->prepare(
            'UPDATE furniture_items
             SET item_name = :item_name,
                 description = :description,
                 price = :price,
                 stock_quantity = :stock_quantity,
                 image = :image,
                 category_id = :category_id
             WHERE item_id = :item_id'
        );
        $updateStatement->bindValue(':item_name', $formData['item_name'], PDO::PARAM_STR);
        $updateStatement->bindValue(':description', $formData['description'], PDO::PARAM_STR);
        $updateStatement->bindValue(':price', number_format((float) $formData['price'], 2, '.', ''), PDO::PARAM_STR);
        $updateStatement->bindValue(':stock_quantity', (int) $formData['stock_quantity'], PDO::PARAM_INT);
        $updateStatement->bindValue(':image', $formData['image'] !== '' ? $formData['image'] : null, $formData['image'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $updateStatement->bindValue(':category_id', (int) $formData['category_id'], PDO::PARAM_INT);
        $updateStatement->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $updateStatement->execute();

        header('Location: ../inventory/manage.php?message=' . urlencode('Furniture item updated successfully.') . '&type=success');
        exit;
    }
}

$pageTitle = 'Edit Furniture';
$pageHeading = 'Edit Furniture Item';
$pageDescription = 'Refine pricing, stock, visuals, and category details for this furniture listing.';
$currentPage = 'manage';
$headerActions = '<a class="btn btn-secondary" href="../inventory/manage.php"><i class="fa-solid fa-arrow-left"></i>Back to Inventory</a>';

require_once __DIR__ . '/../includes/header.php';
?>
<section class="hero-banner glass-card fade-up">
    <span class="badge">
        <i class="fa-solid fa-pen-to-square"></i>
        Update Inventory Entry
    </span>
    <h2 style="margin-top: 14px;">Refine this furniture listing without losing control of the catalog.</h2>
    <p style="margin-top: 12px;">
        Adjust the item details below to keep pricing, stock levels, and presentation perfectly aligned with the current inventory.
    </p>
</section>

<?php if ($errors !== []): ?>
    <div class="alert alert-error fade-up">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div>Please review the highlighted fields and correct the form before updating this item.</div>
    </div>
<?php endif; ?>

<section class="form-card fade-up">
    <div class="form-header">
        <h2>Edit Furniture Details</h2>
        <p>Update the product carefully to keep the storefront and admin records consistent.</p>
    </div>

    <form action="edit.php?id=<?php echo $itemId; ?>" method="post" novalidate>
        <div class="form-grid">
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input
                    class="<?php echo isset($errors['item_name']) ? 'input-error' : ''; ?>"
                    id="item_name"
                    name="item_name"
                    type="text"
                    maxlength="100"
                    placeholder="Modern Oak Dining Table"
                    value="<?php echo htmlspecialchars($formData['item_name'], ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
                <?php if (isset($errors['item_name'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['item_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="helper-text">Use a product name customers can recognize quickly.</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select
                    class="<?php echo isset($errors['category_id']) ? 'input-error' : ''; ?>"
                    id="category_id"
                    name="category_id"
                    required
                >
                    <option value="">Select category</option>
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
                    <span class="helper-text">Choose the furniture category already defined in the database.</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
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
                    <span class="helper-text">Enter the selling price in your store catalog.</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="stock_quantity">Stock Quantity</label>
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
                    <span class="helper-text">Keep this aligned with the real available inventory.</span>
                <?php endif; ?>
            </div>

            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea
                    class="<?php echo isset($errors['description']) ? 'input-error' : ''; ?>"
                    id="description"
                    name="description"
                    placeholder="Describe materials, style, dimensions, and the ideal room placement."
                    required
                ><?php echo htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['description'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="helper-text">A strong description improves both admin clarity and future storefront quality.</span>
                <?php endif; ?>
            </div>

            <div class="form-group full-width">
                <label for="image">Image URL</label>
                <input
                    class="<?php echo isset($errors['image']) ? 'input-error' : ''; ?>"
                    id="image"
                    name="image"
                    type="url"
                    placeholder="http://localhost/online_furniture_store/uploads/oak-table.jpg"
                    value="<?php echo htmlspecialchars($formData['image'], ENT_QUOTES, 'UTF-8'); ?>"
                >
                <?php if (isset($errors['image'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['image'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="helper-text">Optional: add a local or hosted image URL for the product card.</span>
                <?php endif; ?>
            </div>

            <div class="form-group full-width">
                <label>Preview</label>
                <div class="image-preview" id="imagePreview">
                    <?php if ($formData['image'] !== ''): ?>
                        <img src="<?php echo htmlspecialchars($formData['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Furniture preview">
                    <?php else: ?>
                        <div class="image-preview-placeholder">
                            <i class="fa-solid fa-image"></i>
                            <strong>Image preview will appear here</strong>
                            <p style="margin: 8px 0 0;">Paste a valid image URL to visualize the product before saving.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="action-row" style="margin-top: 24px;">
            <button class="btn btn-primary" type="submit">
                <i class="fa-solid fa-floppy-disk"></i>
                Update Furniture Item
            </button>
            <a class="btn btn-secondary" href="../inventory/manage.php">
                <i class="fa-solid fa-xmark"></i>
                Cancel
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
            '<strong>Image preview will appear here</strong>' +
            '<p style="margin: 8px 0 0;">Paste a valid image URL to visualize the product before saving.</p>' +
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
