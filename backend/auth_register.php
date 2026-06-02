<?php
// backend/auth_register.php
require_once 'db.php';
require_once 'security.php';

// Check CSRF
csrf_protect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $driving_license = trim($_POST['driving_license'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Required check
    if (empty($fullname) || empty($email) || empty($phone) || empty($id_number) || empty($driving_license) || empty($username) || empty($password) || empty($confirm_password)) {
        header("Location: ../frontend/register.php?error=" . urlencode("All fields except profile picture are required."));
        exit;
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../frontend/register.php?error=" . urlencode("Please enter a valid email address."));
        exit;
    }

    // Phone validation (Kenyan format e.g. 07XXXXXXXX or 01XXXXXXXX)
    if (!preg_match('/^(07|01)[0-9]{8}$/', $phone)) {
        header("Location: ../frontend/register.php?error=" . urlencode("Please enter a valid phone number (e.g. 0712345678)."));
        exit;
    }

    // Password match check
    if ($password !== $confirm_password) {
        header("Location: ../frontend/register.php?error=" . urlencode("Passwords do not match."));
        exit;
    }

    // Strong password check (min 8 chars, 1 letter, 1 number)
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        header("Location: ../frontend/register.php?error=" . urlencode("Password must be at least 8 characters long and contain both letters and numbers."));
        exit;
    }

    try {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            header("Location: ../frontend/register.php?error=" . urlencode("Username is already taken."));
            exit;
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header("Location: ../frontend/register.php?error=" . urlencode("Email address is already registered."));
            exit;
        }

        // Profile Picture Upload
        $profile_pic_path = NULL;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = $_FILES['profile_picture']['name'];
            $fileSize = $_FILES['profile_picture']['size'];
            $fileType = $_FILES['profile_picture']['type'];
            
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!in_array($ext, $allowedExtensions)) {
                header("Location: ../frontend/register.php?error=" . urlencode("Invalid file format for profile picture. Only JPG, PNG, and WEBP allowed."));
                exit;
            }
            
            // Limit to 2MB
            if ($fileSize > 2 * 1024 * 1024) {
                header("Location: ../frontend/register.php?error=" . urlencode("Profile picture must be smaller than 2MB."));
                exit;
            }
            
            $targetDir = "../frontend/uploads/profile_pics/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $newFileName = uniqid('profile_', true) . '.' . $ext;
            $destPath = $targetDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $profile_pic_path = 'uploads/profile_pics/' . $newFileName;
            }
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user (role defaults to 'customer' in SQL, we set it explicitly here)
        $insert_stmt = $pdo->prepare("
            INSERT INTO users (username, password, fullname, email, phone, id_number, driving_license, profile_picture, role, verification_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'customer', 'unverified')
        ");
        $insert_stmt->execute([$username, $hashed_password, $fullname, $email, $phone, $id_number, $driving_license, $profile_pic_path]);

        $user_id = $pdo->lastInsertId();
        
        // Log activity
        log_activity($pdo, $user_id, "User registered successfully");

        // Redirect to login page on success
        header("Location: ../frontend/login.php?success=" . urlencode("Registration successful. Please log in."));
        exit;
    } catch (\PDOException $e) {
        header("Location: ../frontend/register.php?error=" . urlencode("Database error: Could not complete registration."));
        exit;
    }
} else {
    header("Location: ../frontend/register.php");
    exit;
}
?>
