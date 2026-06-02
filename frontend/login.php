<?php
// frontend/login.php
require_once '../backend/security.php';

if (isset($_SESSION['username'])) {
    if (($_SESSION['is_admin'] ?? 0) === 1) {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">

    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-header">
                <a href="index.php" style="font-size: 2rem; font-weight: 800; color: var(--primary-light); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                    <i class="fas fa-car-side"></i> Prestige
                </a>
                <h2>Welcome Back</h2>
                <p>Log in to manage and book your rentals</p>
            </div>

            <!-- Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form action="../backend/auth_login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
                </div>

                <div style="text-align: right; margin-top: -10px; margin-bottom: 15px;">
                    <a href="forgot_password.php" style="font-size: 0.85rem; color: var(--accent-color); text-decoration: none; font-weight: 500;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    <i class="fas fa-sign-in-alt"></i> Log In
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>
