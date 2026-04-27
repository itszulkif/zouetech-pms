# Production Deployment Runbook

## 1) Server requirements

- PHP `8.1+` with extensions: `mysqli`, `json`, `mbstring`, `openssl`, `fileinfo`, `curl`.
- MySQL `8+` (or compatible MariaDB with equivalent features).
- Web server: Apache or Nginx.

## 2) Environment variables (required in production)

Set these in your hosting environment before serving traffic:

- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- Optional: `APP_ALLOW_RUNTIME_MIGRATIONS=1` only during controlled maintenance.

Create a root `.production` file on the live host to enable production PHP settings.

## 3) Deployment order

1. Backup database and current release.
2. Upload new release.
3. Run SQL migrations from `sql/`:
   - `sql/add_task_assignment_schema.sql`
   - `sql/add_push_subscriptions.sql`
   - `sql/add_task_chat_read.sql`
4. Ensure writable folders:
   - `logs/`
   - `uploads/avatars/`
5. Ensure setup/bootstrap scripts are blocked from web access.
6. Run smoke tests.

## 4) Web server hardening

### Apache

- Keep `.htaccess` enabled for project root and `api/`.
- Root-level protection blocks:
  - `setup_db.php`, `create_admin.php`, `setup_vapid_keys.php`
  - `debug_*.php`, `test_*.php`, `verify_settings.php`
  - `*.log`, `*.local.php`
- API-level protection blocks `ensure_*.php` and `migrate_*.php`.

### Nginx equivalent

```nginx
location ~* /(setup_db|create_admin|setup_vapid_keys)\.php$ { deny all; }
location ~* /api/(ensure_.*|migrate_.*)\.php$ { deny all; }
location ~* /(debug_.*|test_.*|verify_settings)\.php$ { deny all; }
location ~* \.(log|local\.php)$ { deny all; }
location ^~ /logs/ { deny all; }
```

## 5) Smoke tests after deploy

- Login as Super Admin works.
- User create/update API works with proper authorization.
- Create project task and direct task works.
- Task lists load for each role.
- XLSX export endpoint works.
- PWA public key endpoint and push subscribe endpoint respond correctly.

## 6) Rollback steps

1. Put app in maintenance mode (or temporarily restrict traffic).
2. Restore previous code release.
3. Restore DB backup if migration caused issues.
4. Re-run smoke tests.

## 7) Operations notes

- Rotate or truncate large files in `logs/` regularly.
- Keep `APP_ALLOW_RUNTIME_MIGRATIONS` disabled in production outside maintenance windows.
