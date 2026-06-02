<?php
// backend/request_return.php
require_once 'db.php';
require_once 'security.php';

// Access Control
require_role('customer');
check_suspension($pdo);

$user_id = $_SESSION['user_id'];
$booking_id = intval($_GET['booking_id'] ?? 0);

if ($booking_id <= 0) {
    header("Location: ../frontend/dashboard.php");
    exit;
}

try {
    // Fetch booking
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        header("Location: ../frontend/dashboard.php?error=" . urlencode("Booking not found."));
        exit;
    }

    if ($booking['returned'] == 1) {
        header("Location: ../frontend/dashboard.php?error=" . urlencode("This vehicle has already been returned and inspected."));
        exit;
    }

    // Update status to 'completed' indicating the car is handed back and pending admin inspection
    // We keep returned = 0 until the admin runs the inspection.
    $update = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
    $update->execute([$booking_id]);

    // Send admin alert simulation / notification
    log_activity($pdo, $user_id, "User requested return inspection for booking #PW-$booking_id");

    header("Location: ../frontend/dashboard.php?success=" . urlencode("Return request submitted. Please wait for an administrator to complete the vehicle inspection."));
    exit;
} catch (\PDOException $e) {
    header("Location: ../frontend/dashboard.php?error=" . urlencode("Database error: Could not process return."));
    exit;
}
?>
