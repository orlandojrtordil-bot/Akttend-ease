<?php
/**
 * Attend Ease - Header Template
 * 
 * Shared header with responsive navigation.
 * Set $pageTitle and $basePath before including this file.
 * 
 * @package AttendEase
 * @subpackage Templates
 */

if (!defined('ATTEND_EASE')) {
    require_once __DIR__ . '/../config.php';
}

$pageTitle = $pageTitle ?? APP_NAME;
$basePath  = $basePath ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="QR Code-Based Smart Attendance Monitoring System">
    <meta name="theme-color" content="#102E4A">
    <title><?php echo sanitizeInput($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css?v=<?php echo APP_VERSION; ?>">
<?php if (!empty($pageCss)): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/<?php echo $pageCss; ?>.css?v=<?php echo APP_VERSION; ?>">
<?php endif; ?>
    <!-- Leaflet Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>
<body>
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="nav-brand">
            <a href="<?php echo BASE_URL; ?>index.php"><?php echo APP_NAME; ?></a>
        </div>
        <div class="nav-actions">
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle light and dark mode" onclick="toggleTheme()">
                <span id="themeIcon">🌙</span>
            </button>
            <button class="nav-toggle" type="button" aria-label="Toggle navigation menu" aria-expanded="false" onclick="toggleNav()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
        <ul class="nav-links" id="nav-menu">
            <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
            
            <?php if (isStudent()): ?>
            <li><a href="<?php echo BASE_URL; ?>student.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>checkin.php">&#128205; Check-In</a></li>
            <li><a href="<?php echo BASE_URL; ?>scan.php">Scan QR</a></li>
            <li><a href="<?php echo BASE_URL; ?>my_attendance.php">My Records</a></li>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
            <li><a href="<?php echo BASE_URL; ?>admin.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin_locations.php">&#127759; Locations</a></li>
            <li><a href="<?php echo BASE_URL; ?>reports/view.php">Reports</a></li>
            <?php endif; ?>
            
            <?php if (isLoggedIn()): ?>
            <li><span class="nav-user"><?php echo sanitizeInput(getCurrentUser()['full_name'] ?? ''); ?></span></li>
            <li><a href="<?php echo BASE_URL; ?>Registration/logout.php">Log Out</a></li>
            <?php else: ?>
            <li><a href="<?php echo BASE_URL; ?>Registration/login.php">Log In</a></li>
            <li><a href="<?php echo BASE_URL; ?>Registration/register.php">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <main class="main-content">
    <script>
        function toggleNav() {
            const menu = document.getElementById('nav-menu');
            const toggle = document.querySelector('.nav-toggle');
            const isOpen = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen);
        }

        function applyTheme(theme) {
            const root = document.documentElement;
            root.setAttribute('data-theme', theme);
            localStorage.setItem('attendEaseTheme', theme);
            document.getElementById('themeIcon').textContent = theme === 'dark' ? '☀️' : '🌙';
        }

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        }

        function loadTheme() {
            const saved = localStorage.getItem('attendEaseTheme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = saved || (prefersDark ? 'dark' : 'light');
            applyTheme(theme);
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadTheme();
        });

        document.addEventListener('click', function(e) {
            const nav = document.querySelector('.navbar');
            const menu = document.getElementById('nav-menu');
            if (!nav.contains(e.target) && menu.classList.contains('open')) {
                menu.classList.remove('open');
                document.querySelector('.nav-toggle').setAttribute('aria-expanded', 'false');
            }
        });
    </script>

