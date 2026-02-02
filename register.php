<?php
session_start();
if (isset($_SESSION['username'])) header("Location: dashboard.php");
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli = new mysqli("localhost", "root", "", "prestige_wheels");
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    if(!$user || !$pass){ $msg = "Fill username and password."; }
    elseif(strlen($user)<3 || strlen($pass)<4){ $msg = "Username/password too short."; }
    else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?,?)");
        $stmt->bind_param("ss",$user,$hash);
        if($stmt->execute()){
            $msg = "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            $msg = "Username already exists.";
        }
        $stmt->close();
    }
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html><head>
<title>Register - Prestige Wheels Hire</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
<h2>User Registration</h2>
<form action="register.php" method="post">
    <label for="fullname">Full Name:</label>
    <input type="text" name="fullname" id="fullname" required>

    <label for="id_number">National ID / Passport Number:</label>
    <input type="text" name="id_number" id="id_number" required>

    <label for="username">Username:</label>
    <input type="text" name="username" id="username" required>

    <label for="password">Password:</label>
    <input type="password" name="password" id="password" required>

    <!-- Add other fields if needed -->

    <button type="submit">Register</button>
</form>
<div class="errmsg"><?= $msg ?></div>
<p>Already have an account? <a href="login.php">Login here</a></p>
</div>
</body>
</html>