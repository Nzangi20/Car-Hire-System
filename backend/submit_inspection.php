<?php
// backend/submit_inspection.php
require_once 'db.php';
require_once 'security.php';

// Access Control: Admins/Managers/Staff only
require_role(['super_admin', 'manager', 'staff']);
csrf_protect();

$inspector_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $inspection_status = trim($_POST['inspection_status'] ?? 'clean');
    $damages_description = trim($_POST['damages_description'] ?? '');
    $penalties_amount = floatval($_POST['penalties_amount'] ?? 0);
    $late_charges = floatval($_POST['late_charges'] ?? 0);

    if ($booking_id <= 0 || !in_array($inspection_status, ['clean', 'damaged'])) {
        header("Location: ../frontend/admin.php?tab=inspections&error=" . urlencode("Invalid inspection parameters."));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Verify booking
        $bk_stmt = $pdo->prepare("SELECT user_id, car_id FROM bookings WHERE id = ?");
        $bk_stmt->execute([$booking_id]);
        $booking = $bk_stmt->fetch();

        if (!$booking) {
            header("Location: ../frontend/admin.php?tab=inspections&error=" . urlencode("Booking not found."));
            exit;
        }

        // 1. Insert Inspection Record
        $ins_stmt = $pdo->prepare("
            INSERT INTO inspections (booking_id, inspector_id, inspection_status, damages_description, penalties_amount, late_charges) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ins_stmt->execute([
            $booking_id,
            $inspector_id,
            $inspection_status,
            $damages_description,
            $penalties_amount,
            $late_charges
        ]);

        // 2. Update Booking Status
        $upd_stmt = $pdo->prepare("UPDATE bookings SET returned = 1, status = 'completed' WHERE id = ?");
        $upd_stmt->execute([$booking_id]);

        // 3. Send Notification to Customer
        $notif_title = "Vehicle Return Inspection Completed";
        $notif_msg = "The return inspection for booking #PW-$booking_id has been processed. Inspection: " . strtoupper($inspection_status) . ". Penalties: KSh " . number_format($penalties_amount) . ". Late Charges: KSh " . number_format($late_charges) . ".";
        
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $notif_stmt->execute([$booking['user_id'], $notif_title, $notif_msg]);

        // 4. Log Audit Activity
        log_activity($pdo, $inspector_id, "Completed inspection for booking #PW-$booking_id. Status: $inspection_status.");

        $pdo->commit();

        header("Location: ../frontend/admin.php?tab=inspections&success=" . urlencode("Inspection record saved. Vehicle marked as returned."));
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../frontend/admin.php?tab=inspections&error=" . urlencode("Failed to save inspection: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../frontend/admin.php");
    exit;
}
?>
