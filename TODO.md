# Orlando Updates TODO

Orlando branch updates for Akttend-ease: modernize codebase, clean tests, improve features/security.

## Steps

- [ ] 1. Install GitHub CLI (`winget install GitHub.cli`)
- [ ] 2. Git stash current changes: `git -C Akttend-ease stash push -m "pre-orlando"`
- [ ] 3. Create & switch branch: `git -C Akttend-ease checkout -b Orlando/attendance-updates`
- [ ] 4. Remove test/debug files (test_*.php, diag.php, phpinfo.php, etc.)
- [ ] 5. Update core: config.php (rate limit, password policy), db.php (PDO)
- [ ] 6. Improve auth/UI: login/register role selector, header/footer modular
- [ ] 7. Enhance checkin/scan: hybrid GPS+QR, error handling, JS offline
- [ ] 8. Admin/student: session delete, attendance %, reports PDF
- [ ] 9. CSS/JS modularize, add dark mode/PWA
- [ ] 10. Fix composer.json name, add LICENSE, .gitignore
- [ ] 11. Test app: `cd Akttend-ease && php -S localhost:8000`
- [ ] 12. `git add . && git commit -m "Orlando updates: full codebase modernization" && git push origin Orlando/attendance-updates`
- [ ] 13. `gh pr create --title "Orlando Updates" --body "Modernized all files per plan"`

Progress: Starting step 1.
