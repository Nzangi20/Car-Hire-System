<?php
// frontend/edit_car.php
require_once '../backend/db.php';
require_once '../backend/security.php';

// Access Control: Admins/Managers/Staff only
require_role(['super_admin', 'manager', 'staff']);
check_suspension($pdo);

$id = intval($_GET['id'] ?? 0);
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($id <= 0) {
    header("Location: admin.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch();

    if (!$car) {
        header("Location: admin.php?error=" . urlencode("Car not found."));
        exit;
    }

    // Fetch existing gallery count
    $gal_stmt = $pdo->prepare("SELECT COUNT(*) FROM car_images WHERE car_id = ?");
    $gal_stmt->execute([$id]);
    $gallery_count = $gal_stmt->fetchColumn();
} catch (\PDOException $e) {
    header("Location: admin.php?error=" . urlencode("Database error."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Car - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: var(--bg-light);">

    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="brand">
            <i class="fas fa-car-side"></i> Prestige Wheels
        </a>
        <div class="navbar-user">
            <span>Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
            <a href="admin.php?tab=fleet" style="margin-left: 15px; color: white; text-decoration: none; font-weight: 500;"><i class="fas fa-arrow-left"></i> Fleet Manager</a>
        </div>
    </nav>

    <div class="main-container" style="padding-top: 40px; padding-bottom: 60px; max-width: 800px;">
        <div class="card" style="padding: 35px;">
            <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">
                <h2 style="color: var(--text-dark); margin: 0;">Edit Vehicle Specifications</h2>
                <p style="color: var(--text-muted); margin: 5px 0 0 0;">Update technical attributes, pricing, status, and image gallery.</p>
            </div>

            <!-- Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="../backend/edit_car.php" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <input type="hidden" name="id" value="<?= $car['id'] ?>">

                <div class="form-group">
                    <label for="brand">Brand / Make</label>
                    <input type="text" name="brand" id="brand" class="form-control" value="<?= htmlspecialchars($car['brand']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="model">Model</label>
                    <input type="text" name="model" id="model" class="form-control" value="<?= htmlspecialchars($car['model']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="registration_number">Registration Number (Plate)</label>
                    <input type="text" name="registration_number" id="registration_number" class="form-control" value="<?= htmlspecialchars($car['registration_number'] ?? '') ?>" placeholder="e.g. KDL 234G" required>
                </div>

                <div class="form-group">
                    <label for="year">Year of Manufacture</label>
                    <input type="number" name="year" id="year" class="form-control" value="<?= htmlspecialchars($car['year'] ?? date('Y')) ?>" min="1990" max="<?= date('Y')+1 ?>" required>
                </div>

                <div class="form-group">
                    <label for="charge_per_day">Charge Per Day (KSh)</label>
                    <input type="number" name="charge_per_day" id="charge_per_day" class="form-control" value="<?= htmlspecialchars($car['charge_per_day']) ?>" min="1" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity (Total Units)</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" value="<?= htmlspecialchars($car['quantity']) ?>" min="1" required>
                </div>

                <div class="form-group">
                    <label for="transmission">Transmission</label>
                    <select name="transmission" id="transmission" class="form-control">
                        <option value="automatic" <?= ($car['transmission'] ?? 'automatic') === 'automatic' ? 'selected' : '' ?>>Automatic</option>
                        <option value="manual" <?= ($car['transmission'] ?? 'automatic') === 'manual' ? 'selected' : '' ?>>Manual</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fuel_type">Fuel Type</label>
                    <input type="text" name="fuel_type" id="fuel_type" class="form-control" value="<?= htmlspecialchars($car['fuel_type'] ?? 'Petrol') ?>" placeholder="e.g. Petrol, Diesel, Hybrid" required>
                </div>

                <div class="form-group">
                    <label for="capacity">Seating Capacity</label>
                    <input type="number" name="capacity" id="capacity" class="form-control" value="<?= htmlspecialchars($car['capacity'] ?? 5) ?>" min="2" max="60" required>
                </div>

                <div class="form-group">
                    <label for="category">Vehicle Category</label>
                    <select name="category" id="category" class="form-control">
                        <option value="SUV" <?= ($car['category'] ?? 'SUV') === 'SUV' ? 'selected' : '' ?>>SUV</option>
                        <option value="Sedan" <?= ($car['category'] ?? 'SUV') === 'Sedan' ? 'selected' : '' ?>>Sedan</option>
                        <option value="Luxury" <?= ($car['category'] ?? 'SUV') === 'Luxury' ? 'selected' : '' ?>>Luxury</option>
                        <option value="Mini-Bus" <?= ($car['category'] ?? 'SUV') === 'Mini-Bus' ? 'selected' : '' ?>>Mini-Bus / Van</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Administrative Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="available" <?= $car['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="maintenance" <?= $car['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label for="description">Vehicle Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter special features, options, etc..."><?= htmlspecialchars($car['description'] ?? '') ?></textarea>
                </div>

                <div style="grid-column: span 2; display: flex; gap: 20px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: var(--radius-md); align-items: center; justify-content: space-around;">
                    <div style="text-align: center;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 5px;">Current Main Photo</span>
                        <img src="<?= htmlspecialchars($car['photo']) ?>" alt="car" style="width: 100px; height: 65px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid #e2e8f0;">
                    </div>
                    <div style="text-align: center;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 5px;">Gallery Photos</span>
                        <strong style="font-size: 1.2rem; color: var(--text-dark);"><?= $gallery_count ?> files</strong>
                    </div>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label for="photo">Change Main Photo (leave blank to keep current)</label>
                    <input type="file" name="photo" id="photo" class="form-control" accept="image/*" style="padding: 8px;">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label for="gallery_photos">Add Gallery Photos (Multiple files allowed)</label>
                    <input type="file" name="gallery_photos[]" id="gallery_photos" class="form-control" accept="image/*" multiple style="padding: 8px;">
                </div>

                <div class="form-group" style="grid-column: span 2; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="clear_gallery" id="clear_gallery" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="clear_gallery" style="margin: 0; font-weight: 600; color: #b91c1c; cursor: pointer;">Delete all existing gallery photos before uploading new ones</label>
                </div>

                <button type="submit" class="btn btn-primary" style="grid-column: span 2; padding: 15px; justify-content: center; font-size: 1.1rem; margin-top: 10px;">
                    <i class="fas fa-save"></i> Save Specifications
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date('Y') ?> Prestige Wheels Kenya. All rights reserved. Professional Car Hire Platform.</p>
    </footer>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>
