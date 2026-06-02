<?php
// frontend/book_car.php
require_once '../backend/db.php';
require_once '../backend/security.php';

// Access Control
require_role('customer');
check_suspension($pdo);

$user_id = $_SESSION['user_id'];
$car_id = intval($_GET['car_id'] ?? 0);

try {
    // Check if user is verified
    $usr_stmt = $pdo->prepare("SELECT verification_status, phone FROM users WHERE id = ?");
    $usr_stmt->execute([$user_id]);
    $user = $usr_stmt->fetch();

    if ($user['verification_status'] !== 'verified') {
        header("Location: upload_docs.php?error=" . urlencode("You must upload verification documents and receive admin approval before booking a vehicle."));
        exit;
    }

    // Get car details
    $car_stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND status = 'available'");
    $car_stmt->execute([$car_id]);
    $car = $car_stmt->fetch();

    if (!$car) {
        header("Location: index.php?error=" . urlencode("The selected car is currently not available."));
        exit;
    }
} catch (\PDOException $e) {
    die("System error preparing booking.");
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?> - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: var(--bg-light);">

    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="brand">
            <i class="fas fa-car-side"></i> Prestige Wheels
        </a>
        <div class="navbar-user">
            <a href="car_details.php?id=<?= $car['id'] ?>" style="margin-right: 15px; color: white; text-decoration: none; font-weight: 500;">
                <i class="fas fa-arrow-left"></i> Back to Details
            </a>
            <span>Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
            <a href="logout.php" style="margin-left: 15px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="main-container" style="padding-top: 40px; padding-bottom: 60px; max-width: 800px;">
        
        <div class="card" style="padding: 30px;">
            <div style="display: flex; gap: 20px; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 25px;">
                <img src="<?= htmlspecialchars($car['photo']) ?>" alt="Car" style="width: 100px; height: 70px; object-fit: cover; border-radius: var(--radius-sm);">
                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--primary-color); font-weight: 700;"><?= htmlspecialchars($car['category']) ?></span>
                    <h2 style="color: var(--text-dark); font-weight: 800;"><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></h2>
                    <span style="font-size: 0.9rem; color: var(--text-muted);">Rate: <strong>KSh <?= number_format($car['charge_per_day']) ?> / day</strong></span>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="../backend/hire_car.php" method="POST" id="bookingForm">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="pickup_datetime">Pickup Date & Time</label>
                        <input type="datetime-local" name="pickup_datetime" id="pickup_datetime" class="form-control" required style="padding: 10px;" min="<?= date('Y-m-d\TH:i') ?>">
                    </div>

                    <div class="form-group">
                        <label for="return_datetime">Return Date & Time</label>
                        <input type="datetime-local" name="return_datetime" id="return_datetime" class="form-control" required style="padding: 10px;" min="<?= date('Y-m-d\TH:i') ?>">
                    </div>

                    <div class="form-group">
                        <label for="pickup_location">Pickup Location</label>
                        <select name="pickup_location" id="pickup_location" class="form-control" style="padding: 10px;" required>
                            <option value="Jomo Kenyatta International Airport (JKIA)">Jomo Kenyatta Intl Airport (JKIA)</option>
                            <option value="Nairobi CBD Office">Nairobi CBD Main Office</option>
                            <option value="Westlands Branch">Westlands Branch Office</option>
                            <option value="Mombasa Airport Station">Mombasa Airport Station</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="return_location">Return Location</label>
                        <select name="return_location" id="return_location" class="form-control" style="padding: 10px;" required>
                            <option value="Jomo Kenyatta International Airport (JKIA)">Jomo Kenyatta Intl Airport (JKIA)</option>
                            <option value="Nairobi CBD Office">Nairobi CBD Main Office</option>
                            <option value="Westlands Branch">Westlands Branch Office</option>
                            <option value="Mombasa Airport Station">Mombasa Airport Station</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="phone">Phone Number (For M-Pesa STK push simulation)</label>
                    <input type="tel" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="0712345678" pattern="^(07|01)[0-9]{8}$" required style="padding: 10px;">
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="special_requests">Special Requests (Optional)</label>
                    <textarea name="special_requests" id="special_requests" class="form-control" placeholder="Child seats, GPS navigator preference, etc..." rows="3" style="padding: 10px;"></textarea>
                </div>

                <!-- Cost Calculator Box -->
                <div id="calculatorBox" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 20px; margin-bottom: 30px; display: none;">
                    <h3 style="color: var(--text-dark); margin-bottom: 12px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-calculator" style="color: var(--primary-color);"></i> Invoice Cost Estimation
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.95rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Rental Rate:</span>
                            <strong style="color: var(--text-dark);">KSh <?= number_format($car['charge_per_day']) ?> / day</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Total Duration:</span>
                            <strong id="calcDuration" style="color: var(--text-dark);">0 days</strong>
                        </div>
                        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 8px 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.15rem;">
                            <strong style="color: var(--text-dark);">Estimated Cost:</strong>
                            <strong id="calcTotal" style="color: var(--primary-color);">KSh 0</strong>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; justify-content: center; font-size: 1.1rem;">
                    <i class="fas fa-chevron-right"></i> Proceed to Checkout
                </button>
            </form>

        </div>

    </div>

    <!-- JS Calculator -->
    <script>
        const dailyRate = <?= floatval($car['charge_per_day']) ?>;
        const pickupInput = document.getElementById('pickup_datetime');
        const returnInput = document.getElementById('return_datetime');
        const calcBox = document.getElementById('calculatorBox');
        const calcDuration = document.getElementById('calcDuration');
        const calcTotal = document.getElementById('calcTotal');

        function updateCost() {
            const pickupVal = pickupInput.value;
            const returnVal = returnInput.value;

            if (pickupVal && returnVal) {
                const pickupDate = new Date(pickupVal);
                const returnDate = new Date(returnVal);

                if (returnDate > pickupDate) {
                    // Calculate difference in milliseconds
                    const diffTime = Math.abs(returnDate - pickupDate);
                    // Convert to fractional days, rounded up to whole day
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    const totalCost = diffDays * dailyRate;

                    calcDuration.innerText = diffDays + (diffDays === 1 ? ' day' : ' days');
                    calcTotal.innerText = 'KSh ' + totalCost.toLocaleString();
                    calcBox.style.display = 'block';
                } else {
                    calcBox.style.display = 'none';
                }
            } else {
                calcBox.style.display = 'none';
            }
        }

        pickupInput.addEventListener('change', updateCost);
        returnInput.addEventListener('change', updateCost);
    </script>
</body>
</html>
