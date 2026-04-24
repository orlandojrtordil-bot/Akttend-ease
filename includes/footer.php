<?php
/**
 * Attend Ease - Footer Template
 * 
 * Shared footer. Include at the bottom of every page after header.php.
 * 
 * @package AttendEase
 * @subpackage Templates
 */

if (!defined('ATTEND_EASE')) {
    require_once __DIR__ . '/../config.php';
}
?>
    </main>
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> Capstone Project. BS Information Technology.</p>
        <p>Developed for efficient, accurate, and transparent attendance tracking.</p>
        <p>Version <?php echo APP_VERSION; ?></p>
    </footer>
</body>
</html>

