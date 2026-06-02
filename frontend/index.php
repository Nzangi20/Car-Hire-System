<?php
// frontend/index.php
require_once '../backend/security.php';
require_once '../backend/db.php';

// Fetch options for filters
try {
    $brands_stmt = $pdo->query("SELECT DISTINCT brand FROM cars");
    $all_brands = $brands_stmt->fetchAll(PDO::FETCH_COLUMN);

    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM cars");
    $all_categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (\PDOException $e) {
    $all_brands = [];
    $all_categories = [];
}

// Build Filter Query
$search = trim($_GET['search'] ?? '');
$min_price = trim($_GET['min_price'] ?? '');
$max_price = trim($_GET['max_price'] ?? '');
$category = trim($_GET['category'] ?? '');
$transmission = trim($_GET['transmission'] ?? '');
$status = trim($_GET['status'] ?? '');
$brand = trim($_GET['brand'] ?? '');

$sql = "SELECT * FROM cars WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (brand LIKE ? OR model LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($min_price !== '') {
    $sql .= " AND charge_per_day >= ?";
    $params[] = floatval($min_price);
}
if ($max_price !== '') {
    $sql .= " AND charge_per_day <= ?";
    $params[] = floatval($max_price);
}
if ($category !== '') {
    $sql .= " AND category = ?";
    $params[] = $category;
}
if ($transmission !== '') {
    $sql .= " AND transmission = ?";
    $params[] = $transmission;
}
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
}
if ($brand !== '') {
    $sql .= " AND brand = ?";
    $params[] = $brand;
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cars_list = $stmt->fetchAll();
} catch (\PDOException $e) {
    $cars_list = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestige Wheels - Premium Car Hire Kenya</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="brand">
            <i class="fas fa-car-side"></i> Prestige Wheels
        </a>
        <div class="navbar-user">
            <?php if (isset($_SESSION['username'])): ?>
                <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <a href="<?= in_array($_SESSION['role'] ?? 'customer', ['super_admin','manager','staff']) ? 'admin.php' : 'dashboard.php' ?>">Dashboard</a>
            <?php else: ?>
                <a href="login.php" style="background: transparent; border-color: rgba(255,255,255,0.3);"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php" style="background: var(--accent-color); border-color: var(--accent-color);"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="hero-content">
            <h1>Drive <span>Prestige</span>,<br>Experience Comfort</h1>
            <p>Rent luxury SUVs, premium sedans, and utility cars at affordable daily rates. Complete payment via M-Pesa and hit the road within minutes.</p>
            <div class="hero-buttons">
                <a href="#fleet" class="btn btn-primary"><i class="fas fa-car"></i> Explore Fleet</a>
                <?php if (!isset($_SESSION['username'])): ?>
                    <a href="register.php" class="btn btn-outline">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=600&q=80" alt="Toyota Prado Kenya">
        </div>
    </header>

    <!-- Search & Filter Form Section -->
    <section class="main-container" style="margin-top: -40px; position: relative; z-index: 10;">
        <div class="card" style="padding: 25px; box-shadow: 0 15px 30px rgba(0,0,0,0.1); background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
            <form action="index.php#fleet" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label for="search" style="font-size: 0.8rem; font-weight: 600;">Search Keyword</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Model, keyword..." value="<?= htmlspecialchars($search) ?>" style="padding: 10px;">
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <label for="brand" style="font-size: 0.8rem; font-weight: 600;">Manufacturer</label>
                    <select name="brand" id="brand" class="form-control" style="padding: 10px;">
                        <option value="">All Brands</option>
                        <?php foreach($all_brands as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>" <?= $brand === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label for="category" style="font-size: 0.8rem; font-weight: 600;">Vehicle Type</label>
                    <select name="category" id="category" class="form-control" style="padding: 10px;">
                        <option value="">All Types</option>
                        <?php foreach($all_categories as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label for="transmission" style="font-size: 0.8rem; font-weight: 600;">Transmission</label>
                    <select name="transmission" id="transmission" class="form-control" style="padding: 10px;">
                        <option value="">Any</option>
                        <option value="manual" <?= $transmission === 'manual' ? 'selected' : '' ?>>Manual</option>
                        <option value="automatic" <?= $transmission === 'automatic' ? 'selected' : '' ?>>Automatic</option>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label for="status" style="font-size: 0.8rem; font-weight: 600;">Availability</label>
                    <select name="status" id="status" class="form-control" style="padding: 10px;">
                        <option value="">Any Status</option>
                        <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available Now</option>
                        <option value="maintenance" <?= $status === 'maintenance' ? 'selected' : '' ?>>In Maintenance</option>
                    </select>
                </div>

                <div class="form-group" style="margin: 0; grid-column: span 1;">
                    <label for="max_price" style="font-size: 0.8rem; font-weight: 600;">Max Daily Price (KSh)</label>
                    <input type="number" name="max_price" id="max_price" class="form-control" placeholder="Price limit" value="<?= htmlspecialchars($max_price) ?>" style="padding: 10px;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 2; padding: 12px; font-size: 0.9rem; justify-content: center; height: 42px;">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="index.php#fleet" class="btn btn-secondary" style="flex: 1; padding: 12px; font-size: 0.9rem; display: inline-flex; align-items: center; justify-content: center; height: 42px; text-decoration: none;">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </section>

    <!-- Fleet Preview Section -->
    <section id="fleet" class="main-container" style="padding-top: 40px; padding-bottom: 60px;">
        <div class="section-header" style="text-align: center; margin-bottom: 40px;">
            <h2>Explore Our Premium Fleet</h2>
            <p>Select from our high-end, clean and fully managed vehicles for your rental.</p>
        </div>

        <div class="cars-grid">
            <?php if (empty($cars_list)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 50px 20px;">
                    <i class="fas fa-car-crash" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p style="color: var(--text-muted); font-size: 1.1rem;">No vehicles found matching your criteria. Try adjusting your filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($cars_list as $car): ?>
                    <div class="car-card">
                        <div class="car-image-container">
                            <img class="car-card-img" src="<?= htmlspecialchars($car['photo']) ?>" alt="<?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?>">
                            <span class="car-badge" style="
                                position: absolute; top: 15px; right: 15px; padding: 6px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;
                                <?= $car['status'] === 'available' ? 'background: #dcfce7; color: #15803d;' : 'background: #fee2e2; color: #b91c1c;' ?>
                            ">
                                <?= htmlspecialchars($car['status']) ?>
                            </span>
                        </div>
                        <div class="car-details" style="padding: 20px;">
                            <div class="car-brand-model">
                                <div class="brand" style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--primary-color); font-weight: 600;"><?= htmlspecialchars($car['brand']) ?></div>
                                <div class="model" style="font-size: 1.3rem; font-weight: 700; color: var(--text-dark); margin-top: 2px;"><?= htmlspecialchars($car['model']) ?> (<?= htmlspecialchars($car['year'] ?? 'N/A') ?>)</div>
                            </div>
                            
                            <!-- Specifications Row -->
                            <div style="display: flex; gap: 15px; margin-top: 15px; font-size: 0.85rem; color: var(--text-muted); border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                                <span><i class="fas fa-cogs"></i> <?= htmlspecialchars($car['transmission']) ?></span>
                                <span><i class="fas fa-gas-pump"></i> <?= htmlspecialchars($car['fuel_type'] ?? 'Petrol') ?></span>
                                <span><i class="fas fa-users"></i> <?= htmlspecialchars($car['capacity']) ?> seats</span>
                            </div>

                            <div class="car-pricing-info" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                                <div class="price-box">
                                    <span class="price" style="font-size: 1.4rem; font-weight: 800; color: var(--text-dark);">KSh <?= number_format($car['charge_per_day']) ?></span>
                                    <span class="period" style="font-size: 0.85rem; color: var(--text-muted);">/ day</span>
                                </div>
                                <div class="qty-box" style="font-size: 0.85rem; color: var(--text-muted);">
                                    Available: <strong style="color: var(--text-dark);"><?= htmlspecialchars($car['quantity']) ?></strong>
                                </div>
                            </div>

                            <div class="car-card-actions" style="margin-top: 20px; display: flex; gap: 10px;">
                                <a href="car_details.php?id=<?= $car['id'] ?>" class="btn btn-secondary" style="flex: 1; justify-content: center; padding: 10px;">
                                    <i class="fas fa-info-circle"></i> Details
                                </a>
                                <?php if (isset($_SESSION['username'])): ?>
                                    <?php if ($car['status'] === 'available'): ?>
                                        <a href="book_car.php?car_id=<?= $car['id'] ?>" class="btn btn-primary" style="flex: 1.5; justify-content: center; padding: 10px;">
                                            <i class="fas fa-car-side"></i> Book Now
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-primary" disabled style="flex: 1.5; justify-content: center; padding: 10px; background: #94a3b8; border-color: #94a3b8; cursor: not-allowed;">
                                            Unavailable
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary" style="flex: 1.5; justify-content: center; padding: 10px;">
                                        <i class="fas fa-key"></i> Login
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date('Y') ?> Prestige Wheels Kenya. All rights reserved. Professional Car Hire Platform.</p>
    </footer>

</body>
</html>
