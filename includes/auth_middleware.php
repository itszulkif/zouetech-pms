<?php
// includes/auth_middleware.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';


/**
 * Check if user is logged in. If not, redirect to login page.
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Enforce role-based access control.
 * @param array|string $allowed_roles Single role or array of allowed roles
 */
function require_role($allowed_roles) {
    check_login();

    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    $currentRole = $_SESSION['role'] ?? '';
    // Team Lead should inherit Team Member access by default.
    if ($currentRole === 'Team Lead' && in_array('Team Member', $allowed_roles, true)) {
        $allowed_roles[] = 'Team Lead';
    }

    // "Super Admin" always has access
    if ($currentRole === 'Super Admin') {
        return;
    }

    if (!in_array($currentRole, $allowed_roles, true)) {
        // Log unauthorized access attempt (optional but good practice)
        // error_log("Unauthorized access attempt by User ID " . $_SESSION['user_id']);
        
        // Show 403 Forbidden or redirect
        header("HTTP/1.1 403 Forbidden");
        echo "<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>";
        exit;
    }
}

/**
 * Helper to check if current user can manage a specific project.
 * @param int $project_head_id The ID of the project head
 * @return bool
 */
function can_manage_project($project_head_id) {
    if ($_SESSION['role'] === 'Super Admin') return true;
    if ($_SESSION['user_id'] == $project_head_id) return true;
    // Department Head logic could be added here if we pass dept_id
    return false;
}

/**
 * Returns avatar URL for current session user with local fallback.
 * Keeps UI independent from external avatar generators.
 */
function get_session_avatar_url() {
    $avatar = trim((string)($_SESSION['avatar_url'] ?? ''));
    return $avatar !== '' ? $avatar : 'uploads/avatars/default-avatar.svg';
}
?>
