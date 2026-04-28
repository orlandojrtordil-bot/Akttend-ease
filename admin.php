<?php
/**
 * Attend Ease - Admin Dashboard
 * 
 * @package AttendEase
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

requireTeacher();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        if (isset($_POST['create_session'])) {
            $sessionName = trim($_POST['session_name'] ?? '');
            $sessionCode = trim($_POST['session_code'] ?? '');
            $startTime = trim($_POST['start_time'] ?? '');
            $endTime = trim($_POST['end_time'] ?? '');
            
            if (empty($sessionName) || empty($sessionCode)) {
                $error = 'Please provide both session name and session code.';
            } elseif (!empty($startTime) && !empty($endTime) && strtotime($startTime) >= strtotime($endTime)) {
                $error = 'End time must be after start time.';
            } else {
                $success = dbExecute(
                    "INSERT INTO sessions (session_code, session_name, start_time, end_time, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))",
                    "sssss",
                    [$sessionCode, $sessionName, $startTime ?: null, $endTime ?: null, SESSION_EXPIRY_HOURS]
                );
                
                if ($success) {
                    $message = 'Session created successfully!';
                } else {
                    $error = 'Session code already exists or database error occurred.';
                }
            }
        } elseif (isset($_POST['create_location'])) {
            $name = trim($_POST['name'] ?? '');
            $lat = floatval($_POST['latitude'] ?? 0);
            $lng = floatval($_POST['longitude'] ?? 0);
            $radius = intval($_POST['radius'] ?? 20);
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name) || $lat === 0 || $lng === 0) {
                $error = 'Please provide a name and valid coordinates.';
            } elseif ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                $error = 'Invalid GPS coordinates.';
            } else {
                $result = dbInsert(
                    "INSERT INTO locations (name, latitude, longitude, radius_meters, descripti   on, created_by) VALUES (?, ?, ?, ?, ?, ?)",
                    "sddisi",
                    [$name, $lat, $lng, $radius, $description, $_SESSION['user_id']]
                );
                if ($result) {
                    $message = 'Location "' . htmlspecialchars($name) . '" created successfully.';
                    auditLog('location_created', json_encode(['name' => $name, 'coords' => "$lat, $lng", 'radius' => $radius]), $result, 'location');
                } else {
                    $error = 'Failed to create location.';
                }
            }
        }
    }
}

$sessions = dbQuery("SELECT * FROM sessions ORDER BY created_at DESC");

$pageTitle = 'Admin Dashboard | ' . APP_NAME;
$pageCss = 'teacher';
include 'includes/header.php';
?>
    <div class="container">
        <!-- Formal Header -->
        <div class="formal-header">
            <div class="formal-title-section">
                <h1>Admin Dashboard</h1>
                <p class="formal-subtitle">Manage sessions, locations, and monitor attendance activity</p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="formal-actions-section">
            <h2 class="section-title">Quick Navigation</h2>
            <div class="actions-grid">
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
                <div class="action-card">
                    <div class="action-icon">&#127759;</div>
                    <h4>Manage Locations</h4>
                    <p>Review and manage existing geofenced check-in locations.</p>
                    <a href="admin_locations.php" class="btn-formal-secondary">Manage Locations</a>
                </div>
            </div>
        </div>

        <!-- Creation Forms -->
        <div class="dashboard-grid" style="margin-top: 3rem;">
            <div class="card" style="border-top: 3px solid var(--midnight);">
                <h2 style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">&#128197;</span>
                    Create New Session
                </h2>
                <p class="section-description" style="margin-bottom: 1.5rem;">Set up a new attendance session with a unique code for QR generation.</p>
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="form-group">
                        <label for="session_name">Session Name</label>
                        <input type="text" id="session_name" name="session_name" placeholder="e.g., Web Development - Week 5" required>
                    </div>
                    <div class="form-group">
                        <label for="session_code">Session Code</label>
                        <input type="text" id="session_code" name="session_code" placeholder="e.g., WEBDEV-2026-05" required>
                        <small class="form-hint">This code will be embedded in the QR code.</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Start Time (Optional)</label>
                            <input type="time" id="start_time" name="start_time">
                            <small class="form-hint">When the session begins</small>
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time (Optional)</label>
                            <input type="time" id="end_time" name="end_time">
                            <small class="form-hint">When the session ends</small>
                        </div>
                    </div>
                    <button type="submit" name="create_session" class="btn btn-admin" style="width: 100%;">Generate Session & QR Code</button>
                </form>
            </div>

            <div class="card" style="border-top: 3px solid var(--gold);">
                <h2 style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">&#128205;</span>
                    Add New Location
                </h2>
                <p class="section-description" style="margin-bottom: 1.5rem;">Create a campus check-in zone for accurate attendance tracking.</p>
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="form-group">
                        <label for="locName">Location Name</label>
                        <input type="text" id="locName" name="name" placeholder="e.g., Main Campus - Building A" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="locLat">Latitude</label>
                            <input type="number" id="locLat" name="latitude" step="any" placeholder="14.5995" required>
                        </div>
                        <div class="form-group">
                            <label for="locLng">Longitude</label>
                            <input type="number" id="locLng" name="longitude" step="any" placeholder="120.9842" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="locRadius">Geofence Radius (meters)</label>
                            <input type="number" id="locRadius" name="radius" value="20" min="5" max="500" required>
                            <span class="form-hint">Recommended: 10-30m indoor, 50-100m outdoor.</span>
                        </div>
                        <div class="form-group">
                            <label>Quick Actions</label>
                            <button type="button" class="btn btn-secondary btn-block" onclick="getCurrentLocation()">
                                &#128205; Use Current Location
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="locDesc">Description</label>
                        <textarea id="locDesc" name="description" rows="2" placeholder="Room 301, 3rd Floor, CS Building"></textarea>
                    </div>
                    <button type="submit" name="create_location" class="btn btn-admin" style="width: 100%;">Create Location</button>
                </form>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="formal-section" style="margin-top: 3rem;">
            <h2 class="section-title">Active Sessions</h2>
            <p class="section-description" style="margin-bottom: 1.5rem;">View and manage all created attendance sessions.</p>
            
            <?php if (count($sessions) > 0): ?>
                <div class="academic-table-container">
                    <table class="academic-data-table">
                        <thead>
                            <tr>
                                <th>Session Code</th>
                                <th>Session Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Created</th>
                                <th>Expires</th>
                                <th>QR Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td><span class="session-code"><?php echo htmlspecialchars($session['session_code']); ?></span></td>
                                    <td><?php echo htmlspecialchars($session['session_name']); ?></td>
                                    <td><?php echo $session['start_time'] ? htmlspecialchars($session['start_time']) : '<em class="text-muted">Not set</em>'; ?></td>
                                    <td><?php echo $session['end_time'] ? htmlspecialchars($session['end_time']) : '<em class="text-muted">Not set</em>'; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($session['created_at'])); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($session['expires_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-small btn-secondary" onclick="generateQR('<?php echo htmlspecialchars($session['session_code']); ?>', '<?php echo htmlspecialchars($session['session_name']); ?>')">Show QR</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="formal-notice">
                    <div class="notice-icon">&#128197;</div>
                    <h4>No Sessions Found</h4>
                    <p>Create a new session to get started with attendance tracking.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="qr-modal" class="modal hidden">
            <div class="modal-content card">
                <span class="modal-close">&times;</span>
                <h2 id="qr-title">Session QR Code</h2>
                <div id="qrcode"></div>
                <p class="qr-hint">Students can scan this QR code using the Student Portal.</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        function generateQR(code, name) {
            const modal = document.getElementById('qr-modal');
            const qrContainer = document.getElementById('qrcode');
            const title = document.getElementById('qr-title');
            
            qrContainer.innerHTML = '';
            title.textContent = name + ' - QR Code';
            
            new QRCode(qrContainer, {
                text: code,
                width: 256,
                height: 256,
                colorDark: '#102E4A',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
            
            modal.classList.remove('hidden');
        }
        
        document.querySelector('.modal-close').addEventListener('click', function() {
            document.getElementById('qr-modal').classList.add('hidden');
        });
        
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('qr-modal');
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });

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
    </script>

<?php include 'includes/footer.php'; ?>

