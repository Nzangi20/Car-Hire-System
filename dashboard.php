<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['is_admin']) header('Location: login.php');
$mysqli = new mysqli("localhost","root","","prestige_wheels");
$user = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>MyCars Hire Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css?family=Montserrat:700,400&display=swap" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Montserrat', Arial, sans-serif;
    background: linear-gradient(120deg, #ecf4fb 0%, #f8fefd 100%);
    color: #1a2e40;
}
.navbar {
    width: 100vw;
    background: #13407a;
    color: #fff;
    display: flex;
    justify-content:space-between;
    align-items:center;
    padding: 18px 24px;
    font-size: 1.13rem;
    box-shadow:0 2px 18px #223d5b33;
}
.navbar .brand {
    font-size: 1.42rem;
    font-weight: 700;
    letter-spacing:1.3px;
    display:flex;align-items:center;gap:8px;
}
.navbar a {
    color: #ffe066;
    font-weight: bold;
    text-decoration: none;
    font-size: 1.05rem;
    margin-left: 18px;
    padding: 9px 19px;
    border-radius:30px;
    background:rgba(255,255,255,0.07);
    transition:.2s;
}
.navbar a:hover {background:#ffe066;color:#13407a; }
.hero {
    background: linear-gradient(125deg,#bbeafd85 0%,#ffe06644 88%);
    padding:48px 0 20px 0;
    min-height:160px;
    display:flex;align-items:center;
    justify-content:center;
    flex-wrap:wrap;gap:24px;
}
.hero-content {
    max-width: 490px;
    padding: 0 30px;
}
.hero-content h1 {
    font-size:2.5rem;
    font-weight:900;
    margin-bottom:14px;
    letter-spacing:2.5px;
    color:#13355d;
    text-shadow:0 4px 16px #223d5b14;
}
.hero-content p {
    font-size:1.14rem;
    color:#15436b;
    margin-bottom:24px;
}
.hero-content .cta-btn {
    font-size:1.08rem;
    padding:14px 37px;
    background:linear-gradient(93deg,#16c548 0%,#ffe066 90%);
    color:#13355d;
    font-weight:700;
    border:none; border-radius:40px;
    box-shadow:0 2px 20px #16c54844;
    cursor:pointer; transition:.12s;
    letter-spacing:.4px;
}
.hero-content .cta-btn:hover {
    background:linear-gradient(93deg,#ffe066 10%,#16c548 93%);
    color:#17ac23;
    transform:scale(1.05);
}
.hero-image {
    min-width:210px;
    text-align:center;
}
.hero-image img {
    max-width:280px;
    border-radius:18px;
    box-shadow:0 8px 30px #0352de17;
}
.card-section {
    max-width:1200px;
    margin:0 auto;
    padding:24px 18px 0 18px;
}
.section-title {
    font-size: 2.12rem;
    font-weight:800;
    color:#13355d;
    margin:28px 0 14px 0;
    letter-spacing:.8px;
}
.car-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(265px,1fr));
    gap: 32px;
    margin-top:10px;
}
.car-card {
    background:#fff;
    border-radius:18px;
    box-shadow:0 4px 18px #0352de13;
    padding:20px 15px 18px 15px;
    display: flex;
    flex-direction: column;
    align-items: center;
    position:relative;
    transition:.15s;
    border:2px solid #e3ece8;
    min-height:370px;
}
.car-card:hover {
    box-shadow: 0 8px 36px #0352de2b;
    border-color: #16c54877;
    transform:scale(1.03);
}
.car-img {
    width:100%;
    max-width:190px;
    height:120px;
    border-radius:12px;
    object-fit:cover;
    background:#f7f7fa;
    margin-bottom:14px;
    box-shadow:0 2px 12px #0352de2b;
}
.car-model {
    font-size:1.12rem; font-weight:800; margin-bottom:2px;color:#189bcc;letter-spacing:.7px;
}
.car-brand {
    font-size:1.02rem;color:#7ea7c0;font-weight:700;margin-bottom:7px;
}
.car-price {
    color:#16c548;font-size:1.18rem;font-weight:700;margin-bottom:3px;
}
.car-units {
    color:#567891;
    font-size:.96rem;
    margin-bottom:10px;
}
.car-action {
    width:100%;
    text-align:center;
    margin-top:6px;
}
.car-action form {
    display:flex;flex-direction:column;align-items:center;gap:8px;width:100%;
}
.car-action label {
    font-size:.99rem;color:#29446e;font-weight:600;margin-bottom:2px;width:100%;text-align:left;
}
.car-action input[type="number"],
.car-action input[type="tel"] {
    width:94%;
    font-size:1.01rem;
    padding:8px;
    border-radius:7px;
    border:1px solid #89bffb;
    background:#f4fafd;
    color:#293b56;
    margin-bottom:4px;
    transition:.13s;
}
.car-action input[type="number"]:focus,
.car-action input[type="tel"]:focus {border-color:#16c548;}
.car-action button[type="submit"] {
    padding:11px 0;
    width:100%;
    border-radius:40px;
    background:linear-gradient(93deg,#16c548 0%,#ffe066 90%);
    color:#13355d;
    font-weight:800;
    border:none;
    font-size:1.07rem;
    box-shadow:0 2px 20px #16c54829;
    cursor:pointer;
    transition:.12s;
    margin-top:4px;
}
.car-action button[type="submit"]:hover {
    background:linear-gradient(93deg,#ffe066 10%,#16c548 93%);
    color:#19af25;
    transform:scale(1.03);
}
.hired-badge {
    display:block;
    background:#e91616e6;
    color:#fff;
    font-weight:600;
    padding:9px 0;
    border-radius:25px;
    font-size:1.12rem;
    margin-top:15px;
    width:90%;
    box-shadow:0 2px 22px #ff646488;
    text-align:center;
    letter-spacing:.7px;
}
.hired-badge .fa-lock { margin-right:7px; }
@media (max-width:700px) {
    .car-model,.section-title{font-size:1.1rem;}
    .hero-content h1{font-size:1.3rem;}
    .car-img{height:80px;}
}
.bookings-table-section{
    max-width:900px;
    margin:0 auto;
    padding:18px;
    background:#fff;
    border-radius:17px;
    box-shadow:0 2px 18px #0352de13;
}
.bookings-header {
    font-size:1.38rem;
    font-weight:800;
    color:#13407a;
    margin-bottom:15px;
    letter-spacing:.5px;
}
table {
    width:100%;
    border-collapse:collapse;
    margin-bottom:18px;
    font-size:.99rem;
}
th, td {
    padding:10px 7px;
    text-align:left;
    font-size:1rem;
}
th {
    background:#e3f6dd;
    color:#13407a;
    letter-spacing:.3px;
}
td {
    background:#f7fbfa;
    color:#222;
    border-bottom:1.5px solid #e3ece8;
}
tr:nth-child(even) td { background:#eaf8fc;}
.car-thumb {
    width:38px;height:22px;border-radius:2px;object-fit:cover;vertical-align:middle;margin-right:6px;
}
</style>
</head>
<body>
<div class="navbar">
    <span class="brand"><i class="fas fa-car-side"></i> MyCars Hire</span>
    <div>
        <?=htmlspecialchars($user)?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="hero">
    <div class="hero-content">
        <h1>Hire Premium & Affordable Cars in Kenya</h1>
        <p>
            Browse a variety of popular models: Toyota, Mercedes, BMW, Nissan and more.<br>
            Book your ride in minutes & pay with mobile money. No hidden fees.
        </p>
        <a href="#cars" class="cta-btn">Find Cars</a>
    </div>
    <div class="hero-image">
        <img src="https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=600&q=80" alt="Car rental" />
    </div>
</div>

<div id="cars" class="card-section">
    <div class="section-title"><i class="fas fa-car-side"></i> Available Cars</div>
    <div class="car-grid">
<?php
$uq = $mysqli->prepare("SELECT id FROM users WHERE username=?");
$uq->bind_param("s",$user);
$uq->execute();
$uq->bind_result($user_id);
$uq->fetch();
$uq->close();

// Get booked/hired cars for this user
$user_booked_cars = [];
$res = $mysqli->query("SELECT car_id FROM bookings WHERE user_id=$user_id AND returned=0");
while($row = $res->fetch_assoc()) $user_booked_cars[] = $row['car_id'];

$cars_res = $mysqli->query("SELECT * FROM cars");
if($cars_res->num_rows === 0) {
    echo "<p>No cars are currently available for hire.</p>";
}
while($row = $cars_res->fetch_assoc()){
    echo "<div class='car-card'>
        <img class='car-img' src='".htmlspecialchars($row['photo'])."' alt='".htmlspecialchars($row['brand']." ".$row['model'])."' />
        <div class='car-model'>".htmlspecialchars($row['model'])."</div>
        <div class='car-brand'>".htmlspecialchars($row['brand'])."</div>
        <div class='car-price'>KSh ".number_format($row['charge_per_day'])." <span style='font-size:0.95rem;font-weight:400;color:#1e495c;'>/day</span></div>
        <div class='car-units'><strong>Units available:</strong> <span style='color:#189bcc;'>".$row['quantity']."</span></div>
        <div class='car-action'>";
    if(in_array($row['id'], $user_booked_cars)) {
        echo "<span class='hired-badge'><i class='fas fa-lock'></i> Hired</span>";
    } else {
        echo "<form action='hirecar.php' method='post'>
                <label for='days".$row['id']."'>Days:</label>
                <input id='days".$row['id']."' name='hire_days' type='number' min='1' required>
                <label for='phone".$row['id']."'>Phone:</label>
                <input id='phone".$row['id']."' name='phone' type='tel' maxlength='15' pattern='^0[0-9]{9}$' required placeholder='07XXXXXXXX'>
                <input type='hidden' name='car_id' value='".htmlspecialchars($row['id'])."'>
                <button type='submit'><i class='fas fa-car-side'></i> Hire</button>
            </form>";
    }
    echo "</div></div>";
}
?>
    </div>
</div>
<div class="bookings-table-section">
    <div class="bookings-header"><i class="fas fa-list"></i> Your Bookings</div>
    <table>
        <tr><th>Car</th><th>Days</th><th>Phone</th><th>Amount (KSh)</th><th>Date</th><th>Status</th></tr>
<?php
$bk=$mysqli->query("SELECT b.*,c.brand,c.model,c.photo FROM bookings b JOIN cars c ON b.car_id=c.id WHERE b.user_id=$user_id ORDER BY b.hire_date DESC");
while($row=$bk->fetch_assoc()){
    echo "<tr>
      <td><img class='car-thumb' src='".htmlspecialchars($row['photo'])."' alt='Car' />"
        .htmlspecialchars($row['brand']." ".$row['model'])."</td>
      <td>{$row['hire_days']}</td>
      <td>".htmlspecialchars($row['phone'])."</td>
      <td>KSh ".number_format($row['total_amount'])."</td>
      <td>{$row['hire_date']}</td>
      <td>".($row['returned'] ? 'Returned' : 'Active')."</td>
    </tr>";
}
$mysqli->close();
?>
    </table>
</div>
</body>
</html>