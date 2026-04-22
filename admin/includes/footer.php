<?php
declare(strict_types=1);

$showSidebar = isset($showSidebar) ? (bool) $showSidebar : true;
?>
<?php if ($showSidebar): ?>
            <footer class="content-card text-muted fade-up delay-1" style="margin-top: 24px; text-align: center;">
                <p style="margin: 0;">Online Furniture Store Admin Panel</p>
            </footer>
        </main>
    </div>
<?php else: ?>
    <div class="login-footer-note">Online Furniture Store Admin Module</div>
<?php endif; ?>
<script src="../assets/js/admin_script.js"></script>
</body>
</html>
