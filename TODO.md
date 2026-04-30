# Orlando Updates TODO

Orlando branch updates for Akttend-ease: modernize codebase, clean tests, improve features/security.

## Steps

- [x] 1. Install GitHub CLI (`winget install GitHub.cli`) - Skipped, used manual git
- [x] 2. Git stash current changes - Already on branch
- [x] 3. Create & switch branch: Orlando/attendance-updates
- [x] 4. Remove test/debug files (test_*.php, diag.php, phpinfo.php, etc.)
- [x] 5. Update core: config.php (rate limit, password policy), db.php PDO
- [x] 6. Improve auth/UI: login/register role selector, header/footer modular
- [x] 7. Enhance checkin/scan: hybrid GPS+QR, error handling, JS offline
- [ ] 8. Admin/student: session delete, attendance %, reports PDF
- [x] 9. CSS/JS modularize, add dark mode/PWA
- [x] 10. Fix composer.json name, add LICENSE, .gitignore
- [x] 11. Test app: `php -S localhost:8000` - Running successfully
- [x] 12. Commit and push to Orlando/attendance-updates
- [ ] 13. Create PR on GitHub - Manual review required

## Progress

**Completed (v1.0):**
- Rate limiting config (60 req/min)
- Password policy with validatePassword() function
- Role selector in registration UI
- Dark mode CSS support
- Removed test/debug files
- Added composer.json, LICENSE, .gitignore
- Committed: dc6c4a7

**Remaining:**
- Step 8: Admin features (session delete, attendance %, PDF reports)
- Step 13: Manual PR creation on GitHub

Server running at: http://localhost:8000
