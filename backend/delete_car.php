<?php
// backend/delete_car.php
require_once 'db.php';
require_once 'security.php';

// Access Control: Admins/Managers/Staff only
require_role(['super_admin', 'manager', 'staff']);
check_suspension($pdo);

$id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($id <= 0) {
    header("Location: ../frontend/admin.php?tab=fleet&error=" . urlencode("Invalid car ID."));
    exit;
}

try {
    // Get car details
    $stmt = $pdo->prepare("SELECT brand, model, registration_number, photo FROM cars WHERE id = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch();

    if ($car) {
        // Delete gallery photos from disk
        $gal_stmt = $pdo->prepare("SELECT photo_path FROM car_images WHERE car_id = ?");
        $gal_stmt->execute([$id]);
        $gallery = $gal_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($gallery as $gpath) {
            if (!empty($gpath) && file_exists("../frontend/" . $gpath)) {
                unlink("../frontend/" . $gpath);
            }
        }

        // Delete main photo from disk
        if (!empty($car['photo']) && file_exists("../frontend/" . $car['photo'])) {
            unlink("../frontend/" . $car['photo']);
        }

        // Delete from database (foreign key constraints cascade deletes car_images automatically)
        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
        $stmt->execute([$id]);

        // Log audit activity
        log_activity($pdo, $user_id, "Admin deleted car vehicle " . $car['brand'] . " " . $car['model'] . " (" . $car['registration_number'] . ")");

        header("Location: ../frontend/admin.php?tab=fleet&success=" . urlencode("Vehicle and its image media deleted successfully!"));
        exit;
    } else {
        header("Location: ../frontend/admin.php?tab=fleet&error=" . urlencode("Car not found."));
        exit;
    }
} catch (\PDOException $e) {
    header("Location: ../frontend/admin.php?tab=fleet&error=" . urlencode("Database error: Could not delete car. It may be linked to active bookings."));
    exit;
}
?>
