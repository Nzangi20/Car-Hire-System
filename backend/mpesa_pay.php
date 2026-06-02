<?php
// backend/mpesa_pay.php
require_once 'db.php';
require_once 'security.php';

// Access Control
require_role('customer');
check_suspension($pdo);
csrf_protect();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $payment_type = trim($_POST['payment_type'] ?? 'full');

    if (!isset($_SESSION['pending_booking']) || empty($phone)) {
        header("Location: ../frontend/dashboard.php?error=" . urlencode("Invalid payment request or session expired."));
        exit;
    }

    if (!in_array($payment_type, ['full', 'deposit'])) {
        $payment_type = 'full';
    }

    // Phone validation (Kenyan format e.g. 07XXXXXXXX or 01XXXXXXXX)
    if (!preg_match('/^(07|01)[0-9]{8}$/', $phone)) {
        header("Location: ../frontend/checkout.php?error=" . urlencode("Please enter a valid Kenyan phone number (e.g. 0712345678)."));
        exit;
    }

    $pending = $_SESSION['pending_booking'];

    try {
        // Fetch car details
        $car_stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
        $car_stmt->execute([$pending['car_id']]);
        $car = $car_stmt->fetch();

        if (!$car) {
            unset($_SESSION['pending_booking']);
            header("Location: ../frontend/index.php?error=" . urlencode("Car not found."));
            exit;
        }

        if ($car['status'] !== 'available') {
            unset($_SESSION['pending_booking']);
            header("Location: ../frontend/index.php?error=" . urlencode("Selected car has just become unavailable."));
            exit;
        }

        // Final double-booking overlap verification
        $overlap_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM bookings 
            WHERE car_id = ? 
              AND status NOT IN ('rejected', 'cancelled', 'refunded')
              AND (
                (pickup_datetime <= ? AND return_datetime >= ?) OR
                (pickup_datetime <= ? AND return_datetime >= ?) OR
                (pickup_datetime >= ? AND return_datetime <= ?)
              )
        ");
        $overlap_stmt->execute([
            $pending['car_id'], 
            $pending['pickup_datetime'], $pending['pickup_datetime'], 
            $pending['return_datetime'], $pending['return_datetime'], 
            $pending['pickup_datetime'], $pending['return_datetime']
        ]);
        $booked_count = $overlap_stmt->fetchColumn();

        if ($booked_count >= $car['quantity']) {
            unset($_SESSION['pending_booking']);
            header("Location: ../frontend/index.php?error=" . urlencode("Sorry, this car was booked by another customer in the last few minutes during this date range."));
            exit;
        }

        // Calculate amount to pay
        $total_due = floatval($pending['total_amount']);
        $amount_paid = ($payment_type === 'deposit') ? ($total_due * 0.20) : $total_due;

        // Start transaction
        $pdo->beginTransaction();

        // 1. Insert Booking Record
        $insert_bk = $pdo->prepare("
            INSERT INTO bookings (car_id, user_id, pickup_datetime, return_datetime, pickup_location, return_location, hire_days, phone, total_amount, status, returned, special_requests) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 0, ?)
        ");
        $insert_bk->execute([
            $pending['car_id'],
            $user_id,
            $pending['pickup_datetime'],
            $pending['return_datetime'],
            $pending['pickup_location'],
            $pending['return_location'],
            $pending['hire_days'],
            $phone,
            $total_due,
            $pending['special_requests']
        ]);
        $booking_id = $pdo->lastInsertId();

        // 2. Insert Payment Record
        $transaction_id = "MPESA-" . strtoupper(bin2hex(random_bytes(5)));
        $receipt_path = "receipt.php?booking_id=" . $booking_id;
        
        $insert_pay = $pdo->prepare("
            INSERT INTO payments (booking_id, user_id, transaction_id, amount, payment_type, payment_status, receipt_path) 
            VALUES (?, ?, ?, ?, ?, 'completed', ?)
        ");
        $insert_pay->execute([
            $booking_id,
            $user_id,
            $transaction_id,
            $amount_paid,
            $payment_type,
            $receipt_path
        ]);

        // 3. Send Notification
        $notif_title = "Booking Confirmation & Payment Received";
        $notif_msg = "Your booking for the " . htmlspecialchars($car['brand'] . ' ' . $car['model']) . " from " . date('M d, Y', strtotime($pending['pickup_datetime'])) . " to " . date('M d, Y', strtotime($pending['return_datetime'])) . " has been confirmed. Payment of KSh " . number_format($amount_paid) . " was successfully processed (Ref: $transaction_id).";
        
        $insert_notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $insert_notif->execute([$user_id, $notif_title, $notif_msg]);

        // 4. Log security activity
        log_activity($pdo, $user_id, "Completed booking payment #PW-$booking_id of KSh $amount_paid via M-Pesa");

        // Commit Transaction
        $pdo->commit();

        // Clear session pending
        unset($_SESSION['pending_booking']);

        $successMsg = "Payment of KSh " . number_format($amount_paid) . " processed successfully! Booking confirmed. Reference: $transaction_id.";
        header("Location: ../frontend/dashboard.php?success=" . urlencode($successMsg));
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../frontend/checkout.php?error=" . urlencode("Failed to process payment: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../frontend/dashboard.php");
    exit;
}
?>
