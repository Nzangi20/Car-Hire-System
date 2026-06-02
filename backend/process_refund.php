<?php
// backend/process_refund.php
require_once 'db.php';
require_once 'security.php';

// Access Control: Admins/Managers only
require_role(['super_admin', 'manager']);
csrf_protect();

$payment_id = intval($_POST['payment_id'] ?? 0);

if ($payment_id <= 0) {
    header("Location: ../frontend/admin.php?tab=payments&error=" . urlencode("Invalid payment reference."));
    exit;
}

try {
    // Fetch payment and booking details
    $pay_stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $pay_stmt->execute([$payment_id]);
    $payment = $pay_stmt->fetch();

    if (!$payment) {
        header("Location: ../frontend/admin.php?tab=payments&error=" . urlencode("Payment record not found."));
        exit;
    }

    if ($payment['payment_status'] === 'refunded') {
        header("Location: ../frontend/admin.php?tab=payments&error=" . urlencode("This payment has already been refunded."));
        exit;
    }

    $pdo->beginTransaction();

    // 1. Update Payment Record to refunded
    $upd_pay = $pdo->prepare("UPDATE payments SET payment_status = 'refunded' WHERE id = ?");
    $upd_pay->execute([$payment_id]);

    // 2. Update Booking Record to refunded/cancelled
    $upd_bk = $pdo->prepare("UPDATE bookings SET status = 'refunded' WHERE id = ?");
    $upd_bk->execute([$payment['booking_id']]);

    // 3. Send Notification to user
    $notif_title = "Payment Refund Issued";
    $notif_msg = "A refund of KSh " . number_format($payment['amount']) . " has been issued for payment transaction reference " . htmlspecialchars($payment['transaction_id']) . ".";
    
    $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $notif_stmt->execute([$payment['user_id'], $notif_title, $notif_msg]);

    // 4. Log Audit Activity
    log_activity($pdo, $_SESSION['user_id'], "Admin processed refund of KSh " . $payment['amount'] . " for payment ID " . $payment_id);

    $pdo->commit();

    header("Location: ../frontend/admin.php?tab=payments&success=" . urlencode("Refund successfully logged and customer notified."));
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../frontend/admin.php?tab=payments&error=" . urlencode("Failed to process refund: " . $e->getMessage()));
    exit;
}
?>
