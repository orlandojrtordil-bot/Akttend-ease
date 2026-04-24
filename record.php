<?php
/**
 * Attend Ease - Attendance Recording API
 * 
 * Receives QR scan data and records attendance.
 * 
 * @package AttendEase
 * @version 1.0.0
 */

header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

$response = [
    'success' => false,
    'message' => ''
];

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Use POST.';
    echo json_encode($response);
    exit;
}

// Get input data
$sessionCode = trim($_POST['session_code'] ?? '');
$studentId = trim($_POST['student_id'] ?? '');
$studentName = trim($_POST['student_name'] ?? '');

// Validate session code
if (empty($sessionCode)) {
    $response['message'] = 'Session code is required.';
    echo json_encode($response);
    exit;
}

// Verify session exists and is not expired
$session = dbRow(
    "SELECT * FROM sessions WHERE session_code = ? AND (expires_at IS NULL OR expires_at > NOW())",
    's',
    [$sessionCode]
);

if (!$session) {
    $response['message'] = 'Invalid or expired session code.';
    echo json_encode($response);
    exit;
}

// Generate anonymous student ID if not provided
if (empty($studentId)) {
    $studentId = 'ANON-' . uniqid();
    $studentName = 'Anonymous Student';
} else if (empty($studentName)) {
    $studentName = 'Student ' . $studentId;
}

// Check for duplicate attendance (same student, same session, within 5 minutes)
$existing = dbRow(
    "SELECT id FROM attendance WHERE session_code = ? AND student_id = ? AND scan_time > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
    'ss',
    [$sessionCode, $studentId]
);

if ($existing) {
    $response['success'] = true;
    $response['message'] = 'Attendance already recorded recently for this session.';
    echo json_encode($response);
    exit;
}

// Record attendance
$insertId = dbInsert(
    "INSERT INTO attendance (session_code, student_id, student_name) VALUES (?, ?, ?)",
    'sss',
    [$sessionCode, $studentId, $studentName]
);

if ($insertId !== false) {
    $response['success'] = true;
    $response['message'] = 'Attendance recorded successfully for "' . htmlspecialchars($session['session_name']) . '" at ' . date('g:i A');
} else {
    $response['message'] = 'Failed to record attendance. Please try again.';
}

echo json_encode($response);
exit;

