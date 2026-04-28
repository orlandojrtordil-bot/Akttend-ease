<?php
/**
 * Attend Ease - Student Dashboard
 * 
 * @package AttendEase
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

requireStudent();

$user = dbRow(
    "SELECT u.*, 
        (SELECT COUNT(*) FROM attendance WHERE student_id = u.student_id) as attendance_count,
        (SELECT COUNT(DISTINCT session_code) FROM attendance WHERE student_id = u.student_id) as sessions_attended
    FROM users u WHERE u.id = ?",
    "i",
    [$_SESSION['user_id']]
);

if (!$user) {
    header('Location: logout.php');
    exit;
}

$attendance = dbQuery(
    "SELECT a.*, s.session_name 
     FROM attendance a 
     LEFT JOIN sessions s ON a.session_code = s.session_code 
     WHERE a.student_id = ? 
     ORDER BY a.scan_time DESC 
     LIMIT 10",
    "s",
    [$user['student_id']]
);

$initials = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
$avatarPath = !empty($user['profile_picture']) && file_exists(__DIR__ . '/' . $user['profile_picture'])
    ? $user['profile_picture'] 
    : null;

$pageTitle = 'Student Dashboard | ' . APP_NAME;
$pageCss = 'student';
include 'includes/header.php';
?>
    <div class="container">
        <!-- Formal Header Section -->
        <div class="formal-header">
            <div class="formal-title-section">
                <h1 class="formal-main-title">Student Attendance Portal</h1>
                <p class="formal-subtitle">Academic Attendance Management System</p>
            </div>
        </div>

        <!-- Student Profile Card -->
        <div class="student-profile-card">
            <div class="profile-header">
                <div class="student-avatar-large">
                    <?php if ($avatarPath): ?>
                        <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Student Profile" class="student-avatar-img">
                    <?php else: ?>
                        <div class="student-avatar-placeholder-large"><?php echo htmlspecialchars($initials); ?></div>
                    <?php endif; ?>
                </div>
                <div class="profile-details">
                    <h2 class="student-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <div class="student-meta">
                        <span class="badge badge-formal">Enrolled Student</span>
                        <?php if (!empty($user['student_id'])): ?>
                            <span class="student-id">Student ID: <?php echo htmlspecialchars($user['student_id']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="profile-actions">
                    <a href="profile.php" class="btn btn-formal-primary">Update Profile</a>
                </div>
            </div>
        </div>

        <!-- Academic Statistics Dashboard -->
        <div class="academic-stats-grid">
            <div class="stat-card academic-stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo (int)$user['sessions_attended']; ?></div>
                    <div class="stat-label">Sessions Attended</div>
                    <div class="stat-description">Academic sessions completed</div>
                </div>
            </div>
            <div class="stat-card academic-stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo (int)$user['attendance_count']; ?></div>
                    <div class="stat-label">Total Check-ins</div>
                    <div class="stat-description">Attendance records logged</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="formal-actions-section">
            <h3 class="section-title">Attendance Management</h3>
            <div class="actions-grid">
                <div class="action-card">
                    <div class="action-icon">📱</div>
                    <h4>Record Attendance</h4>
                    <p>Scan QR codes to mark your presence in academic sessions.</p>
                    <a href="scan.php" class="btn btn-formal-secondary">Open Scanner</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">📊</div>
                    <h4>View Records</h4>
                    <p>Access your complete attendance history and academic records.</p>
                    <a href="my_attendance.php" class="btn btn-formal-secondary">View History</a>
                </div>
            </div>
        </div>

        <!-- Recent Academic Activity -->
        <div class="formal-section">
            <h3 class="section-title">Recent Academic Activity</h3>
            <?php if (count($attendance) > 0): ?>
                <div class="academic-table-container">
                    <table class="academic-data-table">
                        <thead>
                            <tr>
                                <th>Session Code</th>
                                <th>Course/Session Name</th>
                                <th>Check-in Date & Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance as $record): ?>
                                <tr>
                                    <td><code class="session-code"><?php echo htmlspecialchars($record['session_code']); ?></code></td>
                                    <td><?php echo htmlspecialchars($record['session_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($record['scan_time']))); ?></td>
                                    <td><span class="status-badge status-success">Present</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <a href="my_attendance.php" class="btn btn-formal-outline">View Complete Academic Record</a>
                </div>
            <?php else: ?>
                <div class="formal-notice">
                    <div class="notice-icon">📝</div>
                    <h4>No Attendance Records</h4>
                    <p>Begin your academic journey by scanning QR codes for your first session attendance.</p>
                    <a href="scan.php" class="btn btn-formal-primary">Start Recording Attendance</a>
                </div>
            <?php endif; ?>
        </div>
<?php include 'includes/footer.php'; ?>
