<?php
/**
 * Attend Ease - Location Check-In API
 * 
 * Processes GPS-verified attendance with anti-spoofing and biometric validation.
 * 
 * @package AttendEase
 * @version 2.0.0
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// Require authentication
requireAuth();

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}


$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// Get and validate input
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$accuracy = isset($_POST['accuracy']) ? floatval($_POST['accuracy']) : null;
$deviceUuid = isset($_POST['device_uuid']) ? trim($_POST['device_uuid']) : '';
$deviceName = isset($_POST['device_name']) ? trim($_POST['device_name']) : '';
$browser = isset($_POST['browser']) ? trim($_POST['browser']) : '';
$locationId = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
$sessionName = isset($_POST['session_name']) ? trim($_POST['session_name']) : 'Unknown';
$distance = isset($_POST['distance']) ? floatval($_POST['distance']) : 9999;
$biometricPassed = isset($_POST['biometric_passed']) ? intval($_POST['biometric_passed']) : 0;
$biometricMethod = isset($_POST['biometric_method']) ? trim($_POST['biometric_method']) : 'none';
$mockDetected = isset($_POST['mock_detected']) ? intval($_POST['mock_detected']) : 0;
$vpnDetected = isset($_POST['vpn_detected']) ? intval($_POST['vpn_detected']) : 0;
$failureReason = isset($_POST['failure_reason']) ? trim($_POST['failure_reason']) : '';

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

// Determine check status
$checkStatus = 'success';
if (!empty($failureReason)) {
    if (strpos($failureReason, 'Mock') !== false) {
        $checkStatus = 'failed_mock';
    } elseif (strpos($failureReason, 'VPN') !== false) {
        $checkStatus = 'failed_vpn';
    } elseif (strpos($failureReason, 'Biometric') !== false) {
        $checkStatus = 'failed_biometric';
    } elseif (strpos($failureReason, 'Device') !== false) {
        $checkStatus = 'failed_device';
    } else {
        $checkStatus = 'failed_geo';
    }
} elseif ($distance > 100) {
    $checkStatus = 'failed_geo';
    $failureReason = 'Outside geofence radius';
}

// Validate GPS coordinates
if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['success' => false, 'message' => 'Invalid GPS coordinates.']);
    exit;
}

// Get location details from database for validation
$location = null;
if ($locationId > 0) {
    $location = dbRow("SELECT * FROM locations WHERE id = ?", "i", [$locationId]);
}

// Verify distance against database radius if location exists
if ($location) {
    $dbDistance = haversine($lat, $lng, floatval($location['latitude']), floatval($location['longitude']));
    if ($dbDistance > intval($location['radius_meters'])) {
        $checkStatus = 'failed_geo';
        $failureReason = 'Outside allowed radius (' . round($dbDistance, 1) . 'm > ' . $location['radius_meters'] . 'm)';
    }
    $sessionName = $location['name'];
}

// Check for duplicate check-in today
if ($checkStatus === 'success') {
    $existing = dbValue(
        "SELECT COUNT(*) FROM geo_attendance WHERE user_id = ? AND location_id = ? AND DATE(check_time) = CURDATE() AND check_status = 'success'",
        "ii",
        [$user['id'], $locationId]
    );
    if ($existing > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already checked in at this location today.']);
        exit;
    }
}

// Insert geo attendance record
$insertId = dbInsert(
    "INSERT INTO geo_attendance (
        user_id, location_id, session_name, gps_latitude, gps_longitude, accuracy,
        distance_meters, device_uuid, ip_address, user_agent,
        biometric_passed, biometric_method, mock_detected, vpn_detected,
        check_status, failure_reason
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    "iisddddssssiississ",

    [
        $user['id'], $locationId, $sessionName, $lat, $lng, $accuracy,
        $distance, $deviceUuid, $ipAddress, $userAgent,
        $biometricPassed, $biometricMethod, $mockDetected, $vpnDetected,
        $checkStatus, $failureReason
    ]
);

if ($insertId === false) {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    exit;
}

// Bind device if new and check-in was successful
if ($checkStatus === 'success' && !empty($deviceUuid)) {
    $existingDevice = dbRow(
        "SELECT id FROM device_bindings WHERE user_id = ? AND device_uuid = ?",
        "is",
        [$user['id'], $deviceUuid]
    );
    if (!$existingDevice) {
        dbExecute(
            "INSERT INTO device_bindings (user_id, device_uuid, device_name, browser, os) VALUES (?, ?, ?, ?, ?)",
            "issss",
            [$user['id'], $deviceUuid, $deviceName, $browser, php_uname('s')]
        );
    } else {
        dbExecute(
            "UPDATE device_bindings SET last_used = NOW() WHERE id = ?",
            "i",
            [$existingDevice['id']]
        );
    }
}

// Write audit log
$auditDetails = json_encode([
    'location' => $sessionName,
    'distance' => $distance,
    'biometric' => $biometricMethod,
    'mock' => $mockDetected,
    'vpn' => $vpnDetected,
    'device' => substr($deviceUuid, 0, 20) . '...'
]);
auditLog(
    $checkStatus === 'success' ? 'checkin_success' : 'checkin_failed',
    $auditDetails,
    $insertId,
    'geo_attendance'
);

if ($checkStatus !== 'success') {
    echo json_encode([
        'success' => false,
        'message' => $failureReason,
        'status' => $checkStatus
    ]);
    exit;
}

// Success response
echo json_encode([
    'success' => true,
    'message' => 'Attendance recorded successfully.',
    'location' => $sessionName,
    'time' => date('g:i A'),
    'lat' => round($lat, 6),
    'lng' => round($lng, 6),
    'device' => $deviceName,
    'method' => $biometricMethod === 'biometric' ? 'Biometric (FaceID/Fingerprint)' : 'Device PIN',
    'distance' => round($distance, 1) . 'm'
]);

/**
 * Calculate distance between two GPS coordinates using Haversine formula
 */
function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $R = 6371000; // Earth radius in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}
