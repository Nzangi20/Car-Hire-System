<?php
// backend/auth_reset.php
require_once 'db.php';
require_once 'security.php';

// Check CSRF
csrf_protect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Check if token exists in session and matches
    if (empty($token) || !isset($_SESSION['reset_token']) || $_SESSION['reset_token']['token'] !== $token) {
        header("Location: ../frontend/login.php?error=" . urlencode("Invalid or expired password reset token."));
        exit;
    }

    // Check expiry
    if (time() > $_SESSION['reset_token']['expires']) {
        unset($_SESSION['reset_token']);
        header("Location: ../frontend/login.php?error=" . urlencode("Password reset token has expired."));
        exit;
    }

    if (empty($password) || empty($confirm_password)) {
        header("Location: ../frontend/reset_password.php?token=" . urlencode($token) . "&error=" . urlencode("Please fill in all fields."));
        exit;
    }

    // Password match check
    if ($password !== $confirm_password) {
        header("Location: ../frontend/reset_password.php?token=" . urlencode($token) . "&error=" . urlencode("Passwords do not match."));
        exit;
    }

    // Strong password check
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        header("Location: ../frontend/reset_password.php?token=" . urlencode($token) . "&error=" . urlencode("Password must be at least 8 characters long and contain both letters and numbers."));
        exit;
    }

    $user_id = $_SESSION['reset_token']['user_id'];

    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update database
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);

        // Log activity
        log_activity($pdo, $user_id, "Password reset successfully completed");

        // Clear the token
        unset($_SESSION['reset_token']);

        header("Location: ../frontend/login.php?success=" . urlencode("Password updated successfully. Please log in with your new password."));
        exit;
    } catch (\PDOException $e) {
        header("Location: ../frontend/reset_password.php?token=" . urlencode($token) . "&error=" . urlencode("Database error: Could not complete password reset."));
        exit;
    }
} else {
    header("Location: ../frontend/login.php");
    exit;
}
?>
