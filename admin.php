<?php
/**
 * Attend Ease - Admin Dashboard
 * 
 * @package AttendEase
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

requireAuth('teacher');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_session'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $sessionName = trim($_POST['session_name'] ?? '');
        $sessionCode = trim($_POST['session_code'] ?? '');
        
        if (empty($sessionName) || empty($sessionCode)) {
            $error = 'Please provide both session name and session code.';
        } else {
            $success = dbExecute(
                "INSERT INTO sessions (session_code, session_name, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))",
                "ssi",
                [$sessionCode, $sessionName, SESSION_EXPIRY_HOURS]
            );
            
            if ($success) {
                $message = 'Session created successfully!';
            } else {
                $error = 'Session code already exists or database error occurred.';
            }
        }
    }
}

$sessions = dbQuery("SELECT * FROM sessions ORDER BY created_at DESC");

$pageTitle = 'Admin Dashboard | ' . APP_NAME;
include 'includes/header.php';
?>
    <div class="container">
        <h1 class="page-title">Admin Dashboard</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="card">
                <h2>Create New Session</h2>
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
                    <button type="submit" name="create_session" class="btn btn-admin">Generate Session & QR Code</button>
                </form>
            </div>

            <div class="card">
                <h2>Quick Links</h2>
                <ul class="link-list">
                    <li><a href="reports/view.php" class="btn btn-secondary">View Attendance Records</a></li>
                    <li><a href="reports/export.php" class="btn btn-secondary">Export Data</a></li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>Active Sessions</h2>
            <?php if (count($sessions) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Session Code</th>
                            <th>Session Name</th>
                            <th>Created</th>
                            <th>Expires</th>
                            <th>QR Code</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($session['session_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($session['session_name']); ?></td>
                                <td><?php echo htmlspecialchars($session['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($session['expires_at']); ?></td>
                                <td>
                                    <button class="btn btn-small" onclick="generateQR('<?php echo htmlspecialchars($session['session_code']); ?>', '<?php echo htmlspecialchars($session['session_name']); ?>')">Show QR</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No sessions found. Create a new session to get started.</div>
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
    </script>

<?php include 'includes/footer.php'; ?>

