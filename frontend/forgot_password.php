<?php
// frontend/forgot_password.php
require_once '../backend/security.php';

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Prestige Wheels</title>
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
                <h2>Forgot Password?</h2>
                <p>Enter your registered email to simulate receiving a reset link.</p>
            </div>

            <!-- Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="line-height: 1.6;">
                    <i class="fas fa-check-circle"></i> <?= $success // Allow HTML link for reset simulation ?>
                </div>
            <?php endif; ?>

            <form action="../backend/auth_forgot.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email address" required autocomplete="email">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 12px;">
                    <i class="fas fa-paper-plane"></i> Get Reset Link
                </button>
            </form>

            <div class="auth-footer">
                Back to <a href="login.php">Login</a>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>
