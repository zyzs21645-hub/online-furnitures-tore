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
    header('Location: ../inventory/manage.php?message_key=invalid_item_edit&type=error');
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
    header('Location: ../inventory/manage.php?message_key=item_not_found_edit&type=error');
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
$currentImagePath = $formData['image'];

if (!function_exists('adminTextLength')) {
    function adminTextLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}

if (!function_exists('adminEditUploadText')) {
    function adminEditUploadText(string $key): string
    {
        $texts = [
            'en' => [
                'image_upload_failed' => 'The image upload failed. Please try again.',
                'image_invalid_upload' => 'Please upload a valid image file: JPG, PNG, GIF, or WEBP.',
                'image_upload_directory_failed' => 'The uploads directory could not be prepared.',
                'image_upload_label' => 'Furniture Image',
                'image_upload_drop_title' => 'Drag and drop a new furniture image here',
                'image_upload_drop_desc' => 'Leave it empty if you want to keep the current image.',
                'image_upload_browse' => 'Browse Files',
                'image_upload_hint' => 'Upload a replacement image from your device only when you want to change the current one.'
            ],
            'ar' => [
                'image_upload_failed' => 'فشل رفع الصورة. حاول مرة أخرى.',
                'image_invalid_upload' => 'يرجى رفع ملف صورة صالح: JPG أو PNG أو GIF أو WEBP.',
                'image_upload_directory_failed' => 'تعذر تجهيز مجلد الرفع.',
                'image_upload_label' => 'صورة الأثاث',
                'image_upload_drop_title' => 'اسحب وأفلت صورة أثاث جديدة هنا',
                'image_upload_drop_desc' => 'اترك الحقل فارغًا إذا كنت تريد الاحتفاظ بالصورة الحالية.',
                'image_upload_browse' => 'اختيار ملف',
                'image_upload_hint' => 'ارفع صورة بديلة من جهازك فقط عند الرغبة في تغيير الصورة الحالية.'
            ],
        ];

        $language = adminCurrentLanguage();
        return $texts[$language][$key] ?? $texts['en'][$key] ?? $key;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['item_name'] = trim((string) ($_POST['item_name'] ?? ''));
    $formData['description'] = trim((string) ($_POST['description'] ?? ''));
    $formData['price'] = trim((string) ($_POST['price'] ?? ''));
    $formData['stock_quantity'] = trim((string) ($_POST['stock_quantity'] ?? ''));
    $formData['category_id'] = trim((string) ($_POST['category_id'] ?? ''));
    $formData['image'] = $currentImagePath;

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

    $hasNewUpload = isset($_FILES['image_file'])
        && is_array($_FILES['image_file'])
        && (int) ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($hasNewUpload) {
        $imageFile = $_FILES['image_file'];
        $uploadError = (int) ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError !== UPLOAD_ERR_OK) {
            $errors['image_file'] = adminEditUploadText('image_upload_failed');
        } else {
            $tmpPath = (string) ($imageFile['tmp_name'] ?? '');
            $originalName = (string) ($imageFile['name'] ?? '');
            $fileInfo = @getimagesize($tmpPath);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $mimeType = $fileInfo['mime'] ?? '';
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if ($tmpPath === '' || !is_uploaded_file($tmpPath) || $fileInfo === false) {
                $errors['image_file'] = adminEditUploadText('image_invalid_upload');
            } elseif (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
                $errors['image_file'] = adminEditUploadText('image_invalid_upload');
            }
        }
    }

    if ($errors === [] && $hasNewUpload) {
        $uploadsDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';

        if (!is_dir($uploadsDirectory) && !mkdir($uploadsDirectory, 0777, true) && !is_dir($uploadsDirectory)) {
            $errors['image_file'] = adminEditUploadText('image_upload_directory_failed');
        } else {
            $imageFile = $_FILES['image_file'];
            $originalName = (string) ($imageFile['name'] ?? '');
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $safeFileName = 'furniture_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
            $targetPath = $uploadsDirectory . DIRECTORY_SEPARATOR . $safeFileName;
            $databasePath = '../uploads/' . $safeFileName;

            if (!move_uploaded_file((string) $imageFile['tmp_name'], $targetPath)) {
                $errors['image_file'] = adminEditUploadText('image_upload_failed');
            } else {
                $formData['image'] = $databasePath;
            }
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

        header('Location: ../inventory/manage.php?message_key=item_updated_success&type=success');
        exit;
    }
}

$pageTitle = adminTrans('edit_furniture');
$pageHeading = adminTrans('edit_furniture_item');
$pageDescription = adminTrans('edit_furniture_desc');
$currentPage = 'manage';
$headerActions = '<a class="btn btn-secondary" href="../inventory/manage.php"><i class="fa-solid fa-arrow-left"></i>' . htmlspecialchars(adminTrans('back_to_inventory'), ENT_QUOTES, 'UTF-8') . '</a>';

require_once __DIR__ . '/../includes/header.php';
?>
<style>
#imagePreview img {
    max-width: 100%;
    max-height: 200px;
    object-fit: contain;
    border-radius: 8px;
}
</style>

<section class="hero-banner glass-card fade-up">
    <span class="badge">
        <i class="fa-solid fa-pen-to-square"></i>
        <?php echo htmlspecialchars(adminTrans('update_inventory_entry'), ENT_QUOTES, 'UTF-8'); ?>
    </span>
    <h2 style="margin-top: 14px;"><?php echo htmlspecialchars(adminTrans('update_inventory_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <p style="margin-top: 12px;">
        <?php echo htmlspecialchars(adminTrans('update_inventory_desc'), ENT_QUOTES, 'UTF-8'); ?>
    </p>
</section>

<?php if ($errors !== []): ?>
    <div class="alert alert-error fade-up">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?php echo htmlspecialchars(adminTrans('review_highlighted_fields_update'), ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
<?php endif; ?>

<section class="form-card fade-up">
    <div class="form-header">
        <h2><?php echo htmlspecialchars(adminTrans('edit_furniture_details'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars(adminTrans('edit_furniture_details_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <form action="edit.php?id=<?php echo $itemId; ?>" method="post" enctype="multipart/form-data" novalidate>
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
                <label for="image_file"><?php echo htmlspecialchars(adminEditUploadText('image_upload_label'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div
                    class="upload-zone <?php echo isset($errors['image_file']) ? 'input-error' : ''; ?> <?php echo $formData['image'] !== '' ? 'has-file' : ''; ?>"
                    data-upload-zone
                    data-upload-input="#image_file"
                    data-upload-preview="#imagePreview"
                >
                    <input
                        class="file-input-hidden"
                        id="image_file"
                        name="image_file"
                        type="file"
                        accept="image/*"
                    >
                    <div class="upload-zone-content">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <strong><?php echo htmlspecialchars(adminEditUploadText('image_upload_drop_title'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p class="upload-hint"><?php echo htmlspecialchars(adminEditUploadText('image_upload_drop_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="upload-zone-actions">
                            <button class="btn btn-secondary" type="button" data-upload-trigger>
                                <i class="fa-solid fa-folder-open"></i>
                                <?php echo htmlspecialchars(adminEditUploadText('image_upload_browse'), ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php if (isset($errors['image_file'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['image_file'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <span class="helper-text"><?php echo htmlspecialchars(adminEditUploadText('image_upload_hint'), ENT_QUOTES, 'UTF-8'); ?></span>
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
                <?php echo htmlspecialchars(adminTrans('update_furniture_item'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <a class="btn btn-secondary" href="../inventory/manage.php">
                <i class="fa-solid fa-xmark"></i>
                <?php echo htmlspecialchars(adminTrans('cancel'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
