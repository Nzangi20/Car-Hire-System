<?php
// backend/return_car.php
require_once 'db.php';
require_once 'security.php';

// Access Control
require_role(['super_admin', 'manager', 'staff']);
check_suspension($pdo);

$booking_id = intval($_GET['id'] ?? 0);

header("Location: ../frontend/admin.php?tab=inspections&error=" . urlencode("Please perform a formal vehicle return inspection using the panel below."));
exit;
?>
