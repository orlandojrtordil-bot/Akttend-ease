<?php
/**
 * Attend Ease - Database Connection (MySQLi)
 * 
 * Uses MySQLi with prepared statements for security.
 * Provides a PDO-like API via helper functions.
 * 
 * @package AttendEase
 * @version 1.0.0
 */

if (!defined('ATTEND_EASE')) {
    define('ATTEND_EASE', true);
}

require_once __DIR__ . '/config.php';

// Database credentials
$dbHost = 'localhost';
$dbName = 'attend_ease';
$dbUser = 'root';
$dbPass = '';
$dbCharset = 'utf8mb4';

// Create MySQLi connection
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Check connection
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    die("<div style='text-align:center;padding:2rem;font-family:sans-serif;'>
        <h2>Database Connection Failed</h2>
        <p>Please ensure MySQL is running and the <code>attend_ease</code> database exists.</p>
        <p><a href='test_db.php' style='color:#102E4A;font-weight:600;'>Run Diagnostics</a></p>
    </div>");
}

// Set charset
$mysqli->set_charset($dbCharset);

/**
 * Execute a prepared SELECT query and return all rows
 * 
 * @param string $sql SQL with ? placeholders
 * @param string $types Type string (e.g., 'ssi' for string,string,int)
 * @param array $params Parameter values
 * @return array Result rows
 */
function dbQuery(string $sql, string $types = '', array $params = []): array
{
    global $mysqli;
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $mysqli->error);
        return [];
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result === false) {
        $stmt->close();
        return [];
    }
    
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $rows;
}

/**
 * Execute a prepared query that doesn't return rows (INSERT/UPDATE/DELETE)
 * 
 * @param string $sql SQL with ? placeholders
 * @param string $types Type string
 * @param array $params Parameter values
 * @return bool True on success
 */
function dbExecute(string $sql, string $types = '', array $params = []): bool
{
    global $mysqli;
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $mysqli->error);
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $success = $stmt->execute();
    if (!$success) {
        error_log("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    return $success;
}

/**
 * Execute and get the last inserted ID
 * 
 * @param string $sql SQL with ? placeholders
 * @param string $types Type string
 * @param array $params Parameter values
 * @return int|false Last insert ID or false
 */
function dbInsert(string $sql, string $types = '', array $params = [])
{
    global $mysqli;
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $mysqli->error);
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $success = $stmt->execute();
    $insertId = $success ? $mysqli->insert_id : false;
    $stmt->close();
    
    return $insertId;
}

/**
 * Get a single row
 * 
 * @param string $sql SQL with ? placeholders
 * @param string $types Type string
 * @param array $params Parameter values
 * @return array|null Single row or null
 */
function dbRow(string $sql, string $types = '', array $params = []): ?array
{
    $rows = dbQuery($sql, $types, $params);
    return $rows[0] ?? null;
}

/**
 * Get a single scalar value
 * 
 * @param string $sql SQL with ? placeholders
 * @param string $types Type string
 * @param array $params Parameter values
 * @return mixed Value or null
 */
function dbValue(string $sql, string $types = '', array $params = [])
{
    $row = dbRow($sql, $types, $params);
    if ($row === null) {
        return null;
    }
    return array_values($row)[0];
}

/**
 * Escape string for safe use in SQL (fallback when prepared statements aren't feasible)
 * 
 * @param string $str Raw string
 * @return string Escaped string
 */
function dbEscape(string $str): string
{
    global $mysqli;
    return $mysqli->real_escape_string($str);
}

// Create tables if they don't exist
$tableSql = [
    "CREATE TABLE IF NOT EXISTS sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_code VARCHAR(50) NOT NULL UNIQUE,
        session_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_code VARCHAR(50) NOT NULL,
        student_id VARCHAR(50) NOT NULL,
        student_name VARCHAR(255),
        scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_code),
        INDEX idx_student (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        student_id VARCHAR(50),
        subject VARCHAR(100),
        role ENUM('student','teacher','admin') DEFAULT 'student',
        profile_picture VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_role (role),
        INDEX idx_student_id (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // LOCATION VERIFICATION TABLES
    "CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        radius_meters INT DEFAULT 20,
        description TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_coords (latitude, longitude)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS device_bindings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        device_uuid VARCHAR(255) NOT NULL,
        device_name VARCHAR(100),
        browser VARCHAR(100),
        os VARCHAR(100),
        bound_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active TINYINT(1) DEFAULT 1,
        UNIQUE KEY unique_device_user (user_id, device_uuid),
        INDEX idx_uuid (device_uuid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS geo_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        location_id INT,
        session_name VARCHAR(255) NOT NULL,
        gps_latitude DECIMAL(10, 8),
        gps_longitude DECIMAL(11, 8),
        accuracy DECIMAL(8, 2),
        distance_meters DECIMAL(10, 2),
        device_uuid VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        biometric_passed TINYINT(1) DEFAULT 0,
        biometric_method VARCHAR(20),
        mock_detected TINYINT(1) DEFAULT 0,
        vpn_detected TINYINT(1) DEFAULT 0,
        check_status ENUM('success','failed_geo','failed_mock','failed_vpn','failed_biometric','failed_device') DEFAULT 'success',
        failure_reason VARCHAR(255),
        check_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_time (check_time),
        INDEX idx_status (check_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50),
        entity_id INT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_action (action),
        INDEX idx_time (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($tableSql as $sql) {
    $mysqli->query($sql);
}
