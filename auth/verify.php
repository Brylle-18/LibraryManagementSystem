<?php
session_start();

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    include '../config/db_connection.php';

    $token = $_COOKIE['remember_token'];
    $sql = "SELECT us.user_id, u.full_name, u.email, u.role_id FROM user_sessions us JOIN users u ON u.user_id = us.user_id WHERE us.session_token = ? AND us.revoked_at IS NULL AND us.expires_at > NOW() AND u.is_active = 1 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role_id'] = (int)$user['role_id'];
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

/**
 * Get the current user's role ID from session.
 * Returns 0 if not set (should not happen after successful authentication).
 * Role IDs: 1 = admin, 2 = student
 *
 * @return int The role_id from session
 */
function current_role_id(): int
{
    return (int)($_SESSION['role_id'] ?? 0);
}

/**
 * Redirect user to their role-appropriate dashboard.
 * Admin (role_id=1) -> admin dashboard (/pages/dashboard.php)
 * Student (role_id=2) -> student dashboard (/pages/student_dashboard.php)
 *
 * This is used when role-based access control denies access to a page.
 *
 * @param int $roleId The role ID to route based on
 */
function redirect_dashboard_for_role(int $roleId): void
{
    if ($roleId === 1) {
        header('Location: ../pages/dashboard.php');
        exit();
    }

    header('Location: ../pages/student_dashboard.php');
    exit();
}

/**
 * Enforce role-based access control on a page.
 * If current user's role is not in the allowed list, they are redirected
 * to their appropriate dashboard (based on their actual role).
 *
 * Usage examples:
 *   require_roles([1]) - only admin can access
 *   require_roles([2]) - only student can access
 *   require_roles([1, 2]) - both can access (equivalent to no restriction)
 *
 * @param array $allowedRoleIds List of role IDs allowed to access the calling page
 */
function require_roles(array $allowedRoleIds): void
{
    $currentRoleId = current_role_id();

    if (!in_array($currentRoleId, $allowedRoleIds, true)) {
        redirect_dashboard_for_role($currentRoleId);
    }
}
