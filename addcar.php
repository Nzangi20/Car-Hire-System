<?php
session_start();
if(!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']){ header("Location: dashboard.php"); exit; }
$mysqli = new mysqli("localhost", "root", "", "prestige_wheels");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = $_POST['brand']; $model = $_POST['model'];
    $charge = $_POST['charge_per_day'];
    $photoPath = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $target = "photos/" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], $target);
        $photoPath = $target;
    }
    if($brand&&$model&&$charge&&$photoPath){
        $stmt = $mysqli->prepare("INSERT INTO cars (brand,model,charge_per_day,photo) VALUES (?,?,?,?)");
        $stmt->bind_param("ssds",$brand,$model,$charge,$photoPath);
        $stmt->execute(); $stmt->close();
    }
}
header("Location: admin.php");
?>