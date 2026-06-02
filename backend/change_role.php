<?php
// backend/change_role.php
require_once 'db.php';
require_once 'security.php';

// Access Control: Admins/Managers only
require_role(['super_admin', 'manager']);
csrf_protect();

$target_user_id = intval($_POST['user_id'] ?? 0);
$new_role = trim($_POST['role'] ?? 'customer');

$allowed_roles = ['customer', 'staff', 'manager', 'super_admin'];

if ($target_user_id <= 0 || !in_array($new_role, $allowed_roles)) {
    header("Location: ../frontend/admin.php?tab=users&error=" . urlencode("Invalid parameters."));
    exit;
}

// Prevent changing own role
if ($target_user_id === intval($_SESSION['user_id'])) {
    header("Location: ../frontend/admin.php?tab=users&error=" . urlencode("You cannot modify your own role."));
    exit;
}

try {
    // Fetch target user role
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: ../frontend/admin.php?tab=users&error=" . urlencode("User not found."));
        exit;
    }

    // Manager role authorization rule: Managers cannot modify a super_admin or grant super_admin role
    if ($_SESSION['role'] === 'manager') {
        if ($user['role'] === 'super_admin' || $new_role === 'super_admin') {
            header("Location: ../frontend/admin.php?tab=users&error=" . urlencode("Unauthorized action. Managers cannot modify super admin privileges."));
            exit;
        }
    }

    $update = $pdo->prepare("UPDATE users SET role = ?, is_admin = ? WHERE id = ?");
    $is_admin_flag = in_array($new_role, ['super_admin', 'manager', 'staff']) ? 1 : 0;
    $update->execute([$new_role, $is_admin_flag, $target_user_id]);

    log_activity($pdo, $_SESSION['user_id'], "Admin changed role of user " . $user['username'] . " from " . $user['role'] . " to " . $new_role);

    header("Location: ../frontend/admin.php?tab=users&success=" . urlencode("Role of @" . $user['username'] . " updated to " . $new_role . "."));
    exit;
} catch (\PDOException $e) {
    header("Location: ../frontend/admin.php?tab=users&error=" . urlencode("Database error: Could not change role."));
    exit;
}
?>
