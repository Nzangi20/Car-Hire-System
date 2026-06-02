<?php
// backend/edit_car.php
require_once 'db.php';
require_once 'security.php';

// Access Control: Admins/Managers/Staff only
require_role(['super_admin', 'manager', 'staff']);
csrf_protect();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $charge = floatval($_POST['charge_per_day'] ?? 0);
    $status = trim($_POST['status'] ?? 'available');
    $quantity = intval($_POST['quantity'] ?? 1);
    
    $registration_number = trim($_POST['registration_number'] ?? '');
    $year = intval($_POST['year'] ?? date('Y'));
    $transmission = trim($_POST['transmission'] ?? 'automatic');
    $fuel_type = trim($_POST['fuel_type'] ?? 'Petrol');
    $capacity = intval($_POST['capacity'] ?? 5);
    $category = trim($_POST['category'] ?? 'Sedan');
    $description = trim($_POST['description'] ?? '');
    
    $clear_gallery = isset($_POST['clear_gallery']) && $_POST['clear_gallery'] == '1';

    if ($id <= 0 || empty($brand) || empty($model) || $charge <= 0 || $quantity < 1 || empty($registration_number)) {
        header("Location: ../frontend/edit_car.php?id=$id&error=" . urlencode("Please fill all required fields with valid data."));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Fetch current photo details
        $stmt = $pdo->prepare("SELECT photo FROM cars WHERE id = ?");
        $stmt->execute([$id]);
        $car = $stmt->fetch();
        if (!$car) {
            header("Location: ../frontend/admin.php?error=" . urlencode("Car not found."));
            exit;
        }

        $photoPath = $car['photo'];
        
        // Handle photo upload if a new one is selected
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = $_FILES['photo']['type'];

            if (!in_array($fileType, $allowedTypes)) {
                header("Location: ../frontend/edit_car.php?id=$id&error=" . urlencode("Invalid file format. Only JPG, PNG, and WEBP are allowed."));
                exit;
            }

            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('car_main_', true) . '.' . $ext;
            $targetDir = "../frontend/uploads/cars/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetDir . $fileName)) {
                // Delete old photo if exists
                if (!empty($car['photo']) && file_exists("../frontend/" . $car['photo'])) {
                    unlink("../frontend/" . $car['photo']);
                }
                $photoPath = 'uploads/cars/' . $fileName;
            }
        }

        // Clear Gallery if requested
        if ($clear_gallery) {
            $gal_sel = $pdo->prepare("SELECT photo_path FROM car_images WHERE car_id = ?");
            $gal_sel->execute([$id]);
            $old_gallery = $gal_sel->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($old_gallery as $gp) {
                if (file_exists("../frontend/" . $gp)) {
                    unlink("../frontend/" . $gp);
                }
            }
            
            $gal_del = $pdo->prepare("DELETE FROM car_images WHERE car_id = ?");
            $gal_del->execute([$id]);
        }

        // Handle Gallery Uploads (Multiple Photos)
        if (isset($_FILES['gallery_photos']) && is_array($_FILES['gallery_photos']['name'])) {
            $galleryDir = "../frontend/uploads/cars_gallery/";
            if (!file_exists($galleryDir)) {
                mkdir($galleryDir, 0777, true);
            }

            foreach ($_FILES['gallery_photos']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['gallery_photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $gFileName = $_FILES['gallery_photos']['name'][$key];
                    $gFileType = $_FILES['gallery_photos']['type'][$key];
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    if (in_array($gFileType, $allowedTypes)) {
                        $gExt = pathinfo($gFileName, PATHINFO_EXTENSION);
                        $gUniqueName = uniqid('car_gal_', true) . '.' . $gExt;
                        
                        if (move_uploaded_file($tmpName, $galleryDir . $gUniqueName)) {
                            $gPath = 'uploads/cars_gallery/' . $gUniqueName;
                            
                            $gal_stmt = $pdo->prepare("INSERT INTO car_images (car_id, photo_path) VALUES (?, ?)");
                            $gal_stmt->execute([$id, $gPath]);
                        }
                    }
                }
            }
        }

        // Update Car Table
        $upd_stmt = $pdo->prepare("
            UPDATE cars SET 
                brand = ?, model = ?, charge_per_day = ?, photo = ?, status = ?, quantity = ?,
                registration_number = ?, year = ?, transmission = ?, fuel_type = ?, capacity = ?, category = ?, description = ? 
            WHERE id = ?
        ");
        $upd_stmt->execute([
            $brand, $model, $charge, $photoPath, $status, $quantity,
            $registration_number, $year, $transmission, $fuel_type, $capacity, $category, $description, $id
        ]);

        // Log audit activity
        log_activity($pdo, $user_id, "Admin edited details for vehicle: $brand $model ($registration_number)");

        $pdo->commit();

        header("Location: ../frontend/admin.php?tab=fleet&success=" . urlencode("Car updated successfully!"));
        exit;
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../frontend/edit_car.php?id=$id&error=" . urlencode("Database error: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../frontend/admin.php");
    exit;
}
?>
