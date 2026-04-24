<?php
/**
 * Attend Ease - Student Dashboard
 * 
 * @package AttendEase
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

requireAuth('student');

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
include 'includes/header.php';
?>
    <div class="container">
        <div class="student-header">
            <div class="student-profile">
                <div class="student-avatar">
                    <?php if ($avatarPath): ?>
                        <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Profile" class="student-avatar-img">
                    <?php else: ?>
                        <div class="student-avatar-placeholder"><?php echo htmlspecialchars($initials); ?></div>
                    <?php endif; ?>
                </div>
                <div class="student-info">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p>
                        <span class="badge badge-student">Student</span>
                        <?php if (!empty($user['student_id'])): ?>
                            &nbsp;ID: <?php echo htmlspecialchars($user['student_id']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="student-actions">
                <a href="profile.php" class="btn btn-secondary">Edit Profile</a>
            </div>
        </div>

        <div class="dashboard-grid stats-grid">
            <div class="card stat-card">
                <div class="stat-number"><?php echo (int)$user['sessions_attended']; ?></div>
                <div class="stat-label">Sessions Attended</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo (int)$user['attendance_count']; ?></div>
                <div class="stat-label">Total Scans</div>
            </div>
        </div>

        <div class="card-container" style="margin-bottom: 2.5rem;">
            <div class="card card-featured">
                <h3>Scan QR Code</h3>
                <p>Use your camera to scan a session QR code and record your attendance instantly.</p>
                <a href="scan.php" class="btn btn-admin btn-block">Open Scanner</a>
            </div>
            <div class="card card-featured">
                <h3>My Attendance</h3>
                <p>View your complete attendance history across all sessions and dates.</p>
                <a href="my_attendance.php" class="btn btn-secondary btn-block">View History</a>
            </div>
        </div>

        <div class="section">
            <h2>Recent Attendance</h2>
            <?php if (count($attendance) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Session</th>
                                <th>Session Name</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance as $record): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($record['session_code']); ?></code></td>
                                    <td><?php echo htmlspecialchars($record['session_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['scan_time']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="my_attendance.php" class="btn btn-small btn-secondary">View All</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No attendance records yet. Use the scanner to record your first attendance.</div>
            <?php endif; ?>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>

