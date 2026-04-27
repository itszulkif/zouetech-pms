<?php
/**
 * Application environment and PHP settings.
 * Load this first (e.g. from db_connect.php) to disable debug/development behaviour in production.
 */

if (!defined('APP_ENV_LOADED')) {
    define('APP_ENV_LOADED', true);

    /**
     * Load key=value pairs from an env file into process environment
     * only when the key is not already set by the runtime.
     */
    $load_env_file = static function (string $file_path): void {
        if (!is_file($file_path) || !is_readable($file_path)) {
            return;
        }

        $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            $separator_pos = strpos($line, '=');
            if ($separator_pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separator_pos));
            $value = trim(substr($line, $separator_pos + 1));
            if ($key === '' || getenv($key) !== false) {
                continue;
            }

            if (
                (strlen($value) >= 2) &&
                (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    };

    $project_root = dirname(__DIR__);
    $load_env_file($project_root . '/.env');
    $load_env_file($project_root . '/.env.local');

    $app_timezone = getenv('APP_TIMEZONE') ?: 'Asia/Karachi';
    if (!@date_default_timezone_set($app_timezone)) {
        error_log('[app_env] Invalid APP_TIMEZONE "' . $app_timezone . '". Falling back to Asia/Karachi.');
        date_default_timezone_set('Asia/Karachi');
    }

    // Production: file exists in project root (create empty .production on live server)
    $is_production = file_exists(__DIR__ . '/../.production');

    if ($is_production) {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL);
    }
    // Development: allow override via php.ini or leave defaults (display_errors often On locally)

    if (!defined('APP_IS_PRODUCTION')) {
        define('APP_IS_PRODUCTION', $is_production);
    }

    /**
     * Runtime schema mutations are disabled in production by default.
     * Set APP_ALLOW_RUNTIME_MIGRATIONS=1 only for controlled maintenance windows.
     */
    if (!defined('APP_ALLOW_RUNTIME_MIGRATIONS')) {
        $allow_runtime_migrations = getenv('APP_ALLOW_RUNTIME_MIGRATIONS') === '1';
        define('APP_ALLOW_RUNTIME_MIGRATIONS', $allow_runtime_migrations);
    }
}
