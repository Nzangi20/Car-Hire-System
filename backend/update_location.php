<?php
// backend/update_location.php
require_once 'db.php';
require_once 'security.php';

// Access Control
require_role(['super_admin', 'manager', 'staff']);
csrf_protect();

$car_id = intval($_POST['car_id'] ?? 0);

// The preset dropdown sends "lat,lng" as location_preset.
// The JS also copies these into hidden latitude/longitude fields,
// but as a server-side fallback we parse the preset value directly.
$location_preset = trim($_POST['location_preset'] ?? '');
$latitude = null;
$longitude = null;

if (!empty($location_preset) && strpos($location_preset, ',') !== false) {
    // Parse from preset value (e.g. "-1.2616,36.8021")
    $parts = explode(',', $location_preset);
    $latitude = floatval($parts[0]);
    $longitude = floatval($parts[1]);
} else {
    // Fall back to the hidden input values
    $latitude = floatval($_POST['latitude'] ?? '');
    $longitude = floatval($_POST['longitude'] ?? '');
}

// Validate: car_id must be positive, coords must be reasonable (not both exactly 0)
if ($car_id <= 0 || ($latitude == 0 && $longitude == 0)) {
    header("Location: ../frontend/admin.php?tab=tracker&error=" . urlencode("Please select a location preset before pinging."));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT brand, model FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();
    
    if (!$car) {
        header("Location: ../frontend/admin.php?tab=tracker&error=" . urlencode("Vehicle not found."));
        exit;
    }

    $stmt = $pdo->prepare("UPDATE cars SET latitude = ?, longitude = ?, last_tracked = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$latitude, $longitude, $car_id]);

    log_activity($pdo, $_SESSION['user_id'], "Admin updated tracking simulation coordinates of " . $car['brand'] . " " . $car['model'] . " to ($latitude, $longitude)");

    header("Location: ../frontend/admin.php?tab=tracker&success=" . urlencode("GPS coordinates of " . $car['brand'] . " " . $car['model'] . " updated to ($latitude, $longitude)."));
    exit;
} catch (\PDOException $e) {
    header("Location: ../frontend/admin.php?tab=tracker&error=" . urlencode("Database error updating GPS coordinates."));
    exit;
}
?>
