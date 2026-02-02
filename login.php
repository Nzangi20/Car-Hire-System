<?php
session_start();
if (isset($_SESSION['username'])) header("Location: dashboard.php");
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli = new mysqli("localhost", "root", "", "prestige_wheels");
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    $res = $mysqli->prepare("SELECT password, is_admin FROM users WHERE username=?");
    $res->bind_param("s",$user); $res->execute(); $res->store_result();
    if($res->num_rows==0){ $msg = "Username not found."; }
    else {
        $res->bind_result($hashed,$is_admin); $res->fetch();
        if(password_verify($pass, $hashed)){
            $_SESSION['username'] = $user; $_SESSION['is_admin'] = $is_admin;
            $res->close(); $mysqli->close();
            header("Location: ".($is_admin ? "admin.php" : "dashboard.php"));
            exit;
        } else { $msg = "Invalid password."; }
    }
    $res->close(); $mysqli->close();
}
?>
<!DOCTYPE html>
<html><head>
<title>Login - Prestige Wheels Hire</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
<h2>User Login</h2>
<form method="post">
    <label>Username:</label>
    <input type="text" name="username" required>
    <label>Password:</label>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
<div class="errmsg"><?= $msg ?></div>
<p>No account?<a href="register.php">Register here</a></p>
</div>
</body>
</html>