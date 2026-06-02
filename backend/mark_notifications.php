<?php
// backend/mark_notifications.php
require_once 'db.php';
require_once 'security.php';

// Access Control
require_login();
check_suspension($pdo);

$user_id = $_SESSION['user_id'];
$notif_id = trim($_GET['id'] ?? '');

try {
    if ($notif_id === 'all') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } elseif (intval($notif_id) > 0) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([intval($notif_id), $user_id]);
    }
    
    // Redirect back to referring page or dashboard
    $referer = $_SERVER['HTTP_REFERER'] ?? '../frontend/dashboard.php';
    header("Location: " . $referer);
    exit;
} catch (\PDOException $e) {
    header("Location: ../frontend/dashboard.php?error=" . urlencode("Could not update notifications."));
    exit;
}
?>
