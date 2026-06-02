<?php
// frontend/reset_password.php
require_once '../backend/security.php';

$token = $_GET['token'] ?? '';
$error = $_GET['error'] ?? '';

// Check if token exists in session and matches
if (empty($token) || !isset($_SESSION['reset_token']) || $_SESSION['reset_token']['token'] !== $token) {
    header("Location: login.php?error=" . urlencode("Invalid or expired password reset token."));
    exit;
}

// Check expiry (10 minutes)
if (time() > $_SESSION['reset_token']['expires']) {
    unset($_SESSION['reset_token']);
    header("Location: login.php?error=" . urlencode("Password reset token has expired."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">

    <div class="auth-page">
        <div class="auth-card" style="max-width: 500px;">
            <div class="auth-header">
                <a href="index.php" style="font-size: 2rem; font-weight: 800; color: var(--primary-light); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                    <i class="fas fa-car-side"></i> Prestige
                </a>
                <h2>Reset Password</h2>
                <p>Please enter your new strong password below.</p>
            </div>

            <!-- Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="../backend/auth_reset.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="At least 8 characters with letters & numbers" required autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your new password" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 12px;">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>
