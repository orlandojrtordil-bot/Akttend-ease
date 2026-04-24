<?php
/**
 * Attend Ease - My Attendance History
 * 
 * Students view their own attendance records.
 * 
 * @package AttendEase
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

requireAuth('student');

$pageTitle = 'My Attendance | Attend Ease';
$basePath = '';

$studentId = $_SESSION['user']['student_id'] ?? $_SESSION['user']['username'];

// Fetch attendance records
$records = dbQuery(
    "SELECT a.*, s.session_name 
     FROM attendance a 
     LEFT JOIN sessions s ON a.session_code = s.session_code 
     WHERE a.student_id = ? 
     ORDER BY a.scan_time DESC",
    's',
    [$studentId]
);

// Summary stats
$totalScans = count($records);
$uniqueSessions = count(array_unique(array_column($records, 'session_code')));

include 'includes/header.php';
?>

    <div class="container">
        <h1 class="page-title">My Attendance</h1>
        <p class="page-subtitle">View your attendance history across all sessions</p>
        
        <div class="dashboard-grid stats-grid">
            <div class="card stat-card">
                <div class="stat-number"><?php echo $totalScans; ?></div>
                <div class="stat-label">Total Scans</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $uniqueSessions; ?></div>
                <div class="stat-label">Sessions Attended</div>
            </div>
        </div>
        
        <div class="section">
            <h2>Attendance Records</h2>
            <?php if (count($records) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Session</th>
                                <th>Session Code</th>
                                <th>Scan Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['session_name'] ?? 'N/A'); ?></td>
                                    <td><code><?php echo htmlspecialchars($record['session_code']); ?></code></td>
                                    <td><?php echo htmlspecialchars($record['scan_time']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No attendance records found. Scan a QR code to record your first attendance!</div>
            <?php endif; ?>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

