<?php
// backend/toggle_suspension.php
require_once 'db.php';
require_once 'security.php';

// Access Control: Admins/Managers only
require_role(['super_admin', 'manager']);
csrf_protect();

$target_user_id = intval($_POST['user_id'] ?? 0);

if ($target_user_id <= 0) {
    header("Location: ../frontend/admin.php?tab=users&error=" . urlencode("Invalid user."));
    exit;
}

// Prevent suspending oneself
if ($target_user_id === intval($_SESSION['user_id'])) {
    header("Location: ../frontend/admin.php?tab=users&error=" . urlencode("You cannot suspend your own account."));
    exit;
}

try {
    // Fetch user status
    $stmt = $pdo->prepare("SELECT username, is_suspended FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: ../frontend/admin.php?tab=users&error=" . urlencode("User not found."));
        exit;
    }

    $new_status = ($user['is_suspended'] == 1) ? 0 : 1;
    $status_label = ($new_status == 1) ? 'suspended' : 'activated';

    $update = $pdo->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
    $update->execute([$new_status, $target_user_id]);

    // Send notifications if user is suspended
    if ($new_status == 1) {
        // We can write to notification table
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Account Suspended', 'Your account has been suspended by an administrator. Please contact support.')");
        $notif_stmt->execute([$target_user_id]);
    }

    log_activity($pdo, $_SESSION['user_id'], "Admin toggled suspension of user " . $user['username'] . " to " . $status_label);

    header("Location: ../frontend/admin.php?tab=users&success=" . urlencode("Account of @" . $user['username'] . " has been " . $status_label . "."));
    exit;
} catch (\PDOException $e) {
    header("Location: ../frontend/admin.php?tab=users&error=" . urlencode("Database error: Could not toggle suspension."));
    exit;
}
?>
