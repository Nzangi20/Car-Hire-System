<?php
// backend/auth_forgot.php
require_once 'db.php';
require_once 'security.php';

// Check CSRF
csrf_protect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        header("Location: ../frontend/forgot_password.php?error=" . urlencode("Email address is required."));
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate a secure random token
            $token = bin2hex(random_bytes(16));
            
            // Store token in session with user reference and 10-minute expiry
            $_SESSION['reset_token'] = [
                'user_id' => $user['id'],
                'token' => $token,
                'expires' => time() + 600
            ];

            // Log activity
            log_activity($pdo, $user['id'], "Password reset requested");

            // For testing/simulation purposes, we output the link in the success message
            $simulatedLink = "reset_password.php?token=" . $token;
            $successMsg = "A reset link has been generated. <br><strong><a href='" . $simulatedLink . "' style='color: #15803d; text-decoration: underline;'>Click here to reset your password</a></strong>";
            
            header("Location: ../frontend/forgot_password.php?success=" . urlencode($successMsg));
            exit;
        } else {
            // Reassuring or simple message (avoid enumeration vulnerability in production, but here we can show user not found)
            header("Location: ../frontend/forgot_password.php?error=" . urlencode("No user found with that email address."));
            exit;
        }
    } catch (\PDOException $e) {
        header("Location: ../frontend/forgot_password.php?error=" . urlencode("Database error during request processing."));
        exit;
    }
} else {
    header("Location: ../frontend/forgot_password.php");
    exit;
}
?>
