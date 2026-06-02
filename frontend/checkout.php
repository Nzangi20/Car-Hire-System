<?php
// frontend/checkout.php
require_once '../backend/db.php';
require_once '../backend/security.php';

// Access Control
require_role('customer');
check_suspension($pdo);

if (!isset($_SESSION['pending_booking'])) {
    header("Location: dashboard.php");
    exit;
}

$pending = $_SESSION['pending_booking'];
$car_id = intval($pending['car_id']);
$user_id = intval($_SESSION['user_id']);

try {
    // Fetch user details
    $user_stmt = $pdo->prepare("SELECT fullname, id_number FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user_info = $user_stmt->fetch();

    // Fetch car details
    $car_stmt = $pdo->prepare("SELECT brand, model, charge_per_day FROM cars WHERE id = ?");
    $car_stmt->execute([$car_id]);
    $car_info = $car_stmt->fetch();

    if (!$user_info || !$car_info) {
        header("Location: dashboard.php?error=" . urlencode("Details not found."));
        exit;
    }

    $booking = [
        'brand' => $car_info['brand'],
        'model' => $car_info['model'],
        'charge_per_day' => $car_info['charge_per_day'],
        'fullname' => $user_info['fullname'],
        'id_number' => $user_info['id_number'],
        'phone' => $pending['phone'],
        'hire_days' => $pending['hire_days'],
        'total_amount' => $pending['total_amount'],
        'pickup_datetime' => $pending['pickup_datetime'],
        'return_datetime' => $pending['return_datetime'],
        'pickup_location' => $pending['pickup_location'],
        'return_location' => $pending['return_location']
    ];

    $deposit_amount = $booking['total_amount'] * 0.20;
} catch (\PDOException $e) {
    header("Location: dashboard.php?error=" . urlencode("Database error loading checkout."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Invoice - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: radial-gradient(circle at 10% 20%, rgba(243, 246, 253, 1) 0%, rgba(248, 250, 252, 1) 90%);">

    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="brand">
            <i class="fas fa-car-side"></i> Prestige Wheels
        </a>
        <div class="navbar-user">
            <span>Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
            <a href="dashboard.php?cancel=1" style="margin-left: 15px; color: var(--accent-color); font-weight: 600;"><i class="fas fa-times"></i> Cancel Booking</a>
        </div>
    </nav>

    <div class="main-container" style="display: flex; justify-content: center; align-items: center; min-height: 80vh; padding: 40px 0;">
        <div class="checkout-card" style="max-width: 550px; width: 100%;">
            <div class="checkout-header">
                <h2>Booking Invoice</h2>
                <p>Booking Reference: #PW-PENDING</p>
            </div>
            
            <div class="checkout-body">
                <h3 style="font-size: 1.1rem; border-bottom: 1.5px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px; color: var(--primary-color);">
                    <i class="fas fa-user"></i> Customer Information
                </h3>
                <div style="margin-bottom: 25px; line-height: 1.8; font-size: 0.95rem;">
                    <div>Full Name: <strong><?= htmlspecialchars($booking['fullname']) ?></strong></div>
                    <div>ID / Passport: <strong><?= htmlspecialchars($booking['id_number']) ?></strong></div>
                    <div>Contact Phone: <strong><?= htmlspecialchars($booking['phone']) ?></strong></div>
                </div>

                <h3 style="font-size: 1.1rem; border-bottom: 1.5px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px; color: var(--primary-color);">
                    <i class="fas fa-route"></i> Rental Details
                </h3>
                <div style="margin-bottom: 25px; line-height: 1.8; font-size: 0.95rem;">
                    <div>Pickup: <strong><?= date('M d, Y H:i', strtotime($booking['pickup_datetime'])) ?></strong> (<?= htmlspecialchars($booking['pickup_location']) ?>)</div>
                    <div>Return: <strong><?= date('M d, Y H:i', strtotime($booking['return_datetime'])) ?></strong> (<?= htmlspecialchars($booking['return_location']) ?>)</div>
                </div>

                <h3 style="font-size: 1.1rem; border-bottom: 1.5px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px; color: var(--primary-color);">
                    <i class="fas fa-file-invoice-dollar"></i> Charge Breakdown
                </h3>
                
                <div class="invoice-details">
                    <div class="invoice-row">
                        <span>Vehicle Model</span>
                        <span><?= htmlspecialchars($booking['brand'] . ' ' . $booking['model']) ?></span>
                    </div>
                    <div class="invoice-row">
                        <span>Daily Rate</span>
                        <span>KSh <?= number_format($booking['charge_per_day']) ?> / day</span>
                    </div>
                    <div class="invoice-row">
                        <span>Duration</span>
                        <span><?= htmlspecialchars($booking['hire_days']) ?> Days</span>
                    </div>
                    <div class="invoice-row">
                        <span>Total Due</span>
                        <span>KSh <?= number_format($booking['total_amount']) ?></span>
                    </div>
                </div>

                <!-- M-Pesa simulated payment box -->
                <div style="background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: var(--radius-md); padding: 25px; text-align: center; margin-top: 25px;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/1/15/M-PESA_LOGO-01.svg" alt="M-Pesa" class="mpesa-logo" style="max-height: 50px; margin-bottom: 15px;">
                    
                    <form action="../backend/mpesa_pay.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        
                        <!-- Select Payment Option -->
                        <div class="form-group" style="text-align: left; max-width: 320px; margin: 0 auto 20px auto;">
                            <label style="color: #166534; font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 8px;">Payment Option</label>
                            
                            <div style="display: flex; flex-direction: column; gap: 10px; background: white; padding: 15px; border-radius: var(--radius-sm); border: 1px solid #bbf7d0;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-dark); font-weight: 600; font-size: 0.95rem; margin: 0;">
                                    <input type="radio" name="payment_type" value="full" checked onclick="setPayAmount(<?= $booking['total_amount'] ?>)">
                                    Pay Full Amount (KSh <?= number_format($booking['total_amount']) ?>)
                                </label>
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-dark); font-weight: 600; font-size: 0.95rem; margin: 0;">
                                    <input type="radio" name="payment_type" value="deposit" onclick="setPayAmount(<?= $deposit_amount ?>)">
                                    Pay 20% Deposit (KSh <?= number_format($deposit_amount) ?>)
                                </label>
                            </div>
                        </div>

                        <div class="form-group" style="max-width: 320px; margin: 0 auto 20px auto; text-align: left;">
                            <label for="phone" style="color: #166534; font-size: 0.85rem; font-weight: 600;">M-Pesa Mobile Number</label>
                            <input type="tel" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($booking['phone']) ?>" pattern="^(07|01)[0-9]{8}$" required style="border-color: #86efac; background: #fff; padding: 10px;">
                        </div>

                        <button type="submit" id="payButton" class="btn" style="background: #22c55e; color: white; padding: 12px 30px; font-weight: 700; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); border: none; width: 100%; max-width: 320px; border-radius: var(--radius-md); cursor: pointer; font-size: 1rem;">
                            <i class="fas fa-mobile-alt"></i> Pay KSh <?= number_format($booking['total_amount']) ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date('Y') ?> Prestige Wheels Kenya. All rights reserved. Professional Car Hire Platform.</p>
    </footer>

    <script>
        function setPayAmount(amount) {
            const formatted = amount.toLocaleString();
            document.getElementById('payButton').innerHTML = '<i class="fas fa-mobile-alt"></i> Pay KSh ' + formatted;
        }
    </script>
</body>
</html>
