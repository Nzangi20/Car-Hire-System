<?php
session_start();
if(!isset($_SESSION['username']) || !$_SESSION['is_admin']) header('Location: login.php');
$mysqli=new mysqli("localhost","root","","prestige_wheels");
?>
<!DOCTYPE html>
<html><head>
<title>Admin - Prestige Wheels Hire</title>
<link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
<nav>
Hello <strong><?=htmlspecialchars($_SESSION['username'])?></strong> <span class="admin-badge">(ADMIN)</span>
 <a href="logout.php">Logout</a>
</nav>
<h2>Manage Cars</h2>
<div class="table-responsive">
<table>
<tr><th>Brand</th><th>Model</th><th>Charge/Day (KSh)</th><th>Photo</th><th>Status</th><th>Actions</th></tr>
<?php
$res=$mysqli->query("SELECT * FROM cars");
while($row=$res->fetch_assoc()){
    echo "<tr>
        <td>{$row['brand']}</td>
        <td>{$row['model']}</td>
        <td>KSh ".number_format($row['charge_per_day'])."</td>
        <td><img class='car-photo' src='{$row['photo']}'></td>
        <td>{$row['status']}</td>
        <td>
            <a href='delete_car.php?id={$row['id']}'>Delete</a> |
            <a href='edit_car.php?id={$row['id']}'>Edit</a>
        </td>
    </tr>";
}
?>
</table></div>
<h3>Add Car</h3>
<form action="add_car.php" method="post" enctype="multipart/form-data">
    Brand: <input name="brand" required>
    Model: <input name="model" required>
    Charge/Day (KSh): <input name="charge_per_day" type="number" step="0.01" required>
    Photo: <input type="file" name="photo" accept="image/*" required>
    <button type="submit">Add Car</button>
</form>
<h2>All Bookings</h2>
<div class="table-responsive">
<table>
<tr><th>User</th><th>Car</th><th>Days</th><th>Phone</th><th>Amount (KSh)</th><th>Date</th><th>Status</th><th>Return</th></tr>
<?php
$bk=$mysqli->query("SELECT b.*,u.username, c.brand, c.model FROM bookings b JOIN users u ON b.user_id=u.id JOIN cars c ON b.car_id=c.id ORDER BY b.hire_date DESC");
while($row=$bk->fetch_assoc()){
    echo "<tr>
      <td>{$row['username']}</td>
      <td>{$row['brand']} {$row['model']}</td>
      <td>{$row['hire_days']}</td>
      <td>".htmlspecialchars($row['phone'])."</td>
      <td>KSh ".number_format($row['total_amount'])."</td>
      <td>{$row['hire_date']}</td>
      <td>".($row['returned'] ? 'Returned' : 'Active')."</td>
      <td>";
    if(!$row['returned']) echo "<a href='return_car.php?id={$row['id']}'>Mark Returned</a>";
    echo "</td></tr>";
}
?>
</table></div>
<?php $mysqli->close(); ?>
</div>
</body>
</html>