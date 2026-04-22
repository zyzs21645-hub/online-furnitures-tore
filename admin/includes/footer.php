<?php
declare(strict_types=1);

require_once __DIR__ . '/localization.php';

$showSidebar = isset($showSidebar) ? (bool) $showSidebar : true;
?>
<?php if ($showSidebar): ?>
            <footer class="content-card text-muted fade-up delay-1" style="margin-top: 24px; text-align: center;">
                <p style="margin: 0;"><?php echo htmlspecialchars(adminTrans('online_furniture_store_admin_panel'), ENT_QUOTES, 'UTF-8'); ?></p>
            </footer>
        </main>
    </div>
<?php else: ?>
    <div class="login-footer-note"><?php echo htmlspecialchars(adminTrans('online_furniture_store_admin_module'), ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<script src="../assets/js/admin_script.js"></script>
</body>
</html>
