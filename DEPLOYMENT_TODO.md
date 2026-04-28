# Attend Ease - Deployment Checklist

## Step 1: Remove Test/Debug Development Files
- [x] Delete test_*.php files
- [x] Delete diag.php, debug_*.php
- [x] Delete examples/ directory
- [x] Delete minimal.php, phpinfo.php, setup.php

## Step 2: Production Hardening
- [x] Create .htaccess with security rules
- [x] Harden config.php (APP_ENV, conditional error reporting)
- [x] Verify db.php production readiness

## Step 3: Fix Broken HTML/JS
- [x] Fix checkin.php - remove duplicate comments, add missing dbQuery param
- [x] Fix checkin.php - complete truncated JavaScript (runSecurityChecks, detectMockLocation, checkVpn, biometric, submitCheckin)
- [x] Verify all closing tags present

## Step 4: Documentation
- [x] Create README.md
- [x] Create INSTALL.md

## Step 5: Final Verification
- [x] PHP syntax check: config.php, db.php, index.php, admin.php, student.php, checkin.php, api/checkin.php
- [x] Test local server startup (localhost:8000 → HTTP 200 OK)
- [x] Verify first-time setup flow

---

## Deployment Commands

```bash
# Start local PHP server
php -S localhost:8000

# Seed demo locations (run once)
php seed_locations.php

# Access application
# http://localhost:8000/
```

## Post-Deployment Setup
1. Ensure MySQL is running with `attend_ease` database
2. Visit `seed_locations.php` to populate demo check-in locations
3. Register first admin/teacher account via `Registration/register.php`
4. Log in and create sessions via `admin.php`
