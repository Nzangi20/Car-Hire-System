<?php
session_start();
if(!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']){ header("Location: dashboard.php"); exit; }
$mysqli = new mysqli("localhost", "root", "", "prestige_wheels");
$id = $_GET['id'] ?? '';
if (!$id) header('Location: admin.php');
$msg="";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand=$_POST['brand']; $model=$_POST['model']; $charge=$_POST['charge_per_day']; $status=$_POST['status'];
    $photopath='';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $target = "photos/" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], $target);
        $photopath = $target;
    }
    $q = $photopath ?
        "UPDATE cars SET brand=?,model=?,charge_per_day=?,status=?,photo=? WHERE id=?" :
        "UPDATE cars SET brand=?,model=?,charge_per_day=?,status=? WHERE id=?";
    $stmt=$mysqli->prepare($q);
    if($photopath)
        $stmt->bind_param("ssdssi",$brand,$model,$charge,$status,$photopath,$id);
    else
        $stmt->bind_param("ssdsi",$brand,$model,$charge,$status,$id);
    $stmt->execute(); $stmt->close();
    header('Location: admin.php'); exit;
}
$res=$mysqli->query("SELECT * FROM cars WHERE id=$id"); $car=$res->fetch_assoc();
?>
<!DOCTYPE html>
<html><head><title>Edit Car</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
<h2>Edit Car</h2>
<form action="" method="post" enctype="multipart/form-data">
Brand: <input name="brand" value="<?=htmlspecialchars($car['brand'])?>" required>
Model: <input name="model" value="<?=htmlspecialchars($car['model'])?>" required>
Charge/Day: <input name="charge_per_day" type="number" step="0.01" value="<?=$car['charge_per_day']?>" required>
Status:
<select name="status">
    <?php foreach(['available','hired','maintenance'] as $st) echo "<option".($car['status']==$st?" selected":"").">{$st}</option>"; ?>
</select>
<?php if($car['photo']): ?> Current photo: <img class="car-photo" src="<?=$car['photo']?>"><br> <?php endif; ?>
Photo (leave blank to keep): <input type="file" name="photo" accept="image/*">
<button type="submit">Save</button>
</form>
</div>
</body>
</html>