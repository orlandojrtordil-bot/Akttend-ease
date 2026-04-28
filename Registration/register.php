   <?php
/**
 * Attend Ease - Registration Page
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
        $role = trim($_POST['role'] ?? 'student');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($fullName) || empty($email) || empty($password)) {
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
                $error = 'This email is already registered. <a href="' . BASE_URL . 'Registration/login.php">Log in instead?</a>';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                if ($role === 'teacher') {
                    $subject = trim($_POST['subject'] ?? '');
                    if (empty($subject)) {
                        $error = 'Please enter the subject you teach.';
                    } else {
                        $username = strtolower(preg_replace('/[^a-z0-9]/', '', substr($email, 0, strpos($email, '@'))));
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
                } else {
                    $username = strtolower(preg_replace('/[^a-z0-9]/', '', $fullName));
                    if (strlen($username) < 3) {
                        $username = 'student' . rand(1000, 9999);
                    }
                    $baseUsername = $username;
                    $suffix = 1;
                    while (dbValue("SELECT COUNT(*) FROM users WHERE username = ?", "s", [$username]) > 0) {
                        $username = $baseUsername . $suffix;
                        $suffix++;
                    }

                    $studentId = trim($_POST['student_id'] ?? '');

                    $newId = dbInsert(
                        "INSERT INTO users (username, email, password_hash, full_name, student_id, role) VALUES (?, ?, ?, ?, ?, 'student')",
                        "sssss",
                        [$username, $email, $passwordHash, $fullName, $studentId]
                    );

                    if ($newId) {
                        $success = 'Welcome, ' . htmlspecialchars($fullName) . '! Your student account is ready.';
                    } else {
                        $error = 'Something went wrong. Please try again.';
                    }
                }
            }
        }
    }
}

$pageTitle = 'Sign Up | ' . APP_NAME;
$basePath = '../';
$pageCss = 'auth';
include '../includes/header.php';
?>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 480px;">
            <h1 class="auth-title">Create Your Account</h1>
            <p class="auth-subtitle">Join Attend Ease in seconds</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="text-align:center;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">&#127881;</div>
                    <?php echo $success; ?>
                    <a href="<?php echo BASE_URL; ?>Registration/login.php" class="btn btn-admin btn-block" style="margin-top:1rem;">Log In Now</a>
                </div>
            <?php else: ?>
                <form method="POST" action="" class="auth-form" id="signupForm">
                    <?php echo csrfField(); ?>

                    <div class="form-group">
                        <label>I am a</label>
                        <div class="role-selector">
                            <label class="role-option active" data-role="student">
                                <input type="radio" name="role" value="student" checked>
                                <span>&#127891; Student</span>
                            </label>
                            <label class="role-option" data-role="teacher">
                                <input type="radio" name="role" value="teacher">
                                <span>&#127979; Teacher</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Juan Dela Cruz" required
                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" autocomplete="name">
                    </div>

                    <div class="form-group" id="studentIdGroup">
                        <label for="student_id">Student ID <span class="optional-label">(optional)</span></label>
                        <input type="text" id="student_id" name="student_id" placeholder="e.g., 2024-00001"
                            value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                    </div>

                    <div class="form-group hidden" id="subjectGroup">
                        <label for="subject">Subject You Teach *</label>
                        <input type="text" id="subject" name="subject" placeholder="e.g., Computer Science"
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
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required autocomplete="new-password">
                        <div class="password-match" id="passwordMatch"></div>
                    </div>

                    <button type="submit" class="btn btn-admin btn-block" id="submitBtn" style="margin-top:0.5rem;">
                        Create Account
                    </button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p>Already have an account? <a href="<?php echo BASE_URL; ?>Registration/login.php">Log In</a></p>
                <p style="margin-top:0.5rem;"><a href="<?php echo BASE_URL; ?>index.php">&#8592; Back to Home</a></p>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const form = document.getElementById('signupForm');
        const roleOptions = document.querySelectorAll('.role-option');
        const studentIdGroup = document.getElementById('studentIdGroup');
        const subjectGroup = document.getElementById('subjectGroup');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthDiv = document.getElementById('passwordStrength');
        const matchDiv = document.getElementById('passwordMatch');

        roleOptions.forEach(opt => {
            opt.addEventListener('click', function() {
                roleOptions.forEach(o => o.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input').checked = true;
                const role = this.dataset.role;
                if (role === 'teacher') {
                    studentIdGroup.classList.add('hidden');
                    subjectGroup.classList.remove('hidden');
                    document.getElementById('subject').required = true;
                    document.getElementById('student_id').required = false;
                } else {
                    studentIdGroup.classList.remove('hidden');
                    subjectGroup.classList.add('hidden');
                    document.getElementById('subject').required = false;
                    document.getElementById('student_id').required = false;
                }
            });
        });

        passwordInput.addEventListener('input', function() {
            const val = this.value;
            if (val.length === 0) { strengthDiv.classList.remove('show'); strengthDiv.innerHTML = ''; return; }
            strengthDiv.classList.add('show');
            let strength = 0;
            if (val.length >= 6) strength++;
            if (val.length >= 10) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;
            let cls = 'weak', text = 'Weak';
            if (strength >= 4) { cls = 'strong'; text = 'Strong'; }
            else if (strength >= 2) { cls = 'fair'; text = 'Fair'; }
            strengthDiv.innerHTML = '<div class="password-strength-bar ' + cls + '"></div>' +
                '<div class="password-strength-text" style="color:' + (cls==='strong'?'#2d6a4f':cls==='fair'?'#f39c12':'#9b2226') + '">' + text + '</div>';
        });

        function checkMatch() {
            const p = passwordInput.value, c = confirmInput.value;
            if (c.length === 0) { matchDiv.textContent = ''; matchDiv.className = 'password-match'; return; }
            if (p === c) { matchDiv.textContent = 'Passwords match'; matchDiv.className = 'password-match match'; }
            else { matchDiv.textContent = 'Passwords do not match'; matchDiv.className = 'password-match nomatch'; }
        }
        confirmInput.addEventListener('input', checkMatch);
        passwordInput.addEventListener('input', checkMatch);

        form.addEventListener('submit', function(e) {
            const role = document.querySelector('.role-option.active').dataset.role;
            if (role === 'teacher') {
                const subject = document.getElementById('subject').value.trim();
                if (!subject) { e.preventDefault(); alert('Please enter the subject you teach.'); document.getElementById('subject').focus(); return false; }
            }
        });
    })();
    </script>

<?php include '../includes/footer.php'; ?>

