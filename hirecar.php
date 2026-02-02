<?php
session_start();
if(!isset($_SESSION['username'])){ header('Location: login.php'); exit; }
$mysqli=new mysqli("localhost","root","","prestige_wheels");
$car_id = $_POST['car_id'] ?? '';
$hire_days = intval($_POST['hire_days'] ?? '0');
$phone = $_POST['phone'] ?? '';
if(!$car_id || $hire_days<1 || !$phone){ header('Location: dashboard.php'); exit; }
$user=$_SESSION['username'];
$userq = $mysqli->prepare("SELECT id FROM users WHERE username=?");
$userq->bind_param("s",$user); $userq->execute(); $userq->bind_result($user_id); $userq->fetch(); $userq->close();
$res = $mysqli->query("SELECT charge_per_day, status FROM cars WHERE id=$car_id");
$car = $res->fetch_assoc();
if($car['status']!=='available'){ header('Location: dashboard.php'); exit; }
$total = $hire_days * $car['charge_per_day'];
$stmt = $mysqli->prepare("INSERT INTO bookings (car_id, user_id, hire_days, phone, total_amount) VALUES (?,?,?,?,?)");
$stmt->bind_param("iiisd",$car_id,$user_id,$hire_days,$phone,$total);
$stmt->execute();
$mysqli->query("UPDATE cars SET status='hired' WHERE id=$car_id");
$stmt->close(); $mysqli->close();
echo "<!DOCTYPE html>
<html><head><title>Pay Now - Car Hire</title>
<link rel='stylesheet' href='style.css'></head>
<body>
<div class='container'>
<h2>Complete Payment</h2>
<p>Dear customer, please pay <strong>KSh ".number_format($total)."</strong> for your booking.</p>
<p>Your phone number: <strong>".htmlspecialchars($phone)."</strong></p>
<p>
To make payment, use M-Pesa or other mobile payment and confirm with the administrator.<br><br>
<a href='dashboard.php'>Back to Dashboard</a>
</p>
</div>
</body>
</html>";
exit;
?>