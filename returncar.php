<?php
session_start();
if(!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']){ exit; }
$mysqli = new mysqli("localhost", "root", "", "prestige_wheels");
$booking_id = $_GET['id'] ?? '';
if($booking_id){
    $booking = $mysqli->query("SELECT car_id FROM bookings WHERE id=$booking_id")->fetch_assoc();
    $car_id = $booking['car_id'];
    $mysqli->query("UPDATE bookings SET returned=1 WHERE id=$booking_id");
    $mysqli->query("UPDATE cars SET status='available' WHERE id=$car_id");
}
header("Location: admin.php");
?>