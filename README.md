# Attend Ease 🏫📱

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-8892BF?style=flat&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
![Version](https://img.shields.io/badge/version-1.0.0-blue?style=flat)

**Location-Verified Attendance System with GPS Geofencing & Biometric Security**

Attend Ease is a secure, production-ready PHP application for modern attendance tracking. Combines GPS geofencing (10-50m accuracy), biometric authentication, QR code scanning, and advanced anti-cheating measures (mock GPS blocking, VPN detection, device binding). Perfect for schools, universities, and offices requiring verifiable attendance.

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🗺️ **GPS Geofencing** | Check-ins only within 10-50m of designated locations (rooms/campuses) |
| 🔐 **Biometric Auth** | FaceID/fingerprint confirmation post-GPS verification |
| 📱 **QR Scanning** | Instant attendance via session QR codes |
| 🛡️ **Anti-Spoofing** | Detects mock GPS apps, VPN/proxy, multi-device abuse |
| 📊 **Reports & Export** | View/export attendance data (CSV/PDF ready) |
| 🔍 **Device Binding** | One student ID = one hardware device UUID |
| 📝 **Audit Logs** | Full logging of all attempts with GPS coords/IP/timestamps |
| 👥 **Role-Based** | Student/Teacher/Admin portals with granular access |

## 🛠 Quick Start

### Prerequisites
- PHP 7.4+
- MySQL 5.7+ / MariaDB
- Apache/Nginx (optional, works with PHP built-in server)

### 1. Database Setup
```sql
CREATE DATABASE attend_ease CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Edit `config.php`:
```php
define('DB_NAME', 'attend_ease');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 2. Run Locally
```bash
cd Akttend-ease
php -S localhost:8000
```

### 3. Initialize
- Visit http://localhost:8000 → Auto-creates tables via `db.php`
- Run `php seed_locations.php` for demo locations
- Register teacher/admin at `/Registration/register.php`

**Full installation**: See [INSTALL.md](INSTALL.md)

## 📁 Project Structure
```
Akttend-ease/
├── index.php              # Landing page
├── admin.php              # Admin dashboard
├── student.php            # Student dashboard
├── checkin.php            # GPS + biometric check-in
├── scan.php               # QR scanner
├── reports/               # View/export
├── api/checkin.php        # API endpoint
├── config.php             # Settings & auth helpers
├── db.php                 # MySQLi + auto-schema
├── assets/                # CSS/JS/images
├── includes/              # Header/footer
└── data/                  # JSON backups
```

## 🎭 User Roles & Usage

### 👨‍🎓 Student
- Login: `/student/login.php`
- Check-in: GPS + biometric (`checkin.php`) or QR (`scan.php`)
- View: `my_attendance.php`, `profile.php`

### 👨‍🏫 Teacher/Admin
- Login: `/teacher/login.php`
- Dashboard: `admin.php` (sessions, QR generation)
- Locations: `admin_locations.php`
- Reports: `reports/view.php` / `export.php`

## 🗄️ Database Schema
Auto-created tables:

| Table | Purpose |
|-------|---------|
| `users` | Accounts (id, username, role=student/teacher/admin) |
| `sessions` | Attendance sessions (code, name, expires_at) |
| `geo_attendance` | Check-ins (gps_lat, distance_m, mock_detected, check_status) |
| `locations` | Geofences (lat, lng, radius_m) |
| `device_bindings` | Device UUID binding |
| `audit_logs` | Security events |

## 🔒 Security Highlights
- Prepared statements (MySQLi)
- CSRF protection (`generateCsrfToken()`)
- Security headers (X-Frame-Options, etc.)
- Session security (httponly, samesite)
- GPS integrity checks (mock/VPN/device)

## 🚀 Deployment

### Local Dev
```bash
php -S localhost:8000
```

### Production
1. Upload to server (e.g., `/var/www/attend-ease/`)
2. Set `APP_ENV = 'production'` in config.php
3. HTTPS required (GPS/biometrics)
4. Writable: `assets/images/profiles/`
5. Vercel: Deploy via `vercel.json` / `.github/workflows/`

**Checklist**: [DEPLOYMENT_TODO.md](DEPLOYMENT_TODO.md)

## 🔮 Roadmap
- See [TODO.md](TODO.md) (CSS modularization)
- Mobile PWA support
- Bulk import/export
- SMS notifications

## 🤝 Contributing
1. Fork → Clone → Create branch
2. Test changes locally
3. PR to `main`
4. Follow code style (PSR-12)

Issues? [Open one](https://github.com/yourusername/akttend-ease/issues)

## 📄 License
MIT License - see [LICENSE](LICENSE) (create if needed).

## Screenshots
<!-- Add: landing, checkin UI, admin dashboard -->

Built with ❤️ for reliable attendance. Questions? Check [INSTALL.md](INSTALL.md)
