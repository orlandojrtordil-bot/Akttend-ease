<?php
/**
 * Attend Ease - Student Profile Editor
 * 
 * @package AttendEase
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

requireAuth('student');

$user = dbRow("SELECT * FROM users WHERE id = ?", "i", [$_SESSION['user_id']]);
if (!$user) {
    header('Location: logout.php');
    exit;
}

$message = '';
$error = '';

$uploadDir = __DIR__ . '/assets/images/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $studentId = trim($_POST['student_id'] ?? '');
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($fullName) || empty($email)) {
            $error = 'Full name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $picturePath = $user['profile_picture'];
            
            if (!empty($_FILES['profile_picture']['tmp_name'])) {
                $file = $_FILES['profile_picture'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($file['type'], $allowedTypes)) {
                    $error = 'Only JPG, PNG, and GIF files are allowed.';
                } elseif ($file['size'] > $maxSize) {
                    $error = 'File size must be less than 2MB.';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                    $destination = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/' . $user['profile_picture'])) {
                            unlink(__DIR__ . '/' . $user['profile_picture']);
                        }
                        $picturePath = 'assets/images/profiles/' . $filename;
                    } else {
                        $error = 'Failed to upload image. Please try again.';
                    }
                }
            }
            
            if (empty($error)) {
                $updateFields = [$fullName, $email, $studentId, $picturePath, $_SESSION['user_id']];
                $updateSql = "UPDATE users SET full_name = ?, email = ?, student_id = ?, profile_picture = ? WHERE id = ?";
                
                if (!empty($newPassword)) {
                    if (empty($currentPassword) || !password_verify($currentPassword, $user['password_hash'])) {
                        $error = 'Current password is incorrect.';
                    } elseif ($newPassword !== $confirmPassword) {
                        $error = 'New passwords do not match.';
                    } elseif (strlen($newPassword) < 6) {
                        $error = 'New password must be at least 6 characters.';
                    } else {
                        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateSql = "UPDATE users SET full_name = ?, email = ?, student_id = ?, profile_picture = ?, password_hash = ? WHERE id = ?";
                        $updateFields = [$fullName, $email, $studentId, $picturePath, $passwordHash, $_SESSION['user_id']];
                    }
                }
                
                if (empty($error)) {
                    $types = str_repeat('s', count($updateFields) - 1) . 'i';
                    $success = dbExecute($updateSql, $types, $updateFields);
                    
                    if ($success) {
                        $_SESSION['full_name'] = $fullName;
                        $message = 'Profile updated successfully.';
                        $user = dbRow("SELECT * FROM users WHERE id = ?", "i", [$_SESSION['user_id']]);
                    } else {
                        $error = 'Failed to update profile.';
                    }
                }
            }
        }
    }
}

$initials = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
$avatarPath = !empty($user['profile_picture']) && file_exists(__DIR__ . '/' . $user['profile_picture'])
    ? $user['profile_picture'] 
    : null;

$pageTitle = 'Edit Profile | ' . APP_NAME;
include 'includes/header.php';
?>
    <div class="container">
        <h1 class="page-title">Edit Profile</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="dashboard-grid" style="max-width: 700px; margin: 0 auto;">
            <div class="card profile-card">
                <div class="profile-picture-wrapper">
                    <?php if ($avatarPath): ?>
                        <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Profile" class="profile-picture">
                    <?php else: ?>
                        <div class="profile-picture-placeholder"><?php echo htmlspecialchars($initials); ?></div>
                    <?php endif; ?>
                </div>
                <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge badge-student">Student</span>
            </div>
            
            <div class="card edit-profile-card">
                <h2>Update Information</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    
                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                        <small class="form-hint">JPG, PNG, or GIF. Max 2MB.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>">
                    </div>
                    
                    <hr class="form-divider">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--midnight);">Change Password</h3>
                    <p class="text-muted" style="margin-bottom: 1rem;">Leave blank to keep current password.</p>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-admin">Save Changes</button>
                        <
