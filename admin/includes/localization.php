<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!function_exists('adminRememberCookieName')) {
    function adminRememberCookieName(): string
    {
        return 'admin_remember';
    }
}

if (!function_exists('adminRememberCookieLifetime')) {
    function adminRememberCookieLifetime(): int
    {
        return 60 * 60 * 24 * 30;
    }
}

if (!function_exists('adminRememberCookieSecret')) {
    function adminRememberCookieSecret(): string
    {
        return 'online_furniture_store_admin_remember_v1_7f3b22f0a9b44d8d9f214e4c5b672a31';
    }
}

if (!function_exists('adminRememberCookieOptions')) {
    function adminRememberCookieOptions(int $expires): array
    {
        return [
            'expires' => $expires,
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

if (!function_exists('adminLanguageCookieName')) {
    function adminLanguageCookieName(): string
    {
        return 'lang';
    }
}

if (!function_exists('adminLanguageCookieLifetime')) {
    function adminLanguageCookieLifetime(): int
    {
        return 60 * 60 * 24 * 30;
    }
}

if (!function_exists('adminLanguageCookieOptions')) {
    function adminLanguageCookieOptions(int $expires): array
    {
        return [
            'expires' => $expires,
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => false,
            'samesite' => 'Lax',
        ];
    }
}

if (!function_exists('adminSetLanguageCookie')) {
    function adminSetLanguageCookie(string $language): void
    {
        if (!in_array($language, adminAvailableLanguages(), true)) {
            return;
        }

        $expires = time() + adminLanguageCookieLifetime();
        setcookie(adminLanguageCookieName(), $language, adminLanguageCookieOptions($expires));
        $_COOKIE[adminLanguageCookieName()] = $language;
    }
}

if (!function_exists('adminSetRememberCookie')) {
    function adminSetRememberCookie(array $user): void
    {
        $userId = (int) ($user['user_id'] ?? 0);
        $email = trim((string) ($user['email'] ?? ''));
        $role = trim((string) ($user['role'] ?? ''));
        $password = (string) ($user['password'] ?? '');

        if ($userId < 1 || $email === '' || $role === '' || $password === '') {
            return;
        }

        $expires = time() + adminRememberCookieLifetime();
        $payload = $userId . '|' . $email . '|' . $role . '|' . $expires;
        $signature = hash_hmac('sha256', $payload . '|' . $password, adminRememberCookieSecret());
        $cookieValue = base64_encode($payload . '|' . $signature);

        setcookie(adminRememberCookieName(), $cookieValue, adminRememberCookieOptions($expires));
        $_COOKIE[adminRememberCookieName()] = $cookieValue;
    }
}

if (!function_exists('adminClearRememberCookie')) {
    function adminClearRememberCookie(): void
    {
        setcookie(adminRememberCookieName(), '', adminRememberCookieOptions(time() - 3600));
        unset($_COOKIE[adminRememberCookieName()]);
    }
}

if (!function_exists('adminRestoreRememberedSession')) {
    function adminRestoreRememberedSession(): void
    {
        if (isset($_SESSION['admin_user_id']) && strtolower((string) ($_SESSION['admin_role'] ?? '')) === 'admin') {
            return;
        }

        $cookieValue = $_COOKIE[adminRememberCookieName()] ?? '';

        if (!is_string($cookieValue) || trim($cookieValue) === '') {
            return;
        }

        $decoded = base64_decode($cookieValue, true);

        if ($decoded === false) {
            adminClearRememberCookie();
            return;
        }

        $parts = explode('|', $decoded);

        if (count($parts) !== 5) {
            adminClearRememberCookie();
            return;
        }

        [$userId, $email, $role, $expires, $signature] = $parts;

        if (!ctype_digit($userId) || !ctype_digit($expires) || (int) $expires < time() || strtolower($role) !== 'admin') {
            adminClearRememberCookie();
            return;
        }

        require_once __DIR__ . '/../config/db_connect.php';

        if (!isset($pdo) || !($pdo instanceof PDO)) {
            adminClearRememberCookie();
            return;
        }

        $statement = $pdo->prepare(
            'SELECT user_id, full_name, email, password, role
             FROM users
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $statement->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
        $statement->execute();
        $user = $statement->fetch();

        if (!$user) {
            adminClearRememberCookie();
            return;
        }

        $expectedPayload = (int) $user['user_id'] . '|' . (string) $user['email'] . '|' . (string) $user['role'] . '|' . (int) $expires;
        $expectedSignature = hash_hmac('sha256', $expectedPayload . '|' . (string) $user['password'], adminRememberCookieSecret());

        if (
            !hash_equals((string) $user['email'], $email)
            || !hash_equals(strtolower((string) $user['role']), strtolower($role))
            || !hash_equals($expectedSignature, $signature)
        ) {
            adminClearRememberCookie();
            return;
        }

        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int) $user['user_id'];
        $_SESSION['admin_full_name'] = (string) $user['full_name'];
        $_SESSION['admin_email'] = (string) $user['email'];
        $_SESSION['admin_role'] = (string) $user['role'];

        adminSetRememberCookie($user);
    }
}

if (!function_exists('adminAvailableLanguages')) {
    function adminAvailableLanguages(): array
    {
        return ['en', 'ar'];
    }
}

if (!function_exists('adminCurrentLanguage')) {
    function adminCurrentLanguage(): string
    {
        $allowedLanguages = adminAvailableLanguages();

        if (isset($_GET['lang']) && is_string($_GET['lang']) && in_array($_GET['lang'], $allowedLanguages, true)) {
            $_SESSION['admin_lang'] = $_GET['lang'];
            adminSetLanguageCookie($_GET['lang']);
        }

        if (isset($_SESSION['admin_lang']) && is_string($_SESSION['admin_lang']) && in_array($_SESSION['admin_lang'], $allowedLanguages, true)) {
            return $_SESSION['admin_lang'];
        }

        if (
            isset($_COOKIE[adminLanguageCookieName()])
            && is_string($_COOKIE[adminLanguageCookieName()])
            && in_array($_COOKIE[adminLanguageCookieName()], $allowedLanguages, true)
        ) {
            $_SESSION['admin_lang'] = $_COOKIE[adminLanguageCookieName()];
            return $_SESSION['admin_lang'];
        }

        $_SESSION['admin_lang'] = 'en';
        adminSetLanguageCookie('en');
        return 'en';
    }
}

if (!function_exists('adminIsRtl')) {
    function adminIsRtl(): bool
    {
        return adminCurrentLanguage() === 'ar';
    }
}

if (!function_exists('adminDirection')) {
    function adminDirection(): string
    {
        return adminIsRtl() ? 'rtl' : 'ltr';
    }
}

if (!function_exists('adminTranslations')) {
    function adminTranslations(): array
    {
        return [
            'en' => [
                'site_name' => 'Online Furniture Store',
                'admin_panel' => 'Admin Panel',
                'furniture_admin' => 'Furniture Admin',
                'elegant_inventory_control_panel' => 'Elegant inventory control panel',
                'manage_products_securely' => 'Manage products, inventory details, and admin activity securely.',
                'administrator' => 'Administrator',
                'manage_inventory' => 'Manage Inventory',
                'add_new_item' => 'Add New Item',
                'logout' => 'Logout',
                'signed_in_catalog_note' => 'Signed in to keep the catalog polished, stocked, and ready for shoppers.',
                'toggle_theme' => 'Toggle theme',
                'switch_to_english' => 'Switch to English',
                'switch_to_arabic' => 'Switch to Arabic',
                'language_en' => 'EN',
                'language_ar' => 'AR',
                'theme_light' => 'Switch to light mode',
                'theme_dark' => 'Switch to dark mode',

                'admin_login' => 'Admin Login',
                'admin_login_desc' => 'Secure access for catalog and inventory management.',
                'premium_furniture_store' => 'Premium Furniture Store',
                'shape_showroom_title' => 'Shape the showroom behind every elegant order.',
                'shape_showroom_desc' => 'Access the admin console to keep stock levels accurate, refine furniture details, and maintain a polished shopping experience for every customer.',
                'curated_inventory_control' => 'Curated inventory control',
                'curated_inventory_control_desc' => 'Update products, categories, pricing, and availability from one refined workspace.',
                'protected_admin_access' => 'Protected admin access',
                'protected_admin_access_desc' => 'Session-based authentication with secure password verification and guarded redirects.',
                'designed_for_clarity' => 'Designed for clarity',
                'designed_for_clarity_desc' => 'A luxury-inspired control panel with dark and light themes for focused work.',
                'secure_admin_sign_in' => 'Secure admin-only sign in',
                'inventory_ready_workflow' => 'Inventory-ready workflow',
                'built_in_theme_memory' => 'Built-in theme preference memory',
                'admin_portal' => 'Admin Portal',
                'welcome_back' => 'Welcome back',
                'sign_in_with_admin_account' => 'Sign in with your administrator account to manage the furniture catalog.',
                'admin_email' => 'Admin Email',
                'admin_email_placeholder' => 'admin@example.com',
                'password' => 'Password',
                'password_placeholder' => 'Enter your password',
                'show_password' => 'Show password',
                'hide_password' => 'Hide password',
                'sign_in' => 'Sign In',
                'please_enter_email_and_password' => 'Please enter both your admin email and password.',
                'please_enter_valid_email' => 'Please enter a valid email address.',
                'incorrect_admin_credentials' => 'Incorrect admin credentials. Please try again.',
                'logout_success' => 'You have been logged out successfully.',

                'inventory_dashboard' => 'Inventory Dashboard',
                'inventory_dashboard_desc' => 'Track furniture listings, stock movement, and product readiness from one premium workspace.',
                'admin_inventory_control' => 'Admin Inventory Control',
                'maintain_curated_catalog' => 'Maintain a curated catalog with precise stock visibility.',
                'maintain_curated_catalog_desc' => 'Review every furniture item, identify low-stock products quickly, and keep your storefront polished with fast edit and delete actions.',
                'add_furniture' => 'Add Furniture',
                'add_new_item_full' => 'Add New Item',
                'refresh_view' => 'Refresh View',
                'total_furniture_items' => 'Total Furniture Items',
                'total_units_in_stock' => 'Total Units in Stock',
                'estimated_inventory_value' => 'Estimated Inventory Value',
                'low_stock_alerts' => 'Low Stock Alerts',
                'furniture_collection' => 'Furniture Collection',
                'browse_filter_take_action' => 'Browse all products, filter the list, and take action instantly.',
                'search_inventory_placeholder' => 'Search by name, description, or category',
                'furniture_item' => 'Furniture Item',
                'category' => 'Category',
                'price' => 'Price',
                'stock' => 'Stock',
                'added' => 'Added',
                'actions' => 'Actions',
                'uncategorized' => 'Uncategorized',
                'units' => 'units',
                'no_description_available' => 'No description available.',
                'edit' => 'Edit',
                'delete' => 'Delete',
                'delete_confirm_question' => 'Delete this furniture item permanently?',
                'no_furniture_items_found' => 'No furniture items found',
                'no_search_results' => 'No results matched your search. Try a different keyword or clear the filter.',
                'inventory_empty' => 'Your inventory is currently empty. Add the first furniture item to start managing the catalog.',
                'clear_search' => 'Clear Search',

                'invalid_item_edit' => 'Invalid furniture item selected for editing.',
                'item_not_found_edit' => 'The furniture item you tried to edit was not found.',
                'invalid_item_delete' => 'Invalid furniture item selected for deletion.',
                'item_not_found_delete' => 'The furniture item you tried to delete was not found.',
                'delete_request_not_verified' => 'Delete request could not be verified.',
                'item_added_success' => 'Furniture item added successfully.',
                'item_updated_success' => 'Furniture item updated successfully.',
                'item_deleted_success' => 'Furniture item deleted successfully.',

                'new_inventory_entry' => 'New Inventory Entry',
                'add_showroom_ready_item' => 'Add a furniture piece that feels ready for the showroom.',
                'add_showroom_ready_item_desc' => 'Enter clean product details to keep the catalog organized, visually appealing, and easy for customers to browse.',
                'furniture_details' => 'Furniture Details',
                'furniture_details_desc' => 'Every field here feeds the admin catalog directly, so accuracy matters.',
                'item_name' => 'Item Name',
                'item_name_placeholder' => 'Modern Oak Dining Table',
                'item_name_required' => 'Furniture item name is required.',
                'item_name_too_long' => 'Item name must be 100 characters or fewer.',
                'item_name_hint' => 'Use a product name customers can recognize quickly.',
                'select_category' => 'Select category',
                'category_required' => 'Please choose a valid category.',
                'category_missing' => 'The selected category does not exist.',
                'category_hint' => 'Choose the furniture category already defined in the database.',
                'price_required' => 'Price must be a valid number greater than zero.',
                'price_hint' => 'Enter the selling price in your store catalog.',
                'stock_required' => 'Stock quantity must be a whole number of zero or more.',
                'stock_hint' => 'Keep this aligned with the real available inventory.',
                'description' => 'Description',
                'description_required' => 'Please add a short furniture description.',
                'description_placeholder' => 'Describe materials, style, dimensions, and the ideal room placement.',
                'description_hint' => 'A strong description improves both admin clarity and future storefront quality.',
                'image_url' => 'Image URL',
                'image_placeholder' => 'http://localhost/online_furniture_store/uploads/oak-table.jpg',
                'image_invalid' => 'Image must be a valid URL or left empty.',
                'image_hint' => 'Optional: add a local or hosted image URL for the product card.',
                'preview' => 'Preview',
                'image_preview_placeholder_title' => 'Image preview will appear here',
                'image_preview_placeholder_desc' => 'Paste a valid image URL to visualize the product before saving.',
                'save_furniture_item' => 'Save Furniture Item',
                'cancel' => 'Cancel',
                'review_highlighted_fields' => 'Please review the highlighted fields and correct the form before saving.',
                'back_to_inventory' => 'Back to Inventory',

                'edit_furniture' => 'Edit Furniture',
                'edit_furniture_item' => 'Edit Furniture Item',
                'edit_furniture_desc' => 'Refine pricing, stock, visuals, and category details for this furniture listing.',
                'update_inventory_entry' => 'Update Inventory Entry',
                'update_inventory_title' => 'Refine this furniture listing without losing control of the catalog.',
                'update_inventory_desc' => 'Adjust the item details below to keep pricing, stock levels, and presentation perfectly aligned with the current inventory.',
                'edit_furniture_details' => 'Edit Furniture Details',
                'edit_furniture_details_desc' => 'Update the product carefully to keep the storefront and admin records consistent.',
                'update_furniture_item' => 'Update Furniture Item',
                'review_highlighted_fields_update' => 'Please review the highlighted fields and correct the form before updating this item.',

                'delete_furniture' => 'Delete Furniture',
                'delete_furniture_item' => 'Delete Furniture Item',
                'delete_furniture_desc' => 'Review the selected item carefully before permanently removing it from the inventory.',
                'permanent_action' => 'Permanent Action',
                'confirm_delete_title' => 'Confirm before removing this furniture item from the catalog.',
                'confirm_delete_desc' => 'This action permanently deletes the selected product record from the inventory dashboard. Review the details below before continuing.',
                'item_ready_for_deletion' => 'Item Ready for Deletion',
                'item_ready_for_deletion_desc' => 'If you continue, this furniture item will no longer appear in the admin inventory list.',
                'delete_warning' => 'This change cannot be undone from the admin panel.',
                'delete_permanently' => 'Delete Permanently',
                'online_furniture_store_admin_module' => 'Online Furniture Store Admin Module',
                'online_furniture_store_admin_panel' => 'Online Furniture Store Admin Panel',
            ],
            'ar' => [
                'site_name' => 'متجر الأثاث الإلكتروني',
                'admin_panel' => 'لوحة التحكم',
                'furniture_admin' => 'إدارة المخزون',
                'elegant_inventory_control_panel' => 'لوحة أنيقة لإدارة المخزون',
                'manage_products_securely' => 'إدارة المنتجات والمخزون وأنشطة المشرف بشكل آمن.',
                'administrator' => 'المشرف',
                'manage_inventory' => 'إدارة المخزون',
                'add_new_item' => 'إضافة عنصر جديد',
                'logout' => 'تسجيل الخروج',
                'signed_in_catalog_note' => 'مرحبا بك نتمنى لك وقتا ممتعا',
                'toggle_theme' => 'تبديل المظهر',
                'switch_to_english' => 'التبديل إلى الإنجليزية',
                'switch_to_arabic' => 'التبديل إلى العربية',
                'language_en' => 'EN',
                'language_ar' => 'AR',
                'theme_light' => 'التبديل إلى الوضع الفاتح',
                'theme_dark' => 'التبديل إلى الوضع الداكن',

                'admin_login' => 'دخول المشرف',
                'admin_login_desc' => 'وصول آمن لإدارة الكتالوج والمخزون.',
                'premium_furniture_store' => 'متجر أثاث فاخر',
                'shape_showroom_title' => 'اصنع الواجهة الخلفية لكل طلب أنيق.',
                'shape_showroom_desc' => 'ادخل إلى لوحة المشرف للحفاظ على دقة المخزون وتحسين تفاصيل الأثاث وتقديم تجربة تسوق احترافية لكل عميل.',
                'curated_inventory_control' => 'تحكم دقيق بالمخزون',
                'curated_inventory_control_desc' => 'حدّث المنتجات والفئات والأسعار والتوافر من مساحة عمل واحدة أنيقة.',
                'protected_admin_access' => 'وصول إداري محمي',
                'protected_admin_access_desc' => 'تسجيل دخول يعتمد على الجلسات مع التحقق الآمن من كلمة المرور وإعادة التوجيه المحمية.',
                'designed_for_clarity' => 'مصمم للوضوح',
                'designed_for_clarity_desc' => 'لوحة تحكم بطابع فاخر مع وضعي الفاتح والداكن للتركيز أثناء العمل.',
                'secure_admin_sign_in' => 'تسجيل دخول خاص بالمشرف فقط',
                'inventory_ready_workflow' => 'سير عمل جاهز لإدارة المخزون',
                'built_in_theme_memory' => 'تذكر تلقائي لوضع العرض',
                'admin_portal' => 'بوابة المشرف',
                'welcome_back' => 'مرحبًا بعودتك',
                'sign_in_with_admin_account' => 'سجّل الدخول بحساب المشرف لإدارة كتالوج الأثاث.',
                'admin_email' => 'البريد الإلكتروني للمشرف',
                'admin_email_placeholder' => 'admin@example.com',
                'password' => 'كلمة المرور',
                'password_placeholder' => 'أدخل كلمة المرور',
                'show_password' => 'إظهار كلمة المرور',
                'hide_password' => 'إخفاء كلمة المرور',
                'sign_in' => 'تسجيل الدخول',
                'please_enter_email_and_password' => 'يرجى إدخال البريد الإلكتروني وكلمة المرور.',
                'please_enter_valid_email' => 'يرجى إدخال بريد إلكتروني صحيح.',
                'incorrect_admin_credentials' => 'بيانات دخول المشرف غير صحيحة. حاول مرة أخرى.',
                'logout_success' => 'تم تسجيل الخروج بنجاح.',

                'inventory_dashboard' => 'لوحة المخزون',
                'inventory_dashboard_desc' => 'تابع منتجات الأثاث وحركة المخزون وجاهزية العناصر من مساحة عمل واحدة أنيقة.',
                'admin_inventory_control' => 'التحكم الإداري بالمخزون',
                'maintain_curated_catalog' => 'حافظ على مخزون منظم مع رؤية دقيقة للمخزون.',
                'maintain_curated_catalog_desc' => 'راجع كل عنصر أثاث وحدد المنتجات منخفضة المخزون بسرعة وحافظ على واجهة المتجر بخيارات تعديل وحذف سريعة.',
                'add_furniture' => 'إضافة أثاث',
                'add_new_item_full' => 'إضافة عنصر جديد',
                'refresh_view' => 'تحديث العرض',
                'total_furniture_items' => 'إجمالي عناصر الأثاث',
                'total_units_in_stock' => 'إجمالي الوحدات بالمخزون',
                'estimated_inventory_value' => 'القيمة التقديرية للمخزون',
                'low_stock_alerts' => 'تنبيهات انخفاض المخزون',
                'furniture_collection' => 'مجموعة الأثاث',
                'browse_filter_take_action' => 'استعرض كل المنتجات، وفلتر القائمة، واتخذ الإجراء مباشرة.',
                'search_inventory_placeholder' => 'ابحث بالاسم أو الوصف أو الفئة',
                'furniture_item' => 'قطعة الأثاث',
                'category' => 'الفئة',
                'price' => 'السعر',
                'stock' => 'المخزون',
                'added' => 'تاريخ الإضافة',
                'actions' => 'الإجراءات',
                'uncategorized' => 'بدون فئة',
                'units' => 'وحدة',
                'no_description_available' => 'لا يوجد وصف متاح.',
                'edit' => 'تعديل',
                'delete' => 'حذف',
                'delete_confirm_question' => 'هل تريد حذف عنصر الأثاث هذا نهائيًا؟',
                'no_furniture_items_found' => 'لم يتم العثور على عناصر أثاث',
                'no_search_results' => 'لا توجد نتائج مطابقة لبحثك. جرّب كلمة مختلفة أو امسح الفلتر.',
                'inventory_empty' => 'المخزون فارغ حاليًا. أضف أول عنصر أثاث لبدء إدارة الكتالوج.',
                'clear_search' => 'مسح البحث',

                'invalid_item_edit' => 'تم اختيار عنصر غير صالح للتعديل.',
                'item_not_found_edit' => 'عنصر الأثاث الذي حاولت تعديله غير موجود.',
                'invalid_item_delete' => 'تم اختيار عنصر غير صالح للحذف.',
                'item_not_found_delete' => 'عنصر الأثاث الذي حاولت حذفه غير موجود.',
                'delete_request_not_verified' => 'تعذر التحقق من طلب الحذف.',
                'item_added_success' => 'تمت إضافة عنصر الأثاث بنجاح.',
                'item_updated_success' => 'تم تحديث عنصر الأثاث بنجاح.',
                'item_deleted_success' => 'تم حذف عنصر الأثاث بنجاح.',

                'new_inventory_entry' => 'إضافة عنصر للمخزون',
                'add_showroom_ready_item' => 'أضف قطعة أثاث جاهزة للعرض باحترافية.',
                'add_showroom_ready_item_desc' => 'أدخل تفاصيل نظيفة ومنظمة للحفاظ على الكتالوج جذابًا وسهل التصفح.',
                'furniture_details' => 'تفاصيل الأثاث',
                'furniture_details_desc' => 'كل حقل هنا ينعكس مباشرة على كتالوج الإدارة، لذلك الدقة مهمة.',
                'item_name' => 'اسم العنصر',
                'item_name_placeholder' => 'طاولة طعام خشب بلوط عصرية',
                'item_name_required' => 'اسم عنصر الأثاث مطلوب.',
                'item_name_too_long' => 'يجب ألا يتجاوز اسم العنصر 100 حرف.',
                'item_name_hint' => 'استخدم اسمًا واضحًا يتعرف عليه العملاء بسهولة.',
                'select_category' => 'اختر الفئة',
                'category_required' => 'يرجى اختيار فئة صحيحة.',
                'category_missing' => 'الفئة المحددة غير موجودة.',
                'category_hint' => 'اختر فئة الأثاث المسجلة مسبقًا في قاعدة البيانات.',
                'price_required' => 'يجب أن يكون السعر رقمًا صالحًا أكبر من صفر.',
                'price_hint' => 'أدخل سعر البيع المعتمد في المتجر.',
                'stock_required' => 'يجب أن تكون كمية المخزون رقمًا صحيحًا يساوي صفر أو أكثر.',
                'stock_hint' => 'احرص أن تطابق هذه القيمة المخزون الحقيقي المتاح.',
                'description' => 'الوصف',
                'description_required' => 'يرجى إضافة وصف مختصر لقطعة الأثاث.',
                'description_placeholder' => 'اكتب المواد المستخدمة والطراز والأبعاد وأفضل مكان مناسب للقطعة.',
                'description_hint' => 'الوصف الجيد يحسن وضوح الإدارة وجودة عرض المنتج لاحقًا.',
                'image_url' => 'رابط الصورة',
                'image_placeholder' => 'http://localhost/online_furniture_store/uploads/oak-table.jpg',
                'image_invalid' => 'يجب أن يكون رابط الصورة صالحًا أو اتركه فارغًا.',
                'image_hint' => 'اختياري: أضف رابط صورة محلي أو مستضاف لبطاقة المنتج.',
                'preview' => 'المعاينة',
                'image_preview_placeholder_title' => 'ستظهر معاينة الصورة هنا',
                'image_preview_placeholder_desc' => 'ألصق رابط صورة صالحًا لمعاينة المنتج قبل الحفظ.',
                'save_furniture_item' => 'حفظ عنصر الأثاث',
                'cancel' => 'إلغاء',
                'review_highlighted_fields' => 'يرجى مراجعة الحقول المظللة وتصحيح النموذج قبل الحفظ.',
                'back_to_inventory' => 'العودة إلى المخزون',

                'edit_furniture' => 'تعديل الأثاث',
                'edit_furniture_item' => 'تعديل عنصر أثاث',
                'edit_furniture_desc' => 'حدّث السعر والمخزون والصورة والفئة لهذا العنصر.',
                'update_inventory_entry' => 'تحديث عنصر المخزون',
                'update_inventory_title' => 'عدّل هذا العنصر دون فقدان السيطرة على الكتالوج.',
                'update_inventory_desc' => 'غيّر التفاصيل التالية للحفاظ على السعر والمخزون والعرض البصري متوافقين مع الواقع.',
                'edit_furniture_details' => 'تعديل تفاصيل الأثاث',
                'edit_furniture_details_desc' => 'حدّث المنتج بعناية للحفاظ على اتساق الواجهة والسجلات الإدارية.',
                'update_furniture_item' => 'تحديث عنصر الأثاث',
                'review_highlighted_fields_update' => 'يرجى مراجعة الحقول المظللة وتصحيح النموذج قبل تحديث هذا العنصر.',

                'delete_furniture' => 'حذف الأثاث',
                'delete_furniture_item' => 'حذف عنصر أثاث',
                'delete_furniture_desc' => 'راجع العنصر المحدد بعناية قبل إزالته نهائيًا من المخزون.',
                'permanent_action' => 'إجراء نهائي',
                'confirm_delete_title' => 'أكد قبل إزالة عنصر الأثاث هذا من الكتالوج.',
                'confirm_delete_desc' => 'هذا الإجراء يحذف سجل المنتج المحدد نهائيًا من لوحة المخزون. راجع التفاصيل قبل المتابعة.',
                'item_ready_for_deletion' => 'عنصر جاهز للحذف',
                'item_ready_for_deletion_desc' => 'إذا تابعت، فلن يظهر هذا العنصر بعد الآن في قائمة المخزون.',
                'delete_warning' => 'لا يمكن التراجع عن هذا التغيير من لوحة الإدارة.',
                'delete_permanently' => 'حذف نهائي',
                'online_furniture_store_admin_module' => 'وحدة إدارة متجر الأثاث الإلكتروني',
                'online_furniture_store_admin_panel' => 'لوحة إدارة متجر الأثاث الإلكتروني',
            ],
        ];
    }
}

if (!function_exists('adminHasTranslation')) {
    function adminHasTranslation(string $key): bool
    {
        $translations = adminTranslations();
        $language = adminCurrentLanguage();

        return isset($translations[$language][$key]);
    }
}

if (!function_exists('adminTrans')) {
    function adminTrans(string $key, array $replacements = []): string
    {
        $translations = adminTranslations();
        $language = adminCurrentLanguage();
        $text = $translations[$language][$key] ?? $translations['en'][$key] ?? $key;

        foreach ($replacements as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }

        return $text;
    }
}

if (!function_exists('adminUrlWithLang')) {
    function adminUrlWithLang(string $language, ?string $path = null): string
    {
        $target = $path ?? ($_SERVER['REQUEST_URI'] ?? '');
        $parts = parse_url($target);

        $route = $parts['path'] ?? $target;
        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query['lang'] = $language;
        $queryString = http_build_query($query);

        return $route . ($queryString !== '' ? '?' . $queryString : '');
    }
}

adminRestoreRememberedSession();
