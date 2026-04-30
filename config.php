<?php
/**
 * Attend Ease - Configuration File
 * 
 * Centralized configuration for database, security, and application settings.
 * 
 * @package AttendEase
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ATTEND_EASE')) {
    define('ATTEND_EASE', true);
}

// Environment: 'development' or 'production'
define('APP_ENV', 'production');

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'attend_ease');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Attend Ease');
define('APP_VERSION', '1.0.0');
define('SESSION_EXPIRY_HOURS', 24);
define('ATTENDANCE_DUPLICATE_MINUTES', 5);
define('RECORDS_PER_PAGE', 25);

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');

// Base URL calculation - always points to project root
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Get directory of current script and normalize
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath = dirname($scriptPath);
$basePath = str_replace('\\', '/', $basePath);

// Normalize: ensure it starts with / and ends with /
if ($basePath === '.' || $basePath === '') {
    $basePath = '/';
} else {
    $basePath = rtrim($basePath, '/') . '/';
}

// Strip known subdirectories to find project root
$subdirs = ['/reports/', '/includes/', '/assets/', '/data/', '/api/', '/Registration/', '/student/', '/teacher/'];
foreach ($subdirs as $sub) {
    $pos = strpos($basePath, $sub);
    if ($pos !== false) {
        $basePath = substr($basePath, 0, $pos + 1);
        break;
    }
}

define('BASE_URL', $protocol . '://' . $host . $basePath);

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid
 */
function validateCsrfToken(string $token): bool
{
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF token input field
 * 
 * @return string HTML input field
 */
function csrfField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Sanitize user input
 * 
 * @param string $input Raw input
 * @return string Sanitized input
 */
function sanitizeInput(string $input): string
{
    $input = trim($input);
    $input = stripslashes($input);
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Set security headers
 */
function setSecurityHeaders(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

setSecurityHeaders();

/* ============================================
   AUTHENTICATION FUNCTIONS
   ============================================ */

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 * 
 * @return array|null
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['user_username'] ?? null,
        'full_name' => $_SESSION['user_full_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? 'student',
        'email' => $_SESSION['user_email'] ?? null,
    ];
}

/**
 * Check if current user is an admin
 * 
 * @return bool
 */
function isAdmin(): bool
{
    return isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['admin', 'teacher'], true);
}

function isTeacher(): bool
{
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'teacher';
}

function isStudent(): bool
{
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'student';
}

/**
 * Require login to access a page
 * Redirects to Registration/login.php if not authenticated
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . 'Registration/login.php');
        exit;
    }
}

/**
 * Alias for requireLogin()
 */
function requireAuth(): void
{
    requireLogin();
}

/**
 * Require admin role to access a page
 * Redirects to home if not an admin
 */
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

/**
 * Require student role to access a page
 * Redirects to home if not a student
 */
function requireStudent(): void
{
    requireLogin();
    if (!isStudent()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

/**
 * Require teacher role to access a page
 * Redirects to home if not a teacher
 */
function requireTeacher(): void
{
    requireLogin();
    if (!isTeacher()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

/**
 * Set user session after successful login
 * 
 * @param array $user User record from database
 */
function setUserSession(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_full_name'] = $user['full_name'] ?? $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['login_time'] = time();
}

/**
 * Clear user session (logout)
 */
function clearUserSession(): void
{
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_username'],
        $_SESSION['user_full_name'],
        $_SESSION['user_role'],
        $_SESSION['user_email'],
        $_SESSION['login_time']
    );
}

/**
 * Redirect user based on their role
 */
function redirectBasedOnRole(): void
{
    $role = $_SESSION['user_role'] ?? 'student';
    switch ($role) {
        case 'admin':
            header('Location: ' . BASE_URL . 'admin.php');
            break;
        case 'teacher':
            header('Location: ' . BASE_URL . 'admin.php');
            break;
        case 'student':
            header('Location: ' . BASE_URL . 'student.php');
            break;
        default:
            header('Location: ' . BASE_URL . 'index.php');
    }
    exit;
}

/**
 * Write audit log entry using PDO
 * @param string $action Action name
 * @param string|null $details Details text
 * @param int|null $entityId Related entity ID
 * @param string|null $entityType Entity type
 */
function auditLog(string $action, ?string $details = null, ?int $entityId = null, ?string $entityType = null): void
{
    static $pdo = null;
    
    try {
        if ($pdo === null) {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $entityType, $entityId, $ip, $ua, $details]);
    } catch (PDOException $e) {
        error_log("Audit log failed: " . $e->getMessage());
        $pdo = null; // Reset connection on failure
    }
}
