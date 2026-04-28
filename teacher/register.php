<?php
/**
 * Attend Ease - Teacher Registration
 *
 * @package AttendEase
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if (isLoggedIn()) {
    redirectBasedOnRole();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please refresh and try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $subject = trim($_POST['subject'] ?? '');

        if (empty($fullName) || empty($email) || empty($password) || empty($subject)) {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $existing = dbValue("SELECT COUNT(*) FROM users WHERE email = ?", "s", [$email]);
            if ($existing > 0) {
                $error = 'This email is already registered. <a href="' . BASE_URL . 'teacher/login.php">Log in instead?</a>';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $username = strtolower(preg_replace('/[^a-z0-9]/', '', substr($email, 0, strpos($email, '@'))));
                if (strlen($username) < 3) {
                    $username = 'teacher' . rand(1000, 9999);
                }
                $baseUsername = $username;
                $suffix = 1;
                while (dbValue("SELECT COUNT(*) FROM users WHERE username = ?", "s", [$username]) > 0) {
                    $username = $baseUsername . $suffix;
                    $suffix++;
                }

                $newId = dbInsert(
                    "INSERT INTO users (username, email, password_hash, full_name, subject, role) VALUES (?, ?, ?, ?, ?, 'teacher')",
                    "sssss",
                    [$username, $email, $passwordHash, $fullName, $subject]
                );

                if ($newId) {
                    $success = 'Welcome, ' . htmlspecialchars($fullName) . '! Your teacher account is ready.';
                } else {
                    $error = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Teacher Sign Up | ' . APP_NAME;
$basePath = '../';
$pageCss = 'auth';
include '../includes/header.php';
?>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 480px;">
            <div style="text-align:center;margin-bottom:1.5rem;">
                <div style="font-size:3rem;">&#127979;</div>
                <h1 class="auth-title">Teacher Sign Up</h1>
                <p class="auth-subtitle">Create your teacher account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="text-align:center;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">&#127881;</div>
                    <?php echo $success; ?>
                    <a href="<?php echo BASE_URL; ?>teacher/login.php" class="btn btn-admin btn-block" style="margin-top:1rem;">Log In Now</a>
                </div>
            <?php else: ?>
                <form method="POST" action="" class="auth-form" id="signupForm">
                    <?php echo csrfField(); ?>

                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Prof. Juan Dela Cruz" required
                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" autocomplete="name">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject You Teach *</label>
                        <input type="text" id="subject" name="subject" placeholder="e.g., Computer Science" required
                            value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" placeholder="you@school.edu" required
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" placeholder="Min. 6 characters" required autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-admin btn-block" style="margin-top:0.5rem;">
                        Create Teacher Account
                    </button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p>Already have an account? <a href="<?php echo BASE_URL; ?>teacher/login.php">Log In</a></p>
                <p><a href="<?php echo BASE_URL; ?>student/register.php">Sign Up as Student</a></p>
                <p><a href="<?php echo BASE_URL; ?>index.php">Back to Home</a></p>
            </div>
</div>
    </div>
<?php include '../includes/footer.php'; ?>
