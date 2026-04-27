# PWA (Progressive Web App) Integration

Zouetech-PMS now includes PWA capabilities: **Offline access**, **Add to Home Screen**, and **Push Notifications**.

## Features

### 1. Offline Access
- **App shell** (login page, offline page) and static assets are cached
- **Network-first** for HTML pages: tries network, falls back to cache when offline
- **Cache-first** for CDN assets (Tailwind, jQuery, Chart.js, fonts)
- **Network-only** for API calls (no offline API caching to preserve data integrity)
- When offline, navigation requests show a friendly **offline fallback page**

### 2. Add to Home Screen
- **Web App Manifest** (`manifest.json`) with app name, icons, theme color, display mode
- **Icons** generated dynamically via `pwa-icons/icon.php` (192×192 and 512×512)
- Users can install the app on mobile and desktop for a native-like experience

### 3. Push Notifications
- **Subscription** stored per user; enable via **Settings → Notifications**
- **Service Worker** handles push events and displays notifications
- **Backend** stores subscriptions; sending requires VAPID keys and a Web Push library

---

## Setup: Push Notifications

To enable push notifications, run once:

```bash
php setup_vapid_keys.php
```

This creates `pwa_config.local.php` with VAPID keys. **Add this file to `.gitignore`** to keep keys secret.

### Sending Push Notifications

To send pushes from PHP (e.g. on new chat message, task assignment):

1. Install the Web Push library:
   ```bash
   composer require minishlink/web-push
   ```

2. Use the library with `PWA_VAPID_PUBLIC` and `PWA_VAPID_PRIVATE` from `pwa_config.local.php` to send to endpoints stored in `push_subscriptions`.

3. Example integration points:
   - `api/chat/send_message.php` – notify mentioned users
   - `api/create_task.php` – notify assignees
   - `api/approve_task_assignment.php` – notify requester

---

## Files Added/Modified

| File | Purpose |
|------|---------|
| `manifest.json` | Web App Manifest |
| `sw.js` | Service Worker (caching, push, offline) |
| `offline.html` | Offline fallback page |
| `pwa-icons/icon.php` | Dynamic PWA icon generator |
| `assets/js/pwa-register.js` | Service Worker registration |
| `includes/pwa_config.php` | PWA config (VAPID keys) |
| `api/push_subscribe.php` | Save push subscription |
| `api/get_vapid_public.php` | Return public key for subscription |
| `setup_vapid_keys.php` | Generate VAPID keys |
| `sql/add_push_subscriptions.sql` | DB migration (optional; API auto-creates table) |
| `includes/header.php` | Manifest link, PWA meta tags |
| `login.php` | PWA meta tags |
| `includes/footer.php` | PWA registration script |
| `settings.php` | Notifications tab, push enable UI |

---

## Deployment Notes

- **HTTPS required** for Service Workers and Push (except `localhost`)
- **Subdirectory**: If the app runs in a subdirectory (e.g. `/Project_Management_System/`), the Service Worker infers the base path from its own URL
- **Cache version**: Bump `CACHE_VERSION` in `sw.js` and `SW_VERSION` in `pwa-register.js` when deploying updates to force cache refresh
