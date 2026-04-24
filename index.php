<?php
/**
 * Attend Ease - Landing Page
 * 
 * @package AttendEase
 */

require_once __DIR__ . '/config.php';

$pageTitle = APP_NAME . ' | Location-Verified Attendance';
$basePath = '';

include 'includes/header.php';
?>

    <div class="hero">
        <h1>Attend Ease</h1>
        <p>Location-Verified Attendance with GPS Geofencing & Biometric Security</p>
    </div>

    <div class="container">
        <div class="intro-text">
            <p>Welcome to the next-generation attendance system. Choose your portal below to access GPS-verified check-in, QR scanning, or administrative controls.</p>
        </div>

        <div class="card-container">
            <?php if (!isLoggedIn()): ?>
            <div class="card card-featured">
                <h3>Student Access</h3>
                <p>Log in to check in with GPS geofencing, scan QR codes, and track your attendance securely.</p>
                <a href="Registration/login.php" class="btn btn-admin btn-block">Log In as Student</a>
            </div>

            <div class="card card-featured">
                <h3>Teacher Access</h3>
                <p>Administrators can manage geofenced locations, create sessions, and monitor attendance in real time.</p>
                <a href="Registration/login.php" class="btn btn-admin btn-block">Log In as Teacher</a>
            </div>
            <?php elseif (isStudent()): ?>
            <div class="card card-featured">
                <h3>&#128205; Location Check-In</h3>
                <p>GPS-verified attendance with anti-spoofing and biometric confirmation. No more proxy attendance.</p>
                <a href="checkin.php" class="btn btn-admin btn-block">Check In Now</a>
            </div>

            <div class="card card-featured">
                <h3>Scan QR Code</h3>
                <p>Use your device's camera to scan session QR codes and record your attendance instantly.</p>
                <a href="scan.php" class="btn btn-admin btn-block">Open Scanner</a>
            </div>
            <?php elseif (isAdmin()): ?>
            <div class="card card-featured">
                <h3>Admin Dashboard</h3>
                <p>Create sessions, generate QR codes, and manage attendance records.</p>
                <a href="admin.php" class="btn btn-admin btn-block">Go to Dashboard</a>
            </div>

            <div class="card card-featured">
                <h3>&#127759; Location Management</h3>
                <p>Manage geofenced check-in locations and monitor security audit logs.</p>
                <a href="admin_locations.php" class="btn btn-admin btn-block">Manage Locations</a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!isLoggedIn()): ?>
        <div style="text-align: center; margin-top: 2.5rem;">
            <p style="color: var(--slate-navy); font-size: 0.9375rem;">
                New to Attend Ease? <a href="Registration/register.php" style="color: var(--midnight); font-weight: 600;">Create an account</a>
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Feature Showcase -->
        <div style="margin-top:3rem;text-align:left;">
            <h2 style="text-align:center;color:var(--primary-color);margin-bottom:1.5rem;">Security Features</h2>
            <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));gap:1.5rem;">
                <div class="card" style="border-top-color: #27ae60;padding:1.5rem;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">&#128205;</div>
                    <h4 style="margin:0 0 0.5rem;color:var(--primary-color);">GPS Geofencing</h4>
                    <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">Check-in only works within 10-50 meters of designated rooms.</p>
                </div>
                <div class="card" style="border-top-color: #e74c3c;padding:1.5rem;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">&#128683;</div>
                    <h4 style="margin:0 0 0.5rem;color:var(--primary-color);">Mock GPS Blocking</h4>
                    <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">Detects and rejects fake GPS apps and developer mock locations.</p>
                </div>
                <div class="card" style="border-top-color: #3498db;padding:1.5rem;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">&#128274;</div>
                    <h4 style="margin:0 0 0.5rem;color:var(--primary-color);">Biometric Lock</h4>
                    <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">FaceID or fingerprint required after GPS verification.</p>
                </div>
                <div class="card" style="border-top-color: #f39c12;padding:1.5rem;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">&#128187;</div>
                    <h4 style="margin:0 0 0.5rem;color:var(--primary-color);">Device Binding</h4>
                    <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">One student ID linked to one unique hardware device UUID.</p>
                </div>
                <div class="card" style="border-top-color: #9b59b6;padding:1.5rem;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">&#128737;</div>
                    <h4 style="margin:0 0 0.5rem;color:var(--primary-color);">VPN Detection</h4>
                    <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">Flags users attempting to mask their network location.</p>
                </div>
                <div class="card" style="border-top-color: #1abc9c;padding:1.5rem;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">&#128451;</div>
                    <h4 style="margin:0 0 0.5rem;color:var(--primary-color);">Audit Logging</h4>
                    <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">Every attempt logged with GPS coords, device ID, and timestamp.</p>
                </div>
        </div>

<?php include 'includes/footer.php'; ?>
