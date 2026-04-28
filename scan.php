<?php
/**
 * Attend Ease - QR Scanner Page
 *
 * Student-facing QR code scanner for attendance.
 *
 * @package AttendEase
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

requireLogin();
if (!isStudent() && !isAdmin() && !isTeacher()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$pageTitle = 'Scan QR Code | Attend Ease';
$basePath = '';
$pageCss = 'scanner';

include 'includes/header.php';
?>

    <div class="container">
        <h1 class="page-title">Attendance Scanner</h1>
        <p class="page-subtitle">Scan the session QR code to record your attendance</p>

        <div class="scanner-container">
            <div class="card scanner-card">
                <h2>Camera Scanner</h2>
                <p class="scanner-instructions">Point your camera at the QR code displayed by your instructor.</p>

                <div id="reader"></div>

                <div id="scan-result" class="scan-result hidden">
                    <div class="result-icon">&#10003;</div>
                    <h3>Attendance Recorded!</h3>
                    <p id="result-message"></p>
                </div>

                <div id="scan-error" class="scan-result error hidden">
                    <div class="result-icon">&#10007;</div>
                    <h3>Scan Failed</h3>
                    <p id="error-message"></p>
                </div>
            </div>

            <div class="card info-card">
                <h2>How It Works</h2>
                <ol class="steps-list">
                    <li>Allow camera access when prompted</li>
                    <li>Point your camera at the QR code</li>
                    <li>Hold steady until the code is detected</li>
                    <li>Your attendance will be recorded automatically</li>
                </ol>

                <div class="manual-entry">
                    <h3>Manual Entry</h3>
                    <p>If scanning doesn't work, enter the session code manually:</p>
                    <div class="form-group">
                        <input type="text" id="manual-code" placeholder="Enter session code">
                    </div>
                    <button id="manual-submit" class="btn btn-student btn-block">Submit Attendance</button>
                </div>

                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                    <a href="my_attendance.php" class="btn btn-secondary btn-block">View My Attendance</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="assets/js/scanner.js"></script>

<?php include 'includes/footer.php'; ?>