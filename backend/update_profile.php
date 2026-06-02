<?php
// backend/update_profile.php
require_once 'db.php';
require_once 'security.php';

// Access Control
require_role('customer');
csrf_protect();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'update_info') {
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $id_number = trim($_POST['id_number'] ?? '');
        $driving_license = trim($_POST['driving_license'] ?? '');

        if (empty($fullname) || empty($email) || empty($phone) || empty($id_number) || empty($driving_license)) {
            header("Location: ../frontend/profile.php?error=" . urlencode("All fields are required."));
            exit;
        }

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: ../frontend/profile.php?error=" . urlencode("Please enter a valid email address."));
            exit;
        }

        // Phone validation
        if (!preg_match('/^(07|01)[0-9]{8}$/', $phone)) {
            header("Location: ../frontend/profile.php?error=" . urlencode("Please enter a valid phone number (e.g. 0712345678)."));
            exit;
        }

        try {
            // Check if email already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                header("Location: ../frontend/profile.php?error=" . urlencode("Email address is already in use by another account."));
                exit;
            }

            // Update details
            $update = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, id_number = ?, driving_license = ? WHERE id = ?");
            $update->execute([$fullname, $email, $phone, $id_number, $driving_license, $user_id]);

            log_activity($pdo, $user_id, "User updated profile information");

            header("Location: ../frontend/profile.php?success=" . urlencode("Profile information updated successfully."));
            exit;
        } catch (\PDOException $e) {
            header("Location: ../frontend/profile.php?error=" . urlencode("Database error: Could not update profile."));
            exit;
        }
    } 
    
    elseif ($action === 'change_password') {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            header("Location: ../frontend/profile.php?error=" . urlencode("All password fields are required."));
            exit;
        }

        if ($new_password !== $confirm_password) {
            header("Location: ../frontend/profile.php?error=" . urlencode("New passwords do not match."));
            exit;
        }

        // Password strength
        if (strlen($new_password) < 8 || !preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            header("Location: ../frontend/profile.php?error=" . urlencode("New password must be at least 8 characters long and contain both letters and numbers."));
            exit;
        }

        try {
            // Fetch current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($old_password, $user['password'])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$new_hash, $user_id]);

                log_activity($pdo, $user_id, "User changed account password");

                header("Location: ../frontend/profile.php?success=" . urlencode("Password updated successfully."));
                exit;
            } else {
                header("Location: ../frontend/profile.php?error=" . urlencode("Current password is incorrect."));
                exit;
            }
        } catch (\PDOException $e) {
            header("Location: ../frontend/profile.php?error=" . urlencode("Database error changing password."));
            exit;
        }
    } 
    
    elseif ($action === 'update_photo') {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = $_FILES['profile_picture']['name'];
            $fileSize = $_FILES['profile_picture']['size'];
            
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!in_array($ext, $allowedExtensions)) {
                header("Location: ../frontend/profile.php?error=" . urlencode("Invalid file format. Only JPG, PNG, and WEBP allowed."));
                exit;
            }
            
            if ($fileSize > 2 * 1024 * 1024) {
                header("Location: ../frontend/profile.php?error=" . urlencode("Profile picture must be smaller than 2MB."));
                exit;
            }

            try {
                // Delete old photo from folder if exists
                $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                if ($user && !empty($user['profile_picture'])) {
                    $oldPath = '../frontend/' . $user['profile_picture'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $targetDir = "../frontend/uploads/profile_pics/";
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $newFileName = uniqid('profile_', true) . '.' . $ext;
                $destPath = $targetDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $dbPath = 'uploads/profile_pics/' . $newFileName;
                    $update = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $update->execute([$dbPath, $user_id]);

                    log_activity($pdo, $user_id, "User updated profile photo");

                    header("Location: ../frontend/profile.php?success=" . urlencode("Profile picture updated successfully."));
                    exit;
                } else {
                    header("Location: ../frontend/profile.php?error=" . urlencode("Could not save uploaded photo."));
                    exit;
                }
            } catch (\PDOException $e) {
                header("Location: ../frontend/profile.php?error=" . urlencode("Database error handling photo."));
                exit;
            }
        } else {
            header("Location: ../frontend/profile.php?error=" . urlencode("Please select a photo to upload."));
            exit;
        }
    } 
    
    else {
        header("Location: ../frontend/profile.php");
        exit;
    }
} else {
    header("Location: ../frontend/profile.php");
    exit;
}
?>
