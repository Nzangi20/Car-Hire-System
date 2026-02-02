<?php
session_start();
if(!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']){ exit; }
$mysqli = new mysqli("localhost", "root", "", "prestige_wheels");
$id = $_GET['id'] ?? '';
if($id) $mysqli->query("DELETE FROM cars WHERE id=$id");
header("Location: admin.php");
?>