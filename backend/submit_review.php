<?php
// backend/submit_review.php
require_once 'db.php';
require_once 'security.php';

// Access Control
require_role('customer');
csrf_protect();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = intval($_POST['car_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if ($car_id <= 0 || empty($comment)) {
        header("Location: ../frontend/index.php");
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    try {
        // Double check eligibility (user has completed a booking for this car)
        $bk_stmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND car_id = ? AND status = 'completed' LIMIT 1");
        $bk_stmt->execute([$user_id, $car_id]);
        
        if ($bk_stmt->fetch()) {
            // Save review
            $stmt = $pdo->prepare("INSERT INTO reviews (car_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$car_id, $user_id, $rating, $comment]);

            log_activity($pdo, $user_id, "Submitted review for car ID $car_id");

            header("Location: ../frontend/car_details.php?id=" . $car_id . "&success=" . urlencode("Review submitted successfully!"));
        } else {
            header("Location: ../frontend/car_details.php?id=" . $car_id . "&error=" . urlencode("You can only review cars you have rented and returned."));
        }
        exit;
    } catch (\PDOException $e) {
        header("Location: ../frontend/car_details.php?id=" . $car_id . "&error=" . urlencode("Database error: Could not save review."));
        exit;
    }
} else {
    header("Location: ../frontend/index.php");
    exit;
}
?>
