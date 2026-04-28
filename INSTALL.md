# Attend Ease - Installation Guide

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite (or nginx with equivalent rules)
- HTTPS recommended for production (required for biometric authentication)

## Quick Start

### 1. Database Setup

```sql
CREATE DATABASE attend_ease CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'attend_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON attend_ease.* TO 'attend_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Configure Database Connection

Edit `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'attend_ease');
define('DB_USER', 'attend_user');
define('DB_PASS', 'strong_password');
```

### 3. Deploy Files

Upload all files to your web server document root (e.g., `/var/www/html/attend-ease/`).

Ensure these directories are writable by the web server:
- `assets/images/profiles/`

### 4. Initialize Database

The application auto-creates tables on first run via `db.php`. No manual migration needed.

### 5. Seed Demo Locations (Optional)

Visit `seed_locations.php` in your browser or run:

```bash
php seed_locations.php
```

### 6. First User Registration

1. Visit `Registration/register.php`
2. Create a teacher/admin account
3. Log in via `Registration/login.php`

## Production Checklist

- [ ] Change default database credentials
- [ ] Enable HTTPS
- [ ] Set `APP_ENV` to `'production'` in `config.php`
- [ ] Configure `error_log` path
- [ ] Remove `seed_locations.php` after initial setup
- [ ] Set up regular database backups
- [ ] Configure firewall rules

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Database connection failed | Verify MySQL is running and credentials are correct |
| 500 Internal Server Error | Check `error_log` path in `config.php` |
| GPS not working | Ensure site is served over HTTPS |
| QR scanner not loading | Allow camera permissions in browser |
