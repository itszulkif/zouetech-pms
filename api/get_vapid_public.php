<?php
/**
 * Returns the VAPID public key for push notification subscription.
 * Run setup_vapid_keys.php once to generate keys.
 */
require_once '../includes/pwa_config.php';
header('Content-Type: application/json');
echo json_encode(['publicKey' => PWA_VAPID_PUBLIC]);
