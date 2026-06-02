<?php
// backend/hire_car.php
require_once 'db.php';
require_once 'security.php';

// Access Control
require_role('customer');
check_suspension($pdo);
csrf_protect();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = intval($_POST['car_id'] ?? 0);
    $pickup_datetime = trim($_POST['pickup_datetime'] ?? '');
    $return_datetime = trim($_POST['return_datetime'] ?? '');
    $pickup_location = trim($_POST['pickup_location'] ?? '');
    $return_location = trim($_POST['return_location'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $special_requests = trim($_POST['special_requests'] ?? '');

    if ($car_id <= 0 || empty($pickup_datetime) || empty($return_datetime) || empty($pickup_location) || empty($return_location) || empty($phone)) {
        header("Location: ../frontend/book_car.php?car_id=$car_id&error=" . urlencode("Please fill in all required fields."));
        exit;
    }

    // Phone validation (Kenyan format e.g. 07XXXXXXXX or 01XXXXXXXX)
    if (!preg_match('/^(07|01)[0-9]{8}$/', $phone)) {
        header("Location: ../frontend/book_car.php?car_id=$car_id&error=" . urlencode("Please enter a valid Kenyan phone number (e.g. 0712345678)."));
        exit;
    }

    // Check if user is verified
    $usr_stmt = $pdo->prepare("SELECT verification_status FROM users WHERE id = ?");
    $usr_stmt->execute([$user_id]);
    $user = $usr_stmt->fetch();

    if ($user['verification_status'] !== 'verified') {
        header("Location: ../frontend/upload_docs.php?error=" . urlencode("You must upload verification documents and receive admin approval before booking."));
        exit;
    }

    try {
        // Parse dates
        $pickup = new DateTime($pickup_datetime);
        $return = new DateTime($return_datetime);
        $now = new DateTime();

        // 1. Pickup must be in future (or very close to now)
        if ($pickup < $now->modify('-5 minutes')) {
            header("Location: ../frontend/book_car.php?car_id=$car_id&error=" . urlencode("Pickup date and time cannot be in the past."));
            exit;
        }

        // 2. Return must be after pickup
        if ($return <= $pickup) {
            header("Location: ../frontend/book_car.php?car_id=$car_id&error=" . urlencode("Return date and time must be after the pickup date and time."));
            exit;
        }

        // Calculate hire days (round up)
        $diff = $pickup->diff($return);
        $diff_in_seconds = $return->getTimestamp() - $pickup->getTimestamp();
        $hire_days = ceil($diff_in_seconds / (60 * 60 * 24));
        if ($hire_days < 1) {
            $hire_days = 1;
        }

        // Check car status and capacity
        $car_stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
        $car_stmt->execute([$car_id]);
        $car = $car_stmt->fetch();

        if (!$car) {
            header("Location: ../frontend/index.php?error=" . urlencode("Selected car was not found."));
            exit;
        }

        if ($car['status'] !== 'available') {
            header("Location: ../frontend/book_car.php?car_id=$car_id&error=" . urlencode("This vehicle is currently undergoing maintenance."));
            exit;
        }

        // Check double bookings / overlapping bookings
        // Verify if total overlapping bookings during this interval is less than car quantity
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
            $car_id, 
            $pickup_datetime, $pickup_datetime, 
            $return_datetime, $return_datetime, 
            $pickup_datetime, $return_datetime
        ]);
        $booked_count = $overlap_stmt->fetchColumn();

        if ($booked_count >= $car['quantity']) {
            header("Location: ../frontend/book_car.php?car_id=$car_id&error=" . urlencode("This vehicle is fully booked during the selected date and time range."));
            exit;
        }

        // Calculate costs
        $total_amount = $hire_days * $car['charge_per_day'];

        // Store pending booking details in session
        $_SESSION['pending_booking'] = [
            'car_id' => $car_id,
            'pickup_datetime' => $pickup_datetime,
            'return_datetime' => $return_datetime,
            'pickup_location' => $pickup_location,
            'return_location' => $return_location,
            'phone' => $phone,
            'special_requests' => $special_requests,
            'hire_days' => $hire_days,
            'total_amount' => $total_amount
        ];

        header("Location: ../frontend/checkout.php");
        exit;
    } catch (Exception $e) {
        header("Location: ../frontend/book_car.php?car_id=$car_id&error=" . urlencode("Error checking availability: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../frontend/index.php");
    exit;
}
?>
