<?php
/**
 * Attend Ease - Attendance Reports
 * 
 * @package AttendEase
 * @subpackage Reports
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

requireAdmin();

$pageTitle = 'Attendance Reports | ' . APP_NAME;
$basePath = '../';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Filters
$filterSession = trim($_GET['session'] ?? '');
$filterDate = trim($_GET['date'] ?? '');

// Build WHERE clause
$where = [];
$types = '';
$params = [];

if (!empty($filterSession)) {
    $where[] = 'a.session_code = ?';
    $types .= 's';
    $params[] = $filterSession;
}

if (!empty($filterDate)) {
    $where[] = 'DATE(a.scan_time) = ?';
    $types .= 's';
    $params[] = $filterDate;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) FROM attendance a $whereClause";
$totalRecords = (int) dbValue($countSql, $types, $params);
$totalPages = max(1, ceil($totalRecords / $perPage));

// Get records
$recordsSql = "SELECT a.*, s.session_name 
               FROM attendance a 
               LEFT JOIN sessions s ON a.session_code = s.session_code 
               $whereClause 
               ORDER BY a.scan_time DESC 
               LIMIT ? OFFSET ?";
$records = dbQuery($recordsSql, $types . 'ii', array_merge($params, [$perPage, $offset]));

// Get all sessions for filter dropdown
$sessions = dbQuery("SELECT session_code, session_name FROM sessions ORDER BY session_name");

// Summary statistics
$stats = dbRow("SELECT 
    COUNT(DISTINCT session_code) as total_sessions,
    COUNT(*) as total_attendance,
    COUNT(DISTINCT student_id) as unique_students
    FROM attendance") ?? ['total_sessions' => 0, 'total_attendance' => 0, 'unique_students' => 0];

include '../includes/header.php';
?>

    <div class="container">
        <h1 class="page-title">Attendance Reports</h1>
        
        <div class="dashboard-grid stats-grid">
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_sessions']; ?></div>
                <div class="stat-label">Total Sessions</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['total_attendance']; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="card stat-card">
                <div class="stat-number"><?php echo $stats['unique_students']; ?></div>
                <div class="stat-label">Unique Students</div>
            </div>
        </div>

        <div class="card filter-card">
            <h2>Filter Records</h2>
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label for="session">Session</label>
                    <select id="session" name="session">
                        <option value="">All Sessions</option>
                        <?php foreach ($sessions as $session): ?>
                            <option value="<?php echo sanitizeInput($session['session_code']); ?>" 
                                <?php echo $filterSession === $session['session_code'] ? 'selected' : ''; ?>>
                                <?php echo sanitizeInput($session['session_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" value="<?php echo sanitizeInput($filterDate); ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-admin">Apply Filters</button>
                    <a href="view.php" class="btn btn-secondary">Clear</a>
                    <a href="export.php?<?php echo http_build_query($_GET); ?>" class="btn btn-student">Export CSV</a>
                </div>
            </form>
        </div>

        <div class="section">
            <h2>Attendance Records</h2>
            <?php if (count($records) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Session</th>
                                <th>Session Name</th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Scan Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo sanitizeInput($record['id']); ?></td>
                                    <td><code><?php echo sanitizeInput($record['session_code']); ?></code></td>
                                    <td><?php echo sanitizeInput($record['session_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo sanitizeInput($record['student_id']); ?></td>
                                    <td><?php echo sanitizeInput($record['student_name']); ?></td>
                                    <td><?php echo sanitizeInput($record['scan_time']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => 1])); ?>" class="btn btn-secondary btn-small">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => 1])); ?>" class="btn btn-secondary btn-small">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">No attendance records found matching your criteria.</div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="../admin.php" class="btn btn-secondary btn-small">&larr; Back to Admin Dashboard</a>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>

