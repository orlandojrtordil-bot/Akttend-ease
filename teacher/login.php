<?php
/**
 * Attend Ease - Teacher Login
 *
 * @package AttendEase
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if (isLoggedIn()) {
    redirectBasedOnRole();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $user = dbRow(
                "SELECT id, username, password_hash, full_name, role, email, subject FROM users WHERE (username = ? OR email = ?) AND role IN ('teacher', 'admin')",
                "ss",
                [$login, $login]
            );

            if ($user && password_verify($password, $user['password_hash'])) {
                setUserSession($user);
                session_regenerate_id(true);
                redirectBasedOnRole();
                exit;
            } else {
                $error = 'Invalid teacher credentials. Please check your username/email and password.';
            }
        }
    }
}

$pageTitle = 'Teacher Login | ' . APP_NAME;
$basePath = '../';
$pageCss = 'auth';
include '../includes/header.php';
?>
    <div class="auth-container">
        <div class="auth-card">
            <div style="text-align:center;margin-bottom:1.5rem;">
                <div style="font-size:3rem;">&#127979;</div>
                <h1 class="auth-title">Teacher Login</h1>
                <p class="auth-subtitle">Access your admin dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="login">Username or Email</label>
                    <input type="text" id="login" name="login" placeholder="Enter your username or email" required
                        value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-admin btn-block">Log In as Teacher</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="<?php echo BASE_URL; ?>teacher/register.php">Sign Up as Teacher</a></p>
                <p><a href="<?php echo BASE_URL; ?>student/login.php">I am a Student</a></p>
                <p><a href="<?php echo BASE_URL; ?>index.php">Back to Home</a></p>
            </div>
    </div>
<?php include '../includes/footer.php'; ?>
