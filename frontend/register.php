<?php
// frontend/register.php
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">

    <div class="auth-page">
        <div class="auth-card" style="max-width: 650px;">
            <div class="auth-header">
                <a href="index.php" style="font-size: 2rem; font-weight: 800; color: var(--primary-light); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                    <i class="fas fa-car-side"></i> Prestige
                </a>
                <h2>Create Account</h2>
                <p>Register to start renting premium vehicles</p>
            </div>

            <!-- Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="../backend/auth_register.php" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: left;">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" name="fullname" id="fullname" class="form-control" placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="john@example.com" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="0712345678" pattern="^(07|01)[0-9]{8}$" required>
                </div>

                <div class="form-group">
                    <label for="id_number">National ID / Passport</label>
                    <input type="text" name="id_number" id="id_number" class="form-control" placeholder="12345678" required>
                </div>

                <div class="form-group">
                    <label for="driving_license">Driving License Number</label>
                    <input type="text" name="driving_license" id="driving_license" class="form-control" placeholder="DL-12345678" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="johndoe" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Strong password" required autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password" required autocomplete="new-password">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="profile_picture">Profile Picture (Optional)</label>
                    <input type="file" name="profile_picture" id="profile_picture" class="form-control" accept="image/*" style="padding: 8px;">
                </div>

                <button type="submit" class="btn btn-primary" style="grid-column: 1 / -1; width: 100%; margin-top: 10px; padding: 12px;">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>
