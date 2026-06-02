<?php
// backend/add_car.php
require_once 'db.php';
require_once 'security.php';

// Access Control: Admins/Managers/Staff only
require_role(['super_admin', 'manager', 'staff']);
csrf_protect();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $charge = floatval($_POST['charge_per_day'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $registration_number = trim($_POST['registration_number'] ?? '');
    $year = intval($_POST['year'] ?? date('Y'));
    $transmission = trim($_POST['transmission'] ?? 'automatic');
    $fuel_type = trim($_POST['fuel_type'] ?? 'Petrol');
    $capacity = intval($_POST['capacity'] ?? 5);
    $category = trim($_POST['category'] ?? 'Sedan');
    $description = trim($_POST['description'] ?? '');

    if (empty($brand) || empty($model) || $charge <= 0 || $quantity < 1 || empty($registration_number)) {
        header("Location: ../frontend/admin.php?tab=fleet&error=" . urlencode("Please fill in all required fields."));
        exit;
    }

    $photoPath = '';
    // Main Photo Upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = $_FILES['photo']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            header("Location: ../frontend/admin.php?tab=fleet&error=" . urlencode("Invalid main image format. Only JPG, PNG, and WEBP allowed."));
            exit;
        }

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('car_main_', true) . '.' . $ext;
        $targetDir = "../frontend/uploads/cars/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetDir . $fileName)) {
            $photoPath = 'uploads/cars/' . $fileName;
        } else {
            header("Location: ../frontend/admin.php?tab=fleet&error=" . urlencode("Failed to upload the main photo."));
            exit;
        }
    } else {
        header("Location: ../frontend/admin.php?tab=fleet&error=" . urlencode("Please select a main car photo."));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Insert into cars
        $stmt = $pdo->prepare("
            INSERT INTO cars (brand, model, charge_per_day, photo, status, quantity, registration_number, year, transmission, fuel_type, capacity, category, description) 
            VALUES (?, ?, ?, ?, 'available', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $brand, $model, $charge, $photoPath, $quantity,
            $registration_number, $year, $transmission, $fuel_type, $capacity, $category, $description
        ]);
        $car_id = $pdo->lastInsertId();

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
                            $gal_stmt->execute([$car_id, $gPath]);
                        }
                    }
                }
            }
        }

        // Log audit activity
        log_activity($pdo, $user_id, "Admin added car model $brand $model ($registration_number) to the fleet");

        $pdo->commit();

        header("Location: ../frontend/admin.php?tab=fleet&success=" . urlencode("Vehicle and gallery added successfully!"));
        exit;
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../frontend/admin.php?tab=fleet&error=" . urlencode("Database error: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../frontend/admin.php");
    exit;
}
?>
