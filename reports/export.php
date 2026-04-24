<?php
/**
 * Attend Ease - CSV Export
 * 
 * Exports attendance records as a CSV file with UTF-8 BOM for Excel compatibility.
 * 
 * @package AttendEase
 * @subpackage Reports
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

requireAdmin();

// Filters
$filterSession = trim($_GET['session'] ?? '');
$filterDate    = trim($_GET['date'] ?? '');

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

// Get records
$records = [];
$records = dbQuery(
    "SELECT a.id, a.session_code, s.session_name, a.student_id, a.student_name, a.scan_time 
     FROM attendance a 
     LEFT JOIN sessions s ON a.session_code = s.session_code 
     $whereClause 
     ORDER BY a.scan_time DESC",
    $types,
    $params
);

// Generate filename
$filename = 'attendance_report_' . date('Y-m-d_H-i-s') . '.csv';

// Set download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for Excel compatibility
fprintf($output, "\xEF\xBB\xBF");

// Write column headers
fputcsv($output, ['ID', 'Session Code', 'Session Name', 'Student ID', 'Student Name', 'Scan Time']);

// Write data rows
foreach ($records as $record) {
    fputcsv($output, [
        $record['id'],
        $record['session_code'],
        $record['session_name'] ?? 'N/A',
        $record['student_id'],
        $record['student_name'],
        $record['scan_time']
    ]);
}

fclose($output);
exit;
