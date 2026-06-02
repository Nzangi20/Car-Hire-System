<?php
// frontend/profile.php
require_once '../backend/db.php';
require_once '../backend/security.php';

// Access Control
require_role('customer');
check_suspension($pdo);

$user_id = $_SESSION['user_id'];
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (\PDOException $e) {
    $error = "Failed to load profile details.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Prestige Wheels</title>
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
            <a href="dashboard.php" style="margin-right: 15px; color: white; text-decoration: none; font-weight: 500;">
                <i class="fas fa-columns"></i> Dashboard
            </a>
            <span>Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
            <a href="logout.php" style="margin-left: 15px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container" style="padding-top: 40px; padding-bottom: 60px;">
        
        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
            
            <!-- Left Panel: Profile summary & Photo -->
            <div class="card" style="flex: 1; min-width: 300px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px;">
                <div style="position: relative; margin-bottom: 20px;">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-light); box-shadow: 0 8px 16px rgba(0,0,0,0.15);">
                    <?php else: ?>
                        <div style="width: 150px; height: 150px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; border: 4px solid #cbd5e1; box-shadow: 0 8px 16px rgba(0,0,0,0.1);">
                            <i class="fas fa-user" style="font-size: 5rem; color: #94a3b8;"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 style="font-size: 1.5rem; color: var(--text-dark); margin-bottom: 5px;"><?= htmlspecialchars($user['fullname']) ?></h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">@<?= htmlspecialchars($user['username']) ?></p>

                <!-- Document Verification Badge -->
                <div style="margin-bottom: 30px;">
                    <span class="status-badge" style="
                        padding: 8px 16px; 
                        border-radius: 50px; 
                        font-weight: 700; 
                        font-size: 0.85rem;
                        text-transform: uppercase;
                        <?php
                            if ($user['verification_status'] === 'verified') {
                                echo 'background: #dcfce7; color: #15803d;';
                            } elseif ($user['verification_status'] === 'pending') {
                                echo 'background: #fef9c3; color: #a16207;';
                            } elseif ($user['verification_status'] === 'rejected') {
                                echo 'background: #fee2e2; color: #b91c1c;';
                            } else {
                                echo 'background: #f1f5f9; color: #475569;';
                            }
                        ?>
                    ">
                        <i class="fas <?php
                            if ($user['verification_status'] === 'verified') {
                                echo 'fa-check-circle';
                            } elseif ($user['verification_status'] === 'pending') {
                                echo 'fa-clock';
                            } elseif ($user['verification_status'] === 'rejected') {
                                echo 'fa-times-circle';
                            } else {
                                echo 'fa-info-circle';
                            }
                        ?>"></i>
                        Verification Status: <?= htmlspecialchars($user['verification_status']) ?>
                    </span>
                </div>

                <!-- Profile Photo Form -->
                <form action="../backend/update_profile.php" method="POST" enctype="multipart/form-data" style="width: 100%; border-top: 1px solid #f1f5f9; padding-top: 25px;">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                    <input type="hidden" name="action" value="update_photo">
                    <div class="form-group" style="text-align: left;">
                        <label for="profile_picture" style="font-size: 0.85rem; font-weight: 600;">Upload Profile Photo</label>
                        <input type="file" name="profile_picture" id="profile_picture" class="form-control" accept="image/*" required style="padding: 8px; margin-bottom: 10px;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-camera"></i> Change Photo
                    </button>
                </form>
            </div>

            <!-- Right Panel: Edit Details & Password -->
            <div style="flex: 2; min-width: 320px; display: flex; flex-direction: column; gap: 30px;">
                
                <!-- Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" style="margin: 0;">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" style="margin: 0;">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Details Card -->
                <div class="card" style="padding: 30px;">
                    <h3 style="margin-bottom: 20px; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-id-card"></i> Edit Profile Info
                    </h3>
                    <form action="../backend/update_profile.php" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        <input type="hidden" name="action" value="update_info">

                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" name="fullname" id="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" pattern="^(07|01)[0-9]{8}$" required>
                        </div>

                        <div class="form-group">
                            <label for="id_number">National ID / Passport</label>
                            <input type="text" name="id_number" id="id_number" class="form-control" value="<?= htmlspecialchars($user['id_number']) ?>" required>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="driving_license">Driving License Number</label>
                            <input type="text" name="driving_license" id="driving_license" class="form-control" value="<?= htmlspecialchars($user['driving_license'] ?? '') ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary" style="grid-column: 1 / -1; justify-self: end; padding: 12px 30px;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>

                <!-- Password Card -->
                <div class="card" style="padding: 30px;">
                    <h3 style="margin-bottom: 20px; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-lock"></i> Change Password
                    </h3>
                    <form action="../backend/update_profile.php" method="POST" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="old_password">Current Password</label>
                            <input type="password" name="old_password" id="old_password" class="form-control" placeholder="Enter current password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="At least 8 characters with letters & numbers" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required>
                        </div>

                        <button type="submit" class="btn btn-primary" style="justify-self: end; padding: 12px 30px; margin-top: 10px;">
                            <i class="fas fa-key"></i> Update Password
                        </button>
                    </form>
                </div>

            </div>

        </div>

    </div>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>
