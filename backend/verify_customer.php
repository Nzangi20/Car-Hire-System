<?php
// backend/verify_customer.php
require_once 'db.php';
require_once 'security.php';

// Access Control: Admins/Managers/Staff only
require_role(['super_admin', 'manager', 'staff']);
csrf_protect();

$user_id = intval($_POST['user_id'] ?? 0);
$action = trim($_POST['action'] ?? '');

if ($user_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    header("Location: ../frontend/admin.php?tab=verification&error=" . urlencode("Invalid parameters."));
    exit;
}

try {
    $status = ($action === 'approve') ? 'verified' : 'rejected';
    
    // Update user
    $stmt = $pdo->prepare("UPDATE users SET verification_status = ? WHERE id = ?");
    $stmt->execute([$status, $user_id]);

    // Send notification
    $title = "Verification Status Update";
    if ($status === 'verified') {
        $msg = "Congratulations! Your profile documents have been verified. You can now rent any available vehicle from our fleet.";
    } else {
        $msg = "Your document verification request was declined. Please verify your driving license and national ID information and upload clear scanned copies.";
    }
    
    $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $notif_stmt->execute([$user_id, $title, $msg]);

    // Log audit activity
    log_activity($pdo, $_SESSION['user_id'], "Admin set verification status of user ID $user_id to '$status'");

    header("Location: ../frontend/admin.php?tab=verification&success=" . urlencode("Customer verification status updated to '$status'."));
    exit;
} catch (\PDOException $e) {
    header("Location: ../frontend/admin.php?tab=verification&error=" . urlencode("Database error: Could not complete verification action."));
    exit;
}
?>
