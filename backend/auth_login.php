<?php
// backend/auth_login.php
require_once 'db.php';
require_once 'security.php';

// Check CSRF
csrf_protect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: ../frontend/login.php?error=" . urlencode("Please fill in all fields."));
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, is_admin, role, is_suspended FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check suspension status
            if ((int)$user['is_suspended'] === 1) {
                header("Location: ../frontend/login.php?error=" . urlencode("Your account has been suspended. Please contact support."));
                exit;
            }

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_admin'] = (int)$user['is_admin'];
            $_SESSION['last_activity'] = time();

            // Log activity
            log_activity($pdo, $user['id'], "User logged in successfully");

            // Redirect based on role
            if (in_array($user['role'], ['super_admin', 'manager', 'staff'])) {
                header("Location: ../frontend/admin.php");
            } else {
                header("Location: ../frontend/dashboard.php");
            }
            exit;
        } else {
            header("Location: ../frontend/login.php?error=" . urlencode("Invalid username/email or password."));
            exit;
        }
    } catch (\PDOException $e) {
        header("Location: ../frontend/login.php?error=" . urlencode("System error during login."));
        exit;
    }
} else {
    header("Location: ../frontend/login.php");
    exit;
}
?>
