<?php
/**
 * Attend Ease - Location-Verified Check-In
 *
 * GPS Geofencing + Biometric Attendance System
 *
 * @package AttendEase
 * @version 2.0.0
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

requireAuth();

$user = getCurrentUser();
if (!$user || $user['role'] !== 'student') {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}


$devices = dbQuery(
    "SELECT device_uuid, device_name, is_active FROM device_bindings WHERE user_id = ? AND is_active = 1",
    "i",
    [$user['id']]
);


// Get active locations
$locations = dbQuery("SELECT * FROM locations ORDER BY name");

// Check if user already checked in today
$todayCheckins = dbQuery(
    "SELECT * FROM geo_attendance WHERE user_id = ? AND DATE(check_time) = CURDATE() AND check_status = 'success'",
    "i",
    [$user['id']]
);

$pageTitle = 'Check-In | ' . APP_NAME;
$pageCss = 'checkin';
include 'includes/header.php';
?>

    <div class="container" style="max-width: 600px;">
        <h1 class="page-title">&#128205; Location Check-In</h1>
        <p class="page-subtitle">GPS-verified attendance with biometric confirmation</p>

        <!-- Room Status Display -->
        <div class="card" id="roomStatusCard" style="margin-bottom: 1.5rem; text-align: center; padding: 2rem;">
            <div id="roomStatusContent">
                <div class="status-emoji" id="statusEmoji">⏳</div>
                <h2 id="statusTitle" style="margin: 1rem 0 0.5rem 0; color: var(--slate-navy);">Initializing Location...</h2>
                <p id="statusMessage" style="color: var(--slate-navy); margin: 0;">Please wait while we detect your location</p>
                <div class="attendance-timer" id="attendanceTimerSection" style="display: none; margin-top: 1.5rem;">
                    <div class="timer-container">
                        <div class="timer-icon">⏰</div>
                        <div class="timer-content">
                            <div class="timer-label">Attendance Valid For:</div>
                            <div id="attendanceCountdown" class="attendance-countdown">15:00</div>
                        </div>
                        <button id="cancelAttendanceBtn" class="btn-cancel-attendance">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-Time Location Status -->
        <div class="card" id="locationStatus" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
            <div class="location-header">
                <h3 style="margin:0; color: var(--midnight);">📍 Real-Time Location</h3>
                <div id="locationIndicator" class="location-indicator">
                    <div class="indicator-light" id="indicatorLight"></div>
                    <span id="locationStatusText">Initializing...</span>
                </div>
            </div>
            <div class="location-details">
                <div class="location-coords">
                    <span id="currentCoords">Lat: --, Lng: --</span>
                    <span id="accuracyDisplay">Accuracy: --m</span>
                </div>
                <div class="location-target">
                    <span id="targetLocation">Target: Detecting...</span>
                    <span id="distanceDisplay">Distance: --m</span>
                </div>
            </div>
        </div>

        <!-- Location Map -->
        <div class="card" id="locationMap" style="margin-bottom: 1.5rem; display: none;">
            <h3>📍 Location Map</h3>
            <div id="mapContainer" style="height: 300px; border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border);">
                <div id="mapLoading" style="display: flex; align-items: center; justify-content: center; height: 100%; background: var(--pearl); color: var(--slate-navy);">
                    <div class="spinner" style="margin-right: 1rem;"></div>
                    Loading map...
                </div>
            </div>
            <div class="map-legend" style="margin-top: 1rem; padding: 0.75rem; background: var(--pearl); border-radius: var(--radius);">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 12px; height: 12px; background: #007bff; border-radius: 50%;"></div>
                        <span style="font-size: 0.875rem;">Your Location</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 12px; height: 12px; background: #28a745; border-radius: 50%;"></div>
                        <span style="font-size: 0.875rem;">Check-in Location</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 12px; height: 12px; background: #dc3545; border-radius: 2px; opacity: 0.3;"></div>
                        <span style="font-size: 0.875rem;">Out of Range</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Initial Status Card -->
        <div class="card" id="statusCard" style="margin-bottom: 1.5rem;">
            <div id="statusContent">
                <div style="text-align:center;padding:2rem;">
                    <div style="font-size:3rem;margin-bottom:1rem;">&#8987;</div>
                    <p>Initializing location services...</p>
                    <button class="btn btn-admin" id="startCheckin" style="margin-top:1rem;">Start Check-In</button>
                </div>
            </div>
        </div>

        <!-- Step 1: GPS Verification -->
        <div class="card hidden" id="stepGps" style="margin-bottom: 1.5rem;">
            <h3>Step 1: GPS Verification</h3>
            <div id="gpsStatus">
                <p>Acquiring your location...</p>
                <div style="width:100%;height:4px;background:var(--border);margin:1rem 0;overflow:hidden;">
                    <div style="height:100%;width:30%;background:var(--midnight);animation:gpsPulse 1.5s infinite;"></div>
                </div>
            </div>
            <div id="gpsResult" class="hidden"></div>
        </div>

        <!-- Step 2: Anti-Spoofing -->
        <div class="card hidden" id="stepSecurity" style="margin-bottom: 1.5rem;">
            <h3>Step 2: Security Checks</h3>
            <div id="securityChecks">
                <div class="check-item" id="checkMock">&#8987; Checking for mock location...</div>
                <div class="check-item" id="checkVpn">&#8987; Checking network integrity...</div>
                <div class="check-item" id="checkDevice">&#8987; Verifying device binding...</div>
            </div>
            <div id="securityResult" class="hidden"></div>
        </div>

        <!-- Step 3: Biometric -->
        <div class="card hidden" id="stepBiometric" style="margin-bottom: 1.5rem;">
            <h3>Step 3: Biometric Verification</h3>
            <p style="color:var(--slate-navy);margin-bottom:1rem;">Confirm your identity with your device's biometric sensor.</p>
            <button class="btn btn-admin btn-block" id="btnBiometric" style="padding:1rem;">
                <span style="font-size:1.25rem;">&#128275;</span> Verify with Biometric
            </button>
            <div id="biometricResult" class="hidden" style="margin-top:1rem;"></div>
        </div>

        <!-- Result -->
        <div class="card hidden" id="stepResult" style="margin-bottom: 1.5rem;">
            <div id="finalResult"></div>
        </div>

        <!-- Recent Check-Ins -->
        <?php if (!empty($todayCheckins)): ?>
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3>Today's Check-Ins</h3>
            <div class="table-responsive">
                <table class="data-table" style="margin-top:0.5rem;">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todayCheckins as $c): ?>
                        <tr>
                            <td><?php echo date('g:i A', strtotime($c['check_time'])); ?></td>
                            <td><?php echo htmlspecialchars($c['session_name']); ?></td>
                            <td>
                                <?php if ($c['check_status'] === 'success'): ?>
                                    <span style="color:#2d6a4f;font-weight:600;">&#10003; Verified</span>
                                <?php else: ?>
                                    <span style="color:#9b2226;">&#10007; <?php echo htmlspecialchars($c['failure_reason']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Debug Info -->
        <details style="margin-top:1rem;">
            <summary style="cursor:pointer;color:var(--slate-navy);font-size:0.875rem;">Technical Details</summary>
            <div style="padding:1rem;background:var(--pearl);font-size:0.8125rem;font-family:monospace;margin-top:0.5rem;">
                <p>User ID: <?php echo $user['id']; ?></p>
                <p>Bound Devices: <?php echo count($devices); ?></p>
                <p>Active Locations: <?php echo count($locations); ?></p>
                <p id="debugGps">GPS: Not acquired</p>
                <p id="debugDevice">Device: Detecting...</p>
            </div>
        </details>
    </div>

    <script>
    window.attendanceConfig = {
        locations: <?php echo json_encode($locations); ?>,
        userId: <?php echo (int)$user['id']; ?>,
        hasDevices: <?php echo count($devices) > 0 ? 'true' : 'false'; ?>,
        csrfToken: "<?php echo generateCsrfToken(); ?>"
    };
    </script>
    <script src="assets/js/checkin.js"></script>

<?php include "includes/footer.php"; ?>
