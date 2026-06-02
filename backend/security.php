<?php
// backend/security.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Session Inactivity Timeout (30 minutes = 1800 seconds)
$timeout_duration = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: /Car_Hire_System/frontend/login.php?error=" . urlencode("Session expired due to inactivity."));
    exit;
}
$_SESSION['last_activity'] = time();

// 2. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function get_csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// CSRF check helper for POST requests (excluding auth/logout actions that handle redirection themselves)
function csrf_protect() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($csrf_token)) {
            header("HTTP/1.1 403 Forbidden");
            echo "403 Forbidden: Invalid CSRF Token.";
            exit;
        }
    }
}

// 3. Activity Logging Helper
function log_activity($pdo, $user_id, $action) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $action, $ip]);
    } catch (Exception $e) {
        // Fail silently
    }
}

// 4. Role Authorization Helpers
function require_login() {
    if (!isset($_SESSION['username'])) {
        header("Location: /Car_Hire_System/frontend/login.php");
        exit;
    }
}

function require_role($roles) {
    require_login();
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    $user_role = $_SESSION['role'] ?? 'customer';
    if (!in_array($user_role, $roles)) {
        header("Location: /Car_Hire_System/frontend/login.php?error=" . urlencode("Unauthorized access."));
        exit;
    }
}

// 5. Check if user is suspended
function check_suspension($pdo) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT is_suspended FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user && $user['is_suspended'] == 1) {
            session_unset();
            session_destroy();
            header("Location: /Car_Hire_System/frontend/login.php?error=" . urlencode("Your account has been suspended. Please contact support."));
            exit;
        }
    }
}
?>
