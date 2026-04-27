<?php
/**
 * PWA Configuration
 * Set PWA_VAPID_PUBLIC and PWA_VAPID_PRIVATE by running: php setup_vapid_keys.php
 */
if (!defined('PWA_VAPID_PUBLIC')) {
    $pwa_config_file = __DIR__ . '/../pwa_config.local.php';
    if (file_exists($pwa_config_file)) {
        require $pwa_config_file;
    }
    if (!defined('PWA_VAPID_PUBLIC')) {
        define('PWA_VAPID_PUBLIC', '');
    }
    if (!defined('PWA_VAPID_PRIVATE')) {
        define('PWA_VAPID_PRIVATE', '');
    }
}
