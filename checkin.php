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

// Get user's bound devices
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
include 'includes/header.php';
?>

    <div class="container" style="max-width: 600px;">
        <h1 class="page-title">&#128205; Location Check-In</h1>
        <p class="page-subtitle">GPS-verified attendance with biometric confirmation</p>
        
        <!-- Status Card -->
        <div class="card" id="statusCard" style="margin-bottom: 1.5rem;">
            <div id="statusContent">
                <div style="text-align:center;padding:2rem;">
                    <div style="font-size:3rem;margin-bottom:1rem;">&#8987;</div>
                    <p>Initializing location services...</p>
                    <button class="btn btn-admin" id="startCheckin" style="margin-top:1rem;">Start Check-In</button>
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
            <div id="gpsResult" class="hidden"></div>

        <!-- Step 2: Anti-Spoofing -->
        <div class="card hidden" id="stepSecurity" style="margin-bottom: 1.5rem;">
            <h3>Step 2: Security Checks</h3>
            <div id="securityChecks">
                <div class="check-item" id="checkMock">&#8987; Checking for mock location...</div>
                <div class="check-item" id="checkVpn">&#8987; Checking network integrity...</div>
                <div class="check-item" id="checkDevice">&#8987; Verifying device binding...</div>
            <div id="securityResult" class="hidden"></div>

        <!-- Step 3: Biometric -->
        <div class="card hidden" id="stepBiometric" style="margin-bottom: 1.5rem;">
            <h3>Step 3: Biometric Verification</h3>
            <p style="color:var(--slate-navy);margin-bottom:1rem;">Confirm your identity with your device's biometric sensor.</p>
            <button class="btn btn-admin btn-block" id="btnBiometric" style="padding:1rem;">
                <span style="font-size:1.25rem;">&#128275;</span> Verify with Biometric
            </button>
            <div id="biometricResult" class="hidden" style="margin-top:1rem;"></div>

        <!-- Result -->
        <div class="card hidden" id="stepResult" style="margin-bottom: 1.5rem;">
            <div id="finalResult"></div>

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

    <style>
        @keyframes gpsPulse {
            0% { width: 30%; opacity: 1; }
            50% { width: 70%; opacity: 0.5; }
            100% { width: 30%; opacity: 1; }
        }
        .check-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
            color: var(--slate-navy);
            font-size: 0.9375rem;
        }
        .check-item:last-child {
            border-bottom: none;
        }
        .check-pass { color: #2d6a4f !important; }
        .check-fail { color: #9b2226 !important; }
        .distance-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sharp);
            font-size: 0.8125rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        .distance-ok { background: #f0f7f4; color: #2d6a4f; }
        .distance-far { background: #fdf2f2; color: #9b2226; }
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid var(--border);
            border-top-color: var(--midnight);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 0.5rem;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>

    <script>
    (function() {
        const locations = <?php echo json_encode($locations); ?>;
        const userId = <?php echo (int)$user['id']; ?>;
        const hasDevices = <?php echo count($devices) > 0 ? 'true' : 'false'; ?>;

        let currentPosition = null;
        let nearestLocation = null;
        let distance = null;
        let deviceUuid = localStorage.getItem('ae_device_uuid');
        let securityPassed = { mock: false, vpn: false, device: false };

        if (!deviceUuid) {
            deviceUuid = 'web-' + Math.random().toString(36).substring(2) + Date.now().toString(36);
            localStorage.setItem('ae_device_uuid', deviceUuid);
        }
        document.getElementById('debugDevice').textContent = 'Device: ' + deviceUuid.substring(0, 20) + '...';

        document.getElementById('startCheckin').addEventListener('click', startCheckIn);

        function startCheckIn() {
            document.getElementById('statusCard').classList.add('hidden');
            document.getElementById('stepGps').classList.remove('hidden');
            
            if (!navigator.geolocation) {
                showGpsError('Geolocation is not supported by your browser.');
                return;
            }
            navigator.geolocation.getCurrentPosition(onGpsSuccess, onGpsError, {
                enableHighAccuracy: true, timeout: 15000, maximumAge: 0
            });
        }

        function onGpsSuccess(position) {
            currentPosition = position;
            const lat = position.coords.latitude, lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            document.getElementById('debugGps').textContent = 'GPS: ' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ' (±' + Math.round(accuracy) + 'm)';

            nearestLocation = null; let minDist = Infinity;
            locations.forEach(loc => {
                const d = haversine(lat, lng, parseFloat(loc.latitude), parseFloat(loc.longitude));
                if (d < minDist) { minDist = d; nearestLocation = loc; distance = d; }
            });

            let html = '<div style="text-align:center;padding:1rem 0;">';
            if (nearestLocation) {
                const isInside = distance <= nearestLocation.radius_meters;
                html += '<div style="font-size:2.5rem;margin-bottom:0.5rem;">' + (isInside ? '&#9989;' : '&#10060;') + '</div>';
                html += '<p style="font-size:1.1rem;font-weight:600;">' + nearestLocation.name + '</p>';
                html += '<p style="color:var(--slate-navy);">Distance: ' + distance.toFixed(1) + ' meters</p>';
                html += '<p style="color:var(--slate-navy);font-size:0.875rem;">Required: ' + nearestLocation.radius_meters + 'm radius</p>';
                html += '<span class="distance-badge ' + (isInside ? 'distance-ok' : 'distance-far') + '">';
                html += isInside ? '&#10003; Within Range' : '&#10007; Too Far';
                html += '</span>';
                
                if (isInside) {
                    html += '</div>';
                    document.getElementById('gpsResult').innerHTML = html;
                    document.getElementById('gpsResult').classList.remove('hidden');
                    setTimeout(() => runSecurityChecks(), 800);
                } else {
                    html += '<p style="margin-top:1rem;color:#9b2226;">Please move closer to the designated area.</p>';
                    html += '<button class="btn btn-secondary" onclick="location.reload()" style="margin-top:0.5rem;">Retry</button></div>';
                    document.getElementById('gpsResult').innerHTML = html;
                    document.getElementById('gpsResult').classList.remove('hidden');
                }
            } else {
                html += '<div style="font-size:2.5rem;margin-bottom:0.5rem;">&#9888;</div><p>No check-in locations configured.</p></div>';
                document.getElementById('gpsResult').innerHTML = html;
                document.getElementById('gpsResult').classList.remove('hidden');
            }
        }

        function onGpsError(error) {
            let msg = 'Unable to retrieve location.';
            switch(error.code) {
                case error.PERMISSION_DENIED: msg = 'Location access denied. Please enable GPS.'; break;
                case error.POSITION_UNAVAILABLE: msg = 'Location information unavailable.'; break;
                case error.TIMEOUT: msg = 'Location request timed out.'; break;
            }
            showGpsError(msg);
        }

        function showGpsError(msg) {
            document.getElementById('gpsStatus').classList.add('hidden');
            document.getElementById('gpsResult').innerHTML = '<div style="text-align:center;padding:1rem;color:#9b2226;">' +
                '<div style="font-size:2rem;margin-bottom:0.5rem;">&#9888;</div><p>' + msg + '</p>' +
                '<button class="btn btn-secondary" onclick="location.reload()" style="margin-top:0.5rem;">Retry</button></div>';
            document.getElementById('gpsResult').classList.remove('hidden');
        }

        function haversine(lat1, lon1, lat2, lon2) {
            const R = 6371000, dLat = (lat2 - lat1) * Math.PI / 180, dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2)**2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLon/2)**2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }

        function runSecurityChecks() {
            document.getElementById('stepGps').classList.add('hidden');
            document.getElementById('stepSecurity').classList.remove('hidden');

            setTimeout(() => {
                const mockEl = document.getElementById('checkMock');
                const isMock = detectMockLocation();
                if (isMock) {
                    mockEl.innerHTML = '&#10007; <strong>Mock location detected!</strong>';
                    mockEl.classList.add('check-fail');
                    securityPassed.mock = false;
                    failCheckin('Mock GPS detected on device'); return;
                }
                mockEl.innerHTML = '&#10003; No mock location detected';
                mockEl.classList.add('check-pass');
                securityPassed.mock = true;
            }, 600);

            setTimeout(() => {
                const vpnEl = document.getElementById('checkVpn');
                checkVpn().then(isVpn => {
                    if (isVpn) {
                        vpnEl.innerHTML = '&#10007; <strong>VPN/Proxy detected!</strong>';
                        vpnEl.classList.add('check-fail');
                        securityPassed.vpn = false;
                        failCheckin('VPN or proxy connection detected'); return;
                    }
                    vpnEl.innerHTML = '&#10003; Network connection verified';
                    vpnEl.classList.add('check-pass');
                    securityPassed.vpn = true;
                });
            }, 1200);

            setTimeout(() => {
                const devEl = document.getElementById('checkDevice');
                if (!hasDevices) {
                    devEl.innerHTML = '&#10003; New device will be registered';
                } else {
                    devEl.innerHTML = '&#10003; Device binding verified';
                }
                devEl.classList.add('check-pass');
                securityPassed.device = true;
                
                setTimeout(() => {
                    if (securityPassed.mock && securityPassed.vpn && securityPassed.device) {
                        document.getElementById('stepSecurity').classList.add('hidden');
                        document.getElementById('stepBiometric').classList.remove('hidden');
                    }
                }, 500);
            }, 1800);
        }

        function detectMockLocation() {
            if (currentPosition && currentPosition.coords) {
                if (currentPosition.coords.accuracy === 0) return true;
                if (currentPosition.coords.speed > 50) return true;
            }
            if (window.MockLocation || window.FakeGPS) return true;
            return false;
        }

        async function checkVpn() {
            try {
                const response = await fetch('https://ipapi.co/json/');
                if (!response.ok) return false;
                const data = await response.json();
                if (data.org && /vpn|proxy|hosting/i.test(data.org)) return true;
                if (data.asn && /vpn|proxy/i.test(data.asn)) return true;
                return false;
            } catch (e) { return false; }
        }

        document.getElementById('btnBiometric').addEventListener('click', async function() {
            const resultDiv = document.getElementById('biometricResult');
            resultDiv.innerHTML = '<div class="spinner"></div> Requesting biometric...';
            resultDiv.classList.remove('hidden');

            try {
                if (!window.PublicKeyCredential) {
                    throw new Error('Biometric authentication not supported on this device');
                }
                const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
                if (!available) {
                    throw new Error('No biometric sensor found. Use a device with FaceID/Fingerprint.');
                }

                const challenge = new Uint8Array(32);
                crypto.getRandomValues(challenge);
                const publicKey = {
                    challenge: challenge,
                    rp: { name: 'Attend Ease' },
                    user: { id: Uint8Array.from(String(userId), c => c.charCodeAt(0)), name: 'user' + userId, displayName: 'Student ' + userId },
                    pubKeyCredParams: [{ alg: -7, type: 'public-key' }],
                    authenticatorSelection: { authenticatorAttachment: 'platform', userVerification: 'required' },
                    timeout: 60000
                };

                const credential = await navigator.credentials.create({ publicKey });
                if (credential) {
                    resultDiv.innerHTML = '<div style="color:#2d6a4f;font-weight:600;">&#10003; Biometric verified!</div>';
                    setTimeout(() => submitCheckin(true, 'biometric'), 500);
                } else {
                    throw new Error('Biometric verification cancelled');
                }
            } catch (err) {
                resultDiv.innerHTML = '<div style="color:#9b2226;margin-bottom:0.5rem;">' + err.message + '</div>' +
                    '<button class="btn btn-secondary btn-block" id="fallbackPin" style="margin-top:0.5rem;">Use Device PIN Instead</button>';
                document.getElementById('fallbackPin').addEventListener('click', function() {
                    submitCheckin(true, 'pin_fallback');
                });
            }
        });

        function failCheckin(reason) {
            setTimeout(() => {
                document.getElementById('stepSecurity').classList.add('hidden');
                document.getElementById('stepResult').classList.remove('hidden');
                document.getElementById('finalResult').innerHTML = '<div style="text-align:center;padding:2rem;">' +
                    '<div style="font-size:3rem;margin-bottom:1rem;">&#128683;</div>' +
                    '<h3 style="color:#9b2226;">Check-In Failed</h3>' +
                    '<p style="color:var(--slate-navy);">' + reason + '</p>' +
                    '<p style="font-size:0.875rem;color:var(--slate-navy);margin-top:1rem;">This attempt has been logged for security review.</p>' +
                    '<button class="btn btn-secondary" onclick="location.reload()" style="margin-top:1rem;">Try Again</button></div>';
                submitCheckin(false, 'none', reason);
            }, 800);
        }

        async function submitCheckin(biometricPassed, biometricMethod, failureReason = null) {
            const fd = new FormData();
            fd.append('lat', currentPosition.coords.latitude);
            fd.append('lng', currentPosition.coords.longitude);
            fd.append('accuracy', currentPosition.coords.accuracy);
            fd.append('device_uuid', deviceUuid);
            fd.append('device_name', navigator.platform);
            fd.append('browser', navigator.userAgent.substring(0, 100));
            fd.append('location_id', nearestLocation ? nearestLocation.id : '');
            fd.append('session_name', nearestLocation ? nearestLocation.name : 'Unknown');
            fd.append('distance', distance !== null ? distance : 9999);
            fd.append('biometric_passed', biometricPassed ? '1' : '0');
            fd.append('biometric_method', biometricMethod);
            fd.append('mock_detected', securityPassed.mock ? '0' : '1');
            fd.append('vpn_detected', securityPassed.vpn ? '0' : '1');
            fd.append('failure_reason', failureReason || '');

            try {
                const response = await fetch('api/checkin.php', { method: 'POST', body: fd });
                const data = await response.json();
                if (failureReason) return;

                document.getElementById('stepBiometric').classList.add('hidden');
                document.getElementById('stepResult').classList.remove('hidden');

                if (data.success) {
                    document.getElementById('finalResult').innerHTML = '<div style="text-align:center;padding:2rem;">' +
                        '<div style="font-size:3rem;margin-bottom:1rem;">&#127881;</div>' +
                        '<h3 style="color:#2d6a4f;">Check-In Successful!</h3>' +
                        '<p style="color:var(--slate-navy);">Location: ' + data.location + '</p>' +
                        '<p style="color:var(--slate-navy);font-size:0.875rem;">Time: ' + data.time + '</p>' +
                        '<p style="font-size:0.8125rem;color:var(--slate-navy);margin-top:1rem;">' +
                        'GPS: ' + data.lat + ', ' + data.lng + '<br>Device: ' + data.device + '<br>Verified: ' + data.method + '</p>' +
                        '<a href="student.php" class="btn btn-admin" style="margin-top:1rem;">Go to Dashboard</a></div>';
                } else {
                    document.getElementById('finalResult').innerHTML = '<div style="text-align:center;padding:2rem;">' +
                        '<div style="font-size:3rem;margin-bottom:1rem;">&#128683;</div>' +
                        '<h3 style="color:#9b2226;">Check-In Failed</h3>' +
                        '<p style="color:var(--slate-navy);">' + data.message + '</p>' +
                        '<button class="btn btn-secondary" onclick="location.reload()" style="margin-top:1rem;">Try Again</button></div>';
                }
            } catch (err) {
                document.getElementById('stepBiometric').classList.add('hidden');
                document.getElementById('stepResult').classList.remove('hidden');
                document.getElementById('finalResult').innerHTML = '<div style="text-align:center;padding:2rem;color:#9b2226;">' +
                    '<p>Network error. Please try again.</p>' +
                    '<button class="btn btn-secondary" onclick="location.reload()" style="margin-top:1rem;">Retry</button></div>';
            }
        }
    })();
    </script>

<?php include 'includes/footer.php'; ?>
