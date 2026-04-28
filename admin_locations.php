<?php
/**
 * Attend Ease - Location Management
 * 
 * Admin interface for managing geofenced check-in locations.
 * 
 * @package AttendEase
 * @version 2.0.0
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

requireAdmin();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'delete') {
            $locationId = intval($_POST['location_id'] ?? 0);
            if ($locationId > 0) {
                dbExecute("DELETE FROM locations WHERE id = ?", "i", [$locationId]);
                $message = 'Location deleted.';
                auditLog('location_deleted', null, $locationId, 'location');
            }
        }
    }
}

// Get all locations
$locations = dbQuery("SELECT l.*, u.full_name as creator_name FROM locations l LEFT JOIN users u ON l.created_by = u.id ORDER BY l.name");

// Get statistics
$totalCheckins = dbValue("SELECT COUNT(*) FROM geo_attendance WHERE check_status = 'success'");
$failedCheckins = dbValue("SELECT COUNT(*) FROM geo_attendance WHERE check_status != 'success'");
$mockAttempts = dbValue("SELECT COUNT(*) FROM geo_attendance WHERE mock_detected = 1");
$vpnAttempts = dbValue("SELECT COUNT(*) FROM geo_attendance WHERE vpn_detected = 1");
$totalDevices = dbValue("SELECT COUNT(DISTINCT device_uuid) FROM device_bindings WHERE is_active = 1");

// Get recent audit logs
$auditLogs = dbQuery("SELECT a.*, u.full_name, u.email FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 50");

$pageTitle = 'Location Management | ' . APP_NAME;
$pageCss = 'teacher';
include 'includes/header.php';
?>

    <div class="container">
        <!-- Formal Header -->
        <div class="formal-header">
            <div class="formal-title-section">
                <h1>Location & Security Management</h1>
                <p class="formal-subtitle">Monitor geofenced areas, review attendance integrity, and manage security events</p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Security Insights -->
        <div class="academic-stats-grid" style="margin-bottom: 3rem;">
            <div class="academic-stat-card">
                <div class="stat-icon">&#9989;</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $totalCheckins; ?></div>
                    <div class="stat-label">Successful Check-Ins</div>
                    <p class="stat-description">Verified attendances within valid geofenced boundaries.</p>
                </div>
            </div>
            <div class="academic-stat-card">
                <div class="stat-icon">&#10060;</div>
                <div class="stat-content">
                    <div class="stat-number text-danger"><?php echo $failedCheckins; ?></div>
                    <div class="stat-label">Failed Attempts</div>
                    <p class="stat-description">Attendance checks blocked due to location or device issues.</p>
                </div>
            </div>
            <div class="academic-stat-card">
                <div class="stat-icon">&#128737;</div>
                <div class="stat-content">
                    <div class="stat-number text-warning"><?php echo $mockAttempts; ?></div>
                    <div class="stat-label">Mock GPS Blocked</div>
                    <p class="stat-description">Potential spoofing activity detected and rejected.</p>
                </div>
            </div>
            <div class="academic-stat-card">
                <div class="stat-icon">&#128374;</div>
                <div class="stat-content">
                    <div class="stat-number text-warning"><?php echo $vpnAttempts; ?></div>
                    <div class="stat-label">VPN Attempts</div>
                    <p class="stat-description">Anonymized network access flagged for review.</p>
                </div>
            </div>
            <div class="academic-stat-card">
                <div class="stat-icon">&#128241;</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $totalDevices; ?></div>
                    <div class="stat-label">Registered Devices</div>
                    <p class="stat-description">Active devices authorized for geo-attendance validation.</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="formal-actions-section">
            <h2 class="section-title">Administrative Actions</h2>
            <div class="actions-grid">
                <div class="action-card">
                    <div class="action-icon">&#128205;</div>
                    <h4>Add New Location</h4>
                    <p>Create a new geofenced campus zone for student check-in validation.</p>
                    <a href="admin.php" class="btn-formal-primary">Create Location</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">&#128200;</div>
                    <h4>View Reports</h4>
                    <p>Review attendance records and export data for analysis.</p>
                    <a href="reports/view.php" class="btn-formal-secondary">View Reports</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">&#128229;</div>
                    <h4>Export Data</h4>
                    <p>Download attendance data in various formats for record keeping.</p>
                    <a href="reports/export.php" class="btn-formal-secondary">Export Data</a>
                </div>
            </div>
        </div>

        <!-- Locations Table -->
        <div class="formal-section" style="margin-top: 3rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <h2 class="section-title" style="margin: 0;">Check-In Locations</h2>
                <a href="admin.php" class="btn-formal-primary">Create New Location</a>
            </div>
            <p class="section-description" style="margin-bottom: 1.5rem;">Review and manage active geofenced locations for the attendance system.</p>
            
            <?php if (count($locations) > 0): ?>
                <div class="academic-table-container">
                    <table class="academic-data-table">
                        <thead>
                            <tr>
                                <th>Location Name</th>
                                <th>Coordinates</th>
                                <th>Radius</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($locations as $loc): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($loc['name']); ?></strong>
                                    <?php if ($loc['description']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($loc['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code style="background: var(--accent-light); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8125rem;"><?php echo number_format($loc['latitude'], 6); ?>, <?php echo number_format($loc['longitude'], 6); ?></code>
                                </td>
                                <td><?php echo $loc['radius_meters']; ?> m</td>
                                <td><?php echo date('M j, Y', strtotime($loc['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-small btn-secondary" type="button" onclick='editLocation(<?php echo json_encode($loc); ?>)'>Edit</button>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete this location?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="location_id" value="<?php echo $loc['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="formal-notice">
                    <div class="notice-icon">&#128205;</div>
                    <h4>No Locations Configured</h4>
                    <p>You haven't created any check-in locations yet. Add your first location to enable geo-attendance.</p>
                    <a href="admin.php" class="btn-formal-primary">Create New Location</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Audit Logs -->
        <div class="formal-section" style="margin-top: 3rem;">
            <h2 class="section-title">Security Audit Logs</h2>
            <p class="section-description" style="margin-bottom: 1.5rem;">Track system events and security-related activities.</p>
            <div class="academic-table-container">
                <table class="academic-data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></td>
                            <td><span class="session-code"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                <?php 
                                $details = $log['details'] ?? '';
                                if (strlen($details) > 80) {
                                    echo htmlspecialchars(substr($details, 0, 80)) . '...';
                                } else {
                                    echo htmlspecialchars($details);
                                }
                                ?>
                            </td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($auditLogs)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 3rem;">
                                <div class="notice-icon" style="font-size: 2rem; margin-bottom: 0.5rem;">&#128203;</div>
                                <p style="color: var(--text-secondary);">No audit logs recorded yet.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

