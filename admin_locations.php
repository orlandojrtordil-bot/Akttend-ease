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
        
        if ($action === 'create' || $action === 'update') {
            $name = trim($_POST['name'] ?? '');
            $lat = floatval($_POST['latitude'] ?? 0);
            $lng = floatval($_POST['longitude'] ?? 0);
            $radius = intval($_POST['radius'] ?? 20);
            $description = trim($_POST['description'] ?? '');
            $locationId = intval($_POST['location_id'] ?? 0);
            
            if (empty($name) || $lat === 0 || $lng === 0) {
                $error = 'Please provide a name and valid coordinates.';
            } elseif ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                $error = 'Invalid GPS coordinates.';
            } else {
                if ($action === 'create') {
                    $result = dbInsert(
                        "INSERT INTO locations (name, latitude, longitude, radius_meters, description, created_by) VALUES (?, ?, ?, ?, ?, ?)",
                        "sddisi",
                        [$name, $lat, $lng, $radius, $description, $user['id']]
                    );
                    if ($result) {
                        $message = 'Location "' . htmlspecialchars($name) . '" created successfully.';
                        auditLog('location_created', json_encode(['name' => $name, 'coords' => "$lat, $lng", 'radius' => $radius]), $result, 'location');
                    } else {
                        $error = 'Failed to create location.';
                    }
                } else {
                    $result = dbExecute(
                        "UPDATE locations SET name = ?, latitude = ?, longitude = ?, radius_meters = ?, description = ? WHERE id = ?",
                        "sddisi",
                        [$name, $lat, $lng, $radius, $description, $locationId]
                    );
                    if ($result) {
                        $message = 'Location updated successfully.';
                        auditLog('location_updated', json_encode(['name' => $name, 'coords' => "$lat, $lng"]), $locationId, 'location');
                    } else {
                        $error = 'Failed to update location.';
                    }
                }
            }
        } elseif ($action === 'delete') {
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
include 'includes/header.php';
?>

    <div class="container">
        <h1 class="page-title">&#127759; Location & Security Management</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Security Stats -->
        <div class="dashboard-grid stats-grid">
            <div class="card stat-card">
                <div class="stat-number"><?php echo $totalCheckins; ?></div>
                <div class="stat-label">Successful Check-Ins</div>
            <div class="card stat-card">
                <div class="stat-number" style="color: var(--danger-color);"><?php echo $failedCheckins; ?></div>
                <div class="stat-label">Failed Attempts</div>
            <div class="card stat-card">
                <div class="stat-number" style="color: var(--warning-color);"><?php echo $mockAttempts; ?></div>
                <div class="stat-label">Mock GPS Blocked</div>
            <div class="card stat-card">
                <div class="stat-number" style="color: var(--warning-color);"><?php echo $vpnAttempts; ?></div>
                <div class="stat-label">VPN Attempts</div>
            <div class="card stat-card">
                <div class="stat-number" style="color: var(--secondary-color);"><?php echo $totalDevices; ?></div>
                <div class="stat-label">Registered Devices</div>
        </div>

        <!-- Create/Edit Location -->
        <div class="card" style="margin-bottom: 2rem; text-align: left;">
            <h2 id="formTitle">Add New Location</h2>
            <form method="POST" action="" id="locationForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="create" id="formAction">
                <input type="hidden" name="location_id" value="" id="formLocationId">
                
                <div class="form-group">
                    <label for="locName">Location Name</label>
                    <input type="text" id="locName" name="name" placeholder="e.g., Main Campus - Building A" required>
                </div>
                
                <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="locLat">Latitude</label>
                        <input type="number" id="locLat" name="latitude" step="any" placeholder="14.5995" required>
                    </div>
                    <div class="form-group">
                        <label for="locLng">Longitude</label>
                        <input type="number" id="locLng" name="longitude" step="any" placeholder="120.9842" required>
                    </div>
                
                <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="locRadius">Geofence Radius (meters)</label>
                        <input type="number" id="locRadius" name="radius" value="20" min="5" max="500" required>
                        <small class="form-hint">Recommended: 10-30 meters for indoor, 50-100 for outdoor</small>
                    </div>
                    <div class="form-group">
                        <label>Quick Actions</label>
                        <button type="button" class="btn btn-secondary btn-block" onclick="getCurrentLocation()">
                            &#128205; Use My Current Location
                        </button>
                    </div>
                
                <div class="form-group">
                    <label for="locDesc">Description</label>
                    <input type="text" id="locDesc" name="description" placeholder="Room 301, 3rd Floor, CS Building">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-admin" id="submitBtn">Create Location</button>
                    <button type="button" class="btn btn-secondary hidden" id="cancelBtn" onclick="resetForm()">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Locations Table -->
        <div class="section">
            <h2>Check-In Locations</h2>
            <?php if (count($locations) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
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
                                        <br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($loc['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?php echo number_format($loc['latitude'], 6); ?>, <?php echo number_format($loc['longitude'], 6); ?></code>
                                </td>
                                <td><?php echo $loc['radius_meters']; ?>m</td>
                                <td><?php echo date('M j, Y', strtotime($loc['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-small" onclick='editLocation(<?php echo json_encode($loc); ?>)'>Edit</button>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete this location?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="location_id" value="<?php echo $loc['id']; ?>">
                                        <button type="submit" class="btn btn-small" style="background:var(--danger-color);">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No locations configured yet. Add your first check-in location above.</div>
            <?php endif; ?>
        </div>

        <!-- Audit Logs -->
        <div class="section" style="margin-top: 3rem;">
            <h2>Security Audit Logs</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td><?php echo date('M j g:i A', strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></td>
                            <td><code><?php echo htmlspecialchars($log['action']); ?></code></td>
                            <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;">
                                <?php 
                                $details = $log['details'] ?? '';
                                if (strlen($details) > 80) {
                                    echo htmlspecialchars(substr($details, 0, 80)) . '...';
                                } else {
                                    echo htmlspecialchars($details);
                                }
                                ?>
                            </td>
                            <td><small><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($auditLogs)): ?>
                        <tr><td colspan="5" style="text-align:center;">No audit logs yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    </div>

    <script>
        function getCurrentLocation() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    document.getElementById('locLat').value = pos.coords.latitude.toFixed(6);
                    document.getElementById('locLng').value = pos.coords.longitude.toFixed(6);
                },
                function(err) {
                    alert('Error getting location: ' + err.message);
                },
                { enableHighAccuracy: true }
            );
        }
        
        function editLocation(loc) {
            document.getElementById('formTitle').textContent = 'Edit Location';
            document.getElementById('formAction').value = 'update';
            document.getElementById('formLocationId').value = loc.id;
            document.getElementById('locName').value = loc.name;
            document.getElementById('locLat').value = loc.latitude;
            document.getElementById('locLng').value = loc.longitude;
            document.getElementById('locRadius').value = loc.radius_meters;
            document.getElementById('locDesc').value = loc.description || '';
            document.getElementById('submitBtn').textContent = 'Update Location';
            document.getElementById('cancelBtn').classList.remove('hidden');
            window.scrollTo({ top: 200, behavior: 'smooth' });
        }
        
        function resetForm() {
            document.getElementById('formTitle').textContent = 'Add New Location';
            document.getElementById('formAction').value = 'create';
            document.getElementById('formLocationId').value = '';
            document.getElementById('locationForm').reset();
            document.getElementById('submitBtn').textContent = 'Create Location';
            document.getElementById('cancelBtn').classList.add('hidden');
        }
    </script>

<?php include 'includes/footer.php'; ?>
