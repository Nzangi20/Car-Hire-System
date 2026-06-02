<?php
// frontend/admin.php
require_once '../backend/db.php';
require_once '../backend/security.php';

// Access Control: Admins/Managers/Staff only
require_role(['super_admin', 'manager', 'staff']);
check_suspension($pdo);

$user = $_SESSION['username'];
$role = $_SESSION['role'];
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
$tab = $_GET['tab'] ?? 'analytics';

try {
    // ----------------------------------------------------
    // ANALYTICS DATA
    // ----------------------------------------------------
    // Total cars (sum of quantity)
    $stmt = $pdo->query("SELECT SUM(quantity) as total_qty FROM cars");
    $total_cars = (int)($stmt->fetch()['total_qty'] ?? 0);

    // Active rentals (bookings paid/active and not returned)
    $stmt = $pdo->query("SELECT COUNT(*) as active_cnt FROM bookings WHERE returned = 0 AND status IN ('paid', 'active')");
    $active_rentals = (int)($stmt->fetch()['active_cnt'] ?? 0);

    // Total Earnings
    $stmt = $pdo->query("SELECT SUM(amount) as total_earn FROM payments WHERE payment_status = 'completed'");
    $total_earnings = floatval($stmt->fetch()['total_earn'] ?? 0);

    // Late Return Inspections
    $stmt = $pdo->query("SELECT COUNT(*) FROM inspections WHERE late_charges > 0");
    $late_returns = (int)$stmt->fetchColumn();

    // Damage Reports Count
    $stmt = $pdo->query("SELECT COUNT(*) FROM inspections WHERE inspection_status = 'damaged'");
    $damaged_cars = (int)$stmt->fetchColumn();

    // Verification Queue count
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE verification_status = 'pending'");
    $pending_verifications = (int)$stmt->fetchColumn();

    // Global Pending Return Inspections count
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE returned = 0");
    $pending_inspections_count = (int)$stmt->fetchColumn();

    // Utilization Rate
    $utilization_rate = ($total_cars > 0) ? round(($active_rentals / $total_cars) * 100, 1) : 0;

    // ----------------------------------------------------
    // TAB SPECIFIC QUERIES
    // ----------------------------------------------------
    if ($tab === 'fleet') {
        $stmt = $pdo->query("SELECT * FROM cars ORDER BY id DESC");
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    
    elseif ($tab === 'verification') {
        // Query all customers with their uploaded documents, sorted by action required
        $stmt = $pdo->query("
            SELECT u.*, 
                   (SELECT file_path FROM user_documents WHERE user_id = u.id AND document_type = 'license' LIMIT 1) as license_file,
                   (SELECT file_path FROM user_documents WHERE user_id = u.id AND document_type = 'id_passport' LIMIT 1) as id_file
            FROM users u 
            WHERE u.role = 'customer'
            ORDER BY FIELD(u.verification_status, 'pending', 'unverified', 'rejected', 'verified') ASC, u.created_at DESC
        ");
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    
    elseif ($tab === 'users') {
        // Query all registered users
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    
    elseif ($tab === 'tracker') {
        // Query all fleet vehicles for GPS tracking dashboard
        $stmt = $pdo->query("SELECT * FROM cars ORDER BY brand ASC, model ASC");
        $tracker_cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    
    elseif ($tab === 'inspections') {
        // Bookings that are completed/active but not returned
        $stmt = $pdo->query("
            SELECT b.*, u.username, u.fullname, c.brand, c.model, c.photo 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            JOIN cars c ON b.car_id = c.id 
            WHERE b.returned = 0 
            ORDER BY b.return_datetime ASC
        ");
        $pending_inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Completed Inspections history
        $stmt = $pdo->query("
            SELECT i.*, b.pickup_datetime, b.return_datetime, u.fullname, c.brand, c.model, u2.username as inspector_name 
            FROM inspections i 
            JOIN bookings b ON i.booking_id = b.id 
            JOIN users u ON b.user_id = u.id 
            JOIN cars c ON b.car_id = c.id 
            JOIN users u2 ON i.inspector_id = u2.id
            ORDER BY i.created_at DESC LIMIT 15
        ");
        $inspections_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    
    elseif ($tab === 'payments') {
        // Fetch transaction tracking list
        $stmt = $pdo->query("
            SELECT p.*, u.username, u.fullname, c.brand, c.model, b.total_amount as booking_total 
            FROM payments p 
            JOIN users u ON p.user_id = u.id 
            JOIN bookings b ON p.booking_id = b.id 
            JOIN cars c ON b.car_id = c.id 
            ORDER BY p.payment_date DESC
        ");
        $payments_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    
    elseif ($tab === 'logs') {
        // Fetch system audit logs
        $stmt = $pdo->query("
            SELECT l.*, u.username, u.role 
            FROM activity_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            ORDER BY l.created_at DESC LIMIT 100
        ");
        $logs_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (\PDOException $e) {
    die("Database load error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .admin-sidebar {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: white;
            padding: 20px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            align-self: start;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all var(--transition-speed);
        }
        .sidebar-link:hover, .sidebar-link.active {
            background: #f1f5f9;
            color: var(--primary-light);
        }
        .sidebar-link.active {
            border-left: 4px solid var(--primary-light);
            background: #f8fafc;
        }
        .admin-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .inspection-form-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 20px;
            margin-top: 15px;
        }
        @media (max-width: 992px) {
            .admin-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body style="background: var(--bg-light);">

    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="brand">
            <i class="fas fa-car-side"></i> Prestige Wheels
        </a>
        <div class="navbar-user">
            <span>Logged in as: <strong><?= htmlspecialchars($user) ?></strong> <span style="font-size: 0.75rem; background: var(--accent-color); color: #fff; padding: 2px 8px; border-radius: 20px; font-weight: 700; margin-left: 5px; text-transform: uppercase;"><?= $role ?></span></span>
            <a href="logout.php" style="margin-left: 15px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container" style="padding-top: 45px; padding-bottom: 60px;">

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 25px;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="margin-bottom: 25px;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; border-radius: var(--radius-lg); padding: 30px; margin-bottom: 30px;">
            <h1 style="margin: 0; font-size: 1.8rem; font-family: 'Montserrat', sans-serif;">Prestige Control Console</h1>
            <p style="margin: 5px 0 0 0; color: #94a3b8; font-size: 0.95rem;">Administrative tools for verifying accounts, inspecting fleet returns, analyzing performance and tracking payments.</p>
        </div>

        <div class="admin-layout">
            
            <!-- Sidebar Navigation Links -->
            <div class="admin-sidebar">
                <a href="admin.php?tab=analytics" class="sidebar-link <?= $tab === 'analytics' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> Analytics Overview
                </a>
                <a href="admin.php?tab=users" class="sidebar-link <?= $tab === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Users Management
                </a>
                <a href="admin.php?tab=fleet" class="sidebar-link <?= $tab === 'fleet' ? 'active' : '' ?>">
                    <i class="fas fa-car"></i> Fleet Management
                </a>
                <a href="admin.php?tab=tracker" class="sidebar-link <?= $tab === 'tracker' ? 'active' : '' ?>">
                    <i class="fas fa-map-marker-alt"></i> GPS Fleet Tracker
                </a>
                <a href="admin.php?tab=verification" class="sidebar-link <?= $tab === 'verification' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i> Verification Queue
                    <?php if ($pending_verifications > 0): ?>
                        <span style="background: #ef4444; color: white; font-size: 0.7rem; padding: 1px 6px; border-radius: 50px; margin-left: auto;"><?= $pending_verifications ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin.php?tab=inspections" class="sidebar-link <?= $tab === 'inspections' ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-check"></i> Inspections & Returns
                    <?php if ($pending_inspections_count > 0): ?>
                        <span style="background: #eab308; color: white; font-size: 0.7rem; padding: 1px 6px; border-radius: 50px; margin-left: auto;"><?= $pending_inspections_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin.php?tab=payments" class="sidebar-link <?= $tab === 'payments' ? 'active' : '' ?>">
                    <i class="fas fa-wallet"></i> Payment Tracking
                </a>
                <a href="admin.php?tab=logs" class="sidebar-link <?= $tab === 'logs' ? 'active' : '' ?>">
                    <i class="fas fa-list-alt"></i> Activity Logs
                </a>
            </div>

            <!-- Content Area -->
            <div class="admin-content">

                <!-- ------------------------------------------------------------------------ -->
                <!-- ANALYTICS TAB -->
                <!-- ------------------------------------------------------------------------ -->
                <?php if ($tab === 'analytics'): ?>
                    <div class="analytics-grid">
                        <div class="stat-card">
                            <div class="stat-info">
                                <div class="stat-num">KSh <?= number_format($total_earnings) ?></div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                            <div class="stat-icon blue"><i class="fas fa-wallet"></i></div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-info">
                                <div class="stat-num"><?= $total_cars ?></div>
                                <div class="stat-label">Fleet Capacity</div>
                            </div>
                            <div class="stat-icon blue"><i class="fas fa-car"></i></div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-info">
                                <div class="stat-num"><?= $active_rentals ?></div>
                                <div class="stat-label">Active Rentals</div>
                            </div>
                            <div class="stat-icon orange"><i class="fas fa-key"></i></div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-info">
                                <div class="stat-num"><?= $utilization_rate ?>%</div>
                                <div class="stat-label">Utilization Rate</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-chart-pie"></i></div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 10px;">
                        <div class="card" style="padding: 25px;">
                            <h3 style="color: var(--text-dark); margin-bottom: 20px;"><i class="fas fa-info-circle"></i> Service Health Insights</h3>
                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                                    <span style="color: var(--text-muted);">Damaged Returns Logged:</span>
                                    <strong style="color: #b91c1c;"><i class="fas fa-tools"></i> <?= $damaged_cars ?> vehicles</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                                    <span style="color: var(--text-muted);">Late Returns Registered:</span>
                                    <strong style="color: #a16207;"><i class="fas fa-clock"></i> <?= $late_returns ?> times</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding-bottom: 10px;">
                                    <span style="color: var(--text-muted);">Awaiting License Approval:</span>
                                    <strong style="color: #2563eb;"><i class="fas fa-id-card-alt"></i> <?= $pending_verifications ?> queues</strong>
                                </div>
                            </div>
                        </div>

                        <div class="card" style="padding: 25px; display: flex; align-items: center; justify-content: center; text-align: center; background: #f8fafc;">
                            <div>
                                <i class="fas fa-tachometer-alt" style="font-size: 3rem; color: var(--primary-light); margin-bottom: 15px;"></i>
                                <h4 style="margin: 0 0 5px 0; color: var(--text-dark);">Need Quick Support?</h4>
                                <p style="color: var(--text-muted); font-size: 0.85rem; max-width: 250px; margin: 0 auto;">Check user logs or verify transactions for security disputes.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>


                <!-- ------------------------------------------------------------------------ -->
                <!-- FLEET TAB -->
                <!-- ------------------------------------------------------------------------ -->
                <?php if ($tab === 'fleet'): ?>
                    <div class="panel-card" style="margin: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2><i class="fas fa-list"></i> Managed Fleet</h2>
                            <a href="#addCarSection" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Vehicle</a>
                        </div>

                        <div class="table-responsive">
                            <table class="custom-table" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Brand & Model</th>
                                        <th>Plate</th>
                                        <th>Category</th>
                                        <th>Rate</th>
                                        <th>Units</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cars as $car): ?>
                                        <tr>
                                            <td>
                                                <img src="<?= htmlspecialchars($car['photo']) ?>" alt="car" style="width: 60px; height: 40px; object-fit: cover; border-radius: var(--radius-sm);">
                                            </td>
                                            <td><strong><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></strong> (<?= htmlspecialchars($car['year'] ?? 'N/A') ?>)</td>
                                            <td><code style="background: #f1f5f9; padding: 3px 6px; border-radius: 4px;"><?= htmlspecialchars($car['registration_number'] ?? 'N/A') ?></code></td>
                                            <td><?= htmlspecialchars($car['category'] ?? 'Sedan') ?></td>
                                            <td><strong>KSh <?= number_format($car['charge_per_day']) ?></strong></td>
                                            <td><?= htmlspecialchars($car['quantity']) ?> Units</td>
                                            <td>
                                                <span class="status-badge" style="
                                                    padding: 3px 8px; font-size: 0.75rem;
                                                    <?= $car['status'] === 'available' ? 'background: #dcfce7; color: #15803d;' : 'background: #fee2e2; color: #b91c1c;' ?>
                                                ">
                                                    <?= htmlspecialchars($car['status']) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <div style="display: inline-flex; gap: 8px;">
                                                    <a href="edit_car.php?id=<?= $car['id'] ?>" class="btn btn-warning btn-sm" style="font-size: 0.75rem; padding: 5px 10px;">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="../backend/delete_car.php?id=<?= $car['id'] ?>" class="btn btn-danger btn-sm" style="font-size: 0.75rem; padding: 5px 10px;" onclick="return confirm('Are you sure you want to remove this car?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Add Vehicle Section -->
                    <div id="addCarSection" class="panel-card" style="margin: 0;">
                        <h2><i class="fas fa-plus-circle"></i> Add Vehicle to Fleet</h2>
                        <form action="../backend/add_car.php" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

                            <div class="form-group">
                                <label for="brand">Brand / Make</label>
                                <input type="text" name="brand" class="form-control" placeholder="e.g. Toyota" required>
                            </div>

                            <div class="form-group">
                                <label for="model">Model</label>
                                <input type="text" name="model" class="form-control" placeholder="e.g. Land Cruiser Prado" required>
                            </div>

                            <div class="form-group">
                                <label for="registration_number">Registration Number (Plate)</label>
                                <input type="text" name="registration_number" class="form-control" placeholder="e.g. KDG 123A" required>
                            </div>

                            <div class="form-group">
                                <label for="year">Year of Manufacture</label>
                                <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="charge_per_day">Charge Per Day (KSh)</label>
                                <input type="number" name="charge_per_day" class="form-control" placeholder="e.g. 10000" min="1" step="0.01" required>
                            </div>

                            <div class="form-group">
                                <label for="quantity">Quantity (Total Units)</label>
                                <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                            </div>

                            <div class="form-group">
                                <label for="transmission">Transmission</label>
                                <select name="transmission" class="form-control">
                                    <option value="automatic">Automatic</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="fuel_type">Fuel Type</label>
                                <input type="text" name="fuel_type" class="form-control" value="Petrol" required>
                            </div>

                            <div class="form-group">
                                <label for="capacity">Seating Capacity</label>
                                <input type="number" name="capacity" class="form-control" value="5" min="2" required>
                            </div>

                            <div class="form-group">
                                <label for="category">Category</label>
                                <select name="category" class="form-control">
                                    <option value="SUV">SUV</option>
                                    <option value="Sedan">Sedan</option>
                                    <option value="Luxury">Luxury</option>
                                    <option value="Mini-Bus">Mini-Bus / Van</option>
                                </select>
                            </div>

                            <div class="form-group" style="grid-column: span 2;">
                                <label for="description">Vehicle Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Enter specifications, options..."></textarea>
                            </div>

                            <div class="form-group" style="grid-column: span 2;">
                                <label for="photo">Main Vehicle Photo</label>
                                <input type="file" name="photo" class="form-control" accept="image/*" required style="padding: 8px;">
                            </div>

                            <div class="form-group" style="grid-column: span 2;">
                                <label for="gallery_photos">Gallery Photos (Multiple files allowed)</label>
                                <input type="file" name="gallery_photos[]" class="form-control" accept="image/*" multiple style="padding: 8px;">
                            </div>

                            <button type="submit" class="btn btn-primary" style="grid-column: span 2; padding: 12px; justify-content: center;">
                                <i class="fas fa-check"></i> Add Vehicle Specs & Gallery
                            </button>
                        </form>
                    </div>
                <?php endif; ?>


                <!-- ------------------------------------------------------------------------ -->
                <!-- CUSTOMER VERIFICATION TAB -->
                <!-- ------------------------------------------------------------------------ -->
                <?php if ($tab === 'verification'): ?>
                    <div class="panel-card" style="margin: 0;">
                        <h2><i class="fas fa-user-shield"></i> Document Verification Queue</h2>
                        
                        <div class="table-responsive">
                            <table class="custom-table" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Name & Email</th>
                                        <th>ID Number</th>
                                        <th>License Number</th>
                                        <th>Documents</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">No customers registered.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($customers as $c): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($c['profile_picture'])): ?>
                                                        <img src="<?= htmlspecialchars($c['profile_picture']) ?>" alt="user" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div style="width: 45px; height: 45px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user" style="color: #94a3b8;"></i></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($c['fullname']) ?></strong><br>
                                                    <span style="font-size: 0.8rem; color: var(--text-muted);">@<?= htmlspecialchars($c['username']) ?> | <?= htmlspecialchars($c['email']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($c['id_number']) ?></td>
                                                <td><?= htmlspecialchars($c['driving_license'] ?? 'N/A') ?></td>
                                                <td>
                                                    <div style="display: flex; gap: 8px;">
                                                        <?php if (!empty($c['license_file'])): ?>
                                                            <a href="<?= htmlspecialchars($c['license_file']) ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 4px 8px;" title="View License"><i class="fas fa-id-card"></i> License</a>
                                                        <?php else: ?>
                                                            <span style="font-size: 0.75rem; color: var(--text-muted); italic;">No License</span>
                                                        <?php endif; ?>

                                                        <?php if (!empty($c['id_file'])): ?>
                                                            <a href="<?= htmlspecialchars($c['id_file']) ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 4px 8px;" title="View ID / Passport"><i class="fas fa-passport"></i> ID File</a>
                                                        <?php else: ?>
                                                            <span style="font-size: 0.75rem; color: var(--text-muted); italic;">No ID</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge" style="
                                                        padding: 3px 8px; font-size: 0.75rem; text-transform: uppercase;
                                                        <?php
                                                            if ($c['verification_status'] === 'verified') echo 'background: #dcfce7; color: #15803d;';
                                                            elseif ($c['verification_status'] === 'pending') echo 'background: #fef9c3; color: #a16207;';
                                                            else echo 'background: #fee2e2; color: #b91c1c;';
                                                        ?>
                                                    ">
                                                        <?= htmlspecialchars($c['verification_status']) ?>
                                                    </span>
                                                </td>
                                                <td style="text-align: right;">
                                                    <?php if ($c['verification_status'] !== 'verified'): ?>
                                                        <div style="display: inline-flex; gap: 6px;">
                                                            <form action="../backend/verify_customer.php" method="POST" style="margin: 0;">
                                                                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                                <input type="hidden" name="user_id" value="<?= $c['id'] ?>">
                                                                <input type="hidden" name="action" value="approve">
                                                                <button type="submit" class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 6px 12px; background: #22c55e; border-color: #22c55e;"><i class="fas fa-check"></i> Approve</button>
                                                            </form>

                                                            <form action="../backend/verify_customer.php" method="POST" style="margin: 0;">
                                                                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                                <input type="hidden" name="user_id" value="<?= $c['id'] ?>">
                                                                <input type="hidden" name="action" value="reject">
                                                                <button type="submit" class="btn btn-danger btn-sm" style="font-size: 0.75rem; padding: 6px 12px;"><i class="fas fa-times"></i> Decline</button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span style="font-size: 0.8rem; color: #16a34a; font-style: normal; font-weight: 600;"><i class="fas fa-user-check"></i> Verified</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>


                <!-- ------------------------------------------------------------------------ -->
                <!-- INSPECTIONS & RETURNS TAB -->
                <!-- ------------------------------------------------------------------------ -->
                <?php if ($tab === 'inspections'): ?>
                    <div class="panel-card" style="margin: 0;">
                        <h2><i class="fas fa-clipboard-list"></i> Pending Return Inspections</h2>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: -10px; margin-bottom: 20px;">Logged-back rentals awaiting safety and condition inspection.</p>
                        
                        <div class="table-responsive">
                            <table class="custom-table" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Ref</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Expected Return</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pending_inspections)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">No active bookings await return inspection.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pending_inspections as $pi): ?>
                                            <tr>
                                                <td>#PW-<?= str_pad($pi['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                                <td><strong><?= htmlspecialchars($pi['fullname']) ?></strong></td>
                                                <td><?= htmlspecialchars($pi['brand'] . ' ' . $pi['model']) ?></td>
                                                <td><?= date('M d, H:i', strtotime($pi['return_datetime'])) ?></td>
                                                <td><?= htmlspecialchars($pi['phone']) ?></td>
                                                <td>
                                                    <span class="status-badge" style="
                                                        padding: 3px 8px; font-size: 0.75rem;
                                                        <?= $pi['status'] === 'completed' ? 'background: #fef9c3; color: #a16207;' : 'background: #dcfce7; color: #15803d;' ?>
                                                    ">
                                                        <?= htmlspecialchars($pi['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 6px 12px;" onclick="openInspectionForm(<?= $pi['id'] ?>, '<?= htmlspecialchars($pi['brand'] . ' ' . $pi['model']) ?>', '<?= htmlspecialchars($pi['fullname']) ?>')">
                                                        <i class="fas fa-search-plus"></i> Run Inspection
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Inspection Execution Form Box (Dynamically shown on click) -->
                    <div id="inspectionFormContainer" class="card inspection-form-box" style="display: none;">
                        <h3 style="color: var(--text-dark); margin: 0 0 15px 0;"><i class="fas fa-file-signature"></i> Record Vehicle Return & Inspection</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Complete conditions checklist, penalties, and late returns charges.</p>
                        
                        <form action="../backend/submit_inspection.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                            <input type="hidden" name="booking_id" id="inspectBookingId">

                            <div style="background: white; border: 1px solid #e2e8f0; padding: 15px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
                                <div>Booking ID: <strong id="inspectLabelId">0</strong></div>
                                <div>Customer: <strong id="inspectLabelCustomer">N/A</strong></div>
                                <div>Vehicle: <strong id="inspectLabelVehicle">N/A</strong></div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label for="inspection_status">Inspection Condition Status</label>
                                    <select name="inspection_status" id="inspection_status" class="form-control" style="padding: 10px;" required>
                                        <option value="clean">Clean Return (No damages / pristine)</option>
                                        <option value="damaged">Damaged Return (Requires maintenance/bodywork)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="penalties_amount">Damages Penalty (KSh)</label>
                                    <input type="number" name="penalties_amount" id="penalties_amount" class="form-control" value="0" min="0" step="0.01" style="padding: 10px;">
                                </div>

                                <div class="form-group">
                                    <label for="late_charges">Late Return Penalty Charges (KSh)</label>
                                    <input type="number" name="late_charges" id="late_charges" class="form-control" value="0" min="0" step="0.01" style="padding: 10px;">
                                </div>

                                <div class="form-group" style="grid-column: span 2;">
                                    <label for="damages_description">Condition / Damages Description (Optional)</label>
                                    <textarea name="damages_description" id="damages_description" class="form-control" placeholder="Write description of vehicle state, fuel levels, body damages..." rows="3" style="padding: 10px;"></textarea>
                                </div>
                            </div>

                            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('inspectionFormContainer').style.display='none'">Cancel</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-check-double"></i> Save Return & Complete</button>
                            </div>
                        </form>
                    </div>

                    <!-- Inspection History Panel -->
                    <div class="panel-card" style="margin-top: 30px; margin-bottom: 0;">
                        <h2><i class="fas fa-history"></i> Recent Completed Inspections</h2>
                        <div class="table-responsive">
                            <table class="custom-table" style="font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Vehicle</th>
                                        <th>Customer</th>
                                        <th>Inspector</th>
                                        <th>Condition</th>
                                        <th>Late Fee</th>
                                        <th>Damage Fee</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($inspections_history)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 20px; color: var(--text-muted);">No historical inspection records logged.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($inspections_history as $ih): ?>
                                            <tr>
                                                <td><?= date('M d, Y', strtotime($ih['created_at'])) ?></td>
                                                <td><strong><?= htmlspecialchars($ih['brand'] . ' ' . $ih['model']) ?></strong></td>
                                                <td><?= htmlspecialchars($ih['fullname']) ?></td>
                                                <td><?= htmlspecialchars($ih['inspector_name']) ?></td>
                                                <td>
                                                    <span class="status-badge" style="
                                                        padding: 3px 8px; font-size: 0.75rem;
                                                        <?= $ih['inspection_status'] === 'clean' ? 'background: #dcfce7; color: #15803d;' : 'background: #fee2e2; color: #b91c1c;' ?>
                                                    ">
                                                        <?= htmlspecialchars($ih['inspection_status']) ?>
                                                    </span>
                                                </td>
                                                <td>KSh <?= number_format($ih['late_charges']) ?></td>
                                                <td>KSh <?= number_format($ih['penalties_amount']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>


                <!-- ------------------------------------------------------------------------ -->
                <!-- PAYMENTS TAB -->
                <!-- ------------------------------------------------------------------------ -->
                <?php if ($tab === 'payments'): ?>
                    <div class="panel-card" style="margin: 0;">
                        <h2><i class="fas fa-wallet"></i> Payment Transactions Tracker</h2>
                        
                        <div class="table-responsive">
                            <table class="custom-table" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Billed Customer</th>
                                        <th>Vehicle Model</th>
                                        <th>Total Invoice</th>
                                        <th>Paid Amount</th>
                                        <th>Payment Type</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payments_list)): ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center; padding: 30px; color: var(--text-muted);">No payment records registered.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($payments_list as $pl): ?>
                                            <tr>
                                                <td><code style="background: #e2e8f0; padding: 3px 6px; border-radius: 4px;"><?= htmlspecialchars($pl['transaction_id']) ?></code></td>
                                                <td><strong><?= htmlspecialchars($pl['fullname']) ?></strong></td>
                                                <td><?= htmlspecialchars($pl['brand'] . ' ' . $pl['model']) ?></td>
                                                <td>KSh <?= number_format($pl['booking_total']) ?></td>
                                                <td style="font-weight: 700; color: #15803d;">KSh <?= number_format($pl['amount']) ?></td>
                                                <td style="text-transform: capitalize;"><?= htmlspecialchars($pl['payment_type']) ?></td>
                                                <td><?= date('M d, Y H:i', strtotime($pl['payment_date'])) ?></td>
                                                <td>
                                                    <span class="status-badge" style="
                                                        padding: 3px 8px; font-size: 0.75rem; text-transform: uppercase;
                                                        <?php
                                                            if ($pl['payment_status'] === 'completed') echo 'background: #dcfce7; color: #15803d;';
                                                            elseif ($pl['payment_status'] === 'pending') echo 'background: #fef9c3; color: #a16207;';
                                                            else echo 'background: #fee2e2; color: #b91c1c;';
                                                        ?>
                                                    ">
                                                        <?= htmlspecialchars($pl['payment_status']) ?>
                                                    </span>
                                                </td>
                                                <td style="text-align: right;">
                                                    <?php if ($pl['payment_status'] === 'completed' && in_array($role, ['super_admin', 'manager'])): ?>
                                                        <form action="../backend/process_refund.php" method="POST" style="margin: 0; display: inline-block;" onsubmit="return confirm('Simulate refunding KSh <?= number_format($pl['amount']) ?> back to the customer?')">
                                                            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                            <input type="hidden" name="payment_id" value="<?= $pl['id'] ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" style="font-size: 0.75rem; padding: 5px 10px;"><i class="fas fa-undo-alt"></i> Refund</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">Closed</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>


                <!-- ------------------------------------------------------------------------ -->
                <!-- USERS MANAGEMENT TAB -->
                <!-- ------------------------------------------------------------------------ -->
                <?php if ($tab === 'users'): ?>
                    <div class="panel-card" style="margin: 0;">
                        <h2><i class="fas fa-users"></i> Users Management Directory</h2>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: -10px; margin-bottom: 20px;">Review registered system users, configure security clearance roles, and toggle account suspensions.</p>
                        
                        <div class="table-responsive">
                            <table class="custom-table" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Name / Contact</th>
                                        <th>Username</th>
                                        <th>Security Role</th>
                                        <th>Verification Status</th>
                                        <th>Account Standing</th>
                                        <th style="text-align: right;">Action Control</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($all_users)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">No registered users found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($all_users as $u_item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($u_item['fullname']) ?></strong><br>
                                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($u_item['email']) ?> | <?= htmlspecialchars($u_item['phone'] ?? 'No Phone') ?></span>
                                                </td>
                                                <td><code>@<?= htmlspecialchars($u_item['username']) ?></code></td>
                                                <td>
                                                    <form action="../backend/change_role.php" method="POST" style="margin: 0; display: inline-flex; align-items: center; gap: 8px;">
                                                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                        <input type="hidden" name="user_id" value="<?= $u_item['id'] ?>">
                                                        
                                                        <select name="role" class="form-control" style="padding: 4px 8px; font-size: 0.8rem; height: auto;" onchange="this.form.submit()" <?= intval($u_item['id']) === intval($_SESSION['user_id']) ? 'disabled' : '' ?>>
                                                            <option value="customer" <?= $u_item['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                                            <option value="staff" <?= $u_item['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                                            <option value="manager" <?= $u_item['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                                                            <option value="super_admin" <?= $u_item['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                                        </select>
                                                    </form>
                                                </td>
                                                <td>
                                                    <span class="status-badge" style="
                                                        padding: 3px 8px; font-size: 0.75rem; text-transform: uppercase;
                                                        <?php
                                                            if ($u_item['verification_status'] === 'verified') echo 'background: #dcfce7; color: #15803d;';
                                                            elseif ($u_item['verification_status'] === 'pending') echo 'background: #fef9c3; color: #a16207;';
                                                            elseif ($u_item['verification_status'] === 'rejected') echo 'background: #fee2e2; color: #b91c1c;';
                                                            else echo 'background: #f1f5f9; color: #64748b;';
                                                        ?>
                                                    ">
                                                        <?= htmlspecialchars($u_item['verification_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge" style="
                                                        padding: 3px 8px; font-size: 0.75rem; text-transform: uppercase;
                                                        <?= $u_item['is_suspended'] == 1 ? 'background: #fee2e2; color: #b91c1c;' : 'background: #dcfce7; color: #15803d;' ?>
                                                    ">
                                                        <?= $u_item['is_suspended'] == 1 ? 'Suspended' : 'Active' ?>
                                                    </span>
                                                </td>
                                                <td style="text-align: right;">
                                                    <?php if (intval($u_item['id']) !== intval($_SESSION['user_id'])): ?>
                                                        <form action="../backend/toggle_suspension.php" method="POST" style="margin: 0; display: inline-block;">
                                                            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                            <input type="hidden" name="user_id" value="<?= $u_item['id'] ?>">
                                                            <button type="submit" class="btn btn-sm <?= $u_item['is_suspended'] == 1 ? 'btn-primary' : 'btn-danger' ?>" style="font-size: 0.75rem; padding: 5px 10px;">
                                                                <?= $u_item['is_suspended'] == 1 ? '<i class="fas fa-check"></i> Activate' : '<i class="fas fa-ban"></i> Suspend' ?>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">Self (Protected)</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>


                <!-- ------------------------------------------------------------------------ -->
                <!-- GPS FLEET TRACKER TAB -->
                <!-- ------------------------------------------------------------------------ -->
                <?php if ($tab === 'tracker'): ?>
                    <div class="panel-card" style="margin: 0; padding: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                            <div>
                                <h2 style="margin: 0;"><i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> GPS Fleet Live Tracker</h2>
                                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 5px 0 0 0;">Real-time GPS anti-theft tracking, route monitoring, and simulated landmark controls.</p>
                            </div>
                            <div style="background: #1e293b; color: #10b981; padding: 8px 15px; border-radius: 50px; font-weight: 600; font-size: 0.8rem; font-family: monospace; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                <span style="display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse-green 1.5s infinite;"></span>
                                LIVE FEED ACTIVE
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px; margin-top: 20px;">
                            
                            <!-- Vehicle List Section -->
                            <div style="display: flex; flex-direction: column; gap: 15px; max-height: 550px; overflow-y: auto; padding-right: 5px;">
                                <?php if (empty($tracker_cars)): ?>
                                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">No fleet vehicles found.</div>
                                <?php else: ?>
                                    <?php foreach ($tracker_cars as $index => $tc): ?>
                                        <div class="tracker-item" id="tracker-item-<?= $tc['id'] ?>" onclick="focusVehicle(<?= htmlspecialchars(json_encode($tc)) ?>)" style="background: white; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 15px; cursor: pointer; transition: all 0.3s; position: relative;">
                                            <div style="display: flex; gap: 12px; align-items: center;">
                                                <img src="<?= htmlspecialchars($tc['photo']) ?>" alt="car" style="width: 70px; height: 50px; border-radius: 4px; object-fit: cover; border: 1px solid #e2e8f0;">
                                                <div style="flex: 1; min-width: 0;">
                                                    <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        <?= htmlspecialchars($tc['brand'] . ' ' . $tc['model']) ?>
                                                    </h4>
                                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                                                        <span style="font-family: monospace; font-size: 0.8rem; background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                                                            <?= htmlspecialchars($tc['registration_number']) ?>
                                                        </span>
                                                        <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: <?= $tc['status'] === 'available' ? '#10b981' : '#f59e0b' ?>">
                                                            ● <?= htmlspecialchars($tc['status']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div style="margin-top: 10px; border-top: 1px dashed #e2e8f0; padding-top: 10px; font-size: 0.75rem; display: flex; flex-direction: column; gap: 4px; color: var(--text-muted);">
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span>Latitude:</span>
                                                    <strong class="latitude-label" style="font-family: monospace; color: var(--text-dark);"><?= htmlspecialchars($tc['latitude']) ?></strong>
                                                </div>
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span>Longitude:</span>
                                                    <strong class="longitude-label" style="font-family: monospace; color: var(--text-dark);"><?= htmlspecialchars($tc['longitude']) ?></strong>
                                                </div>
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span>Last Ping:</span>
                                                    <strong style="color: var(--text-dark);"><?= date('M d, H:i:s', strtotime($tc['last_tracked'])) ?></strong>
                                                </div>
                                            </div>

                                            <!-- Location Simulator Trigger Form -->
                                            <form action="../backend/update_location.php" method="POST" style="margin: 10px 0 0 0; display: flex; gap: 6px;" onclick="event.stopPropagation()">
                                                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                <input type="hidden" name="car_id" value="<?= $tc['id'] ?>">
                                                <select name="location_preset" class="form-control" style="padding: 4px 6px; font-size: 0.75rem; height: auto; flex: 1;" onchange="updateSimCoords(this)">
                                                    <option value="">-- Simulation presets --</option>
                                                    <option value="-1.2833,36.8219">Nairobi CBD (Main Office)</option>
                                                    <option value="-1.2616,36.8021">Westlands Shopping Mall</option>
                                                    <option value="-1.3197,36.9248">JKIA International Airport</option>
                                                    <option value="-1.3188,36.8155">Wilson Airport Hangar</option>
                                                    <option value="-1.3201,36.7029">The Hub Mall, Karen</option>
                                                    <option value="-1.3345,36.8890">Mombasa Road Highway</option>
                                                </select>
                                                <input type="hidden" name="latitude" class="lat-val" value="<?= $tc['latitude'] ?>">
                                                <input type="hidden" name="longitude" class="lng-val" value="<?= $tc['longitude'] ?>">
                                                <button type="submit" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.75rem;" title="Simulate relocation">
                                                    <i class="fas fa-satellite-dish"></i> Ping
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Live Map Simulation Screen -->
                            <div style="background: #0f172a; border-radius: var(--radius-lg); border: 1px solid #1e293b; padding: 20px; color: white; display: flex; flex-direction: column; position: relative; min-height: 550px;">
                                <!-- Map Screen Header -->
                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; padding-bottom: 15px; margin-bottom: 15px;">
                                    <div>
                                        <h3 id="live-car-title" style="margin: 0; color: #60a5fa; font-size: 1.1rem; font-family: 'Montserrat', sans-serif;">Select a Vehicle</h3>
                                        <p id="live-car-meta" style="margin: 3px 0 0 0; color: #94a3b8; font-size: 0.8rem;">Click on a fleet vehicle to connect live GPS tracker...</p>
                                    </div>
                                    <div id="live-car-status" style="font-family: monospace; font-size: 0.85rem; color: #94a3b8;">
                                        STATUS: OFFLINE
                                    </div>
                                </div>

                                <!-- Map Visual Area -->
                                <div style="flex: 1; border: 1px solid #334155; border-radius: var(--radius-md); background: radial-gradient(circle, #1e293b 0%, #0f172a 100%); position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                    
                                    <!-- High-tech Grid / Radar Circles -->
                                    <div style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0.15; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 20px 20px;"></div>
                                    <div style="position: absolute; border: 1px dashed rgba(96, 165, 250, 0.25); border-radius: 50%; width: 400px; height: 400px;"></div>
                                    <div style="position: absolute; border: 1px dashed rgba(96, 165, 250, 0.15); border-radius: 50%; width: 250px; height: 250px;"></div>
                                    <div style="position: absolute; border: 1px dashed rgba(96, 165, 250, 0.1); border-radius: 50%; width: 100px; height: 100px;"></div>
                                    
                                    <!-- Compass Crosshairs -->
                                    <div style="position: absolute; width: 100%; height: 1px; background: rgba(96, 165, 250, 0.1); pointer-events: none;"></div>
                                    <div style="position: absolute; height: 100%; width: 1px; background: rgba(96, 165, 250, 0.1); pointer-events: none;"></div>

                                    <!-- Glowing Radar Sweeper -->
                                    <div id="radar-sweeper" style="position: absolute; width: 450px; height: 450px; border-radius: 50%; background: conic-gradient(from 0deg, rgba(59, 130, 246, 0.15) 0deg, transparent 90deg); animation: radar-spin 4s linear infinite; transform-origin: center; display: none;"></div>

                                    <!-- Mock SVG Nairobi Map Lines -->
                                    <svg viewBox="0 0 800 500" style="position: absolute; width: 100%; height: 100%; opacity: 0.2; pointer-events: none;">
                                        <!-- Highways -->
                                        <path d="M 50 100 Q 400 250 750 400" fill="none" stroke="#60a5fa" stroke-width="4" />
                                        <path d="M 100 450 Q 300 200 700 50" fill="none" stroke="#3b82f6" stroke-width="2" />
                                        <path d="M 400 0 L 400 500" fill="none" stroke="#1e40af" stroke-width="1.5" stroke-dasharray="5,5" />
                                        
                                        <!-- Ring Road Bypass -->
                                        <circle cx="400" cy="250" r="180" fill="none" stroke="#2563eb" stroke-width="2" />
                                    </svg>

                                    <!-- Simulated Landmark Pins -->
                                    <div class="landmark-pin" style="position: absolute; transform: translate(-50%, -50%); left: 50%; top: 50%;">
                                        <div style="width: 6px; height: 6px; background: #64748b; border-radius: 50%;"></div>
                                        <div style="position: absolute; color: #64748b; font-size: 0.6rem; font-family: monospace; white-space: nowrap; top: 10px; left: -10px;">NAIROBI CBD</div>
                                    </div>
                                    <div class="landmark-pin" style="position: absolute; transform: translate(-50%, -50%); left: 75%; top: 65%;">
                                        <div style="width: 6px; height: 6px; background: #64748b; border-radius: 50%;"></div>
                                        <div style="position: absolute; color: #64748b; font-size: 0.6rem; font-family: monospace; white-space: nowrap; top: 10px; left: -10px;">JKIA AIRPORT</div>
                                    </div>
                                    <div class="landmark-pin" style="position: absolute; transform: translate(-50%, -50%); left: 25%; top: 35%;">
                                        <div style="width: 6px; height: 6px; background: #64748b; border-radius: 50%;"></div>
                                        <div style="position: absolute; color: #64748b; font-size: 0.6rem; font-family: monospace; white-space: nowrap; top: 10px; left: -10px;">KAREN HUB</div>
                                    </div>

                                    <!-- Live Tracker Ping Point (Centered on active vehicle) -->
                                    <div id="live-ping-container" style="position: absolute; transform: translate(-50%, -50%); display: none; transition: all 1s ease-in-out;">
                                        <span style="position: absolute; display: inline-block; width: 40px; height: 40px; border: 2px solid #ef4444; border-radius: 50%; animation: ping-glow 1.5s ease-out infinite; left: -13px; top: -13px;"></span>
                                        <div style="width: 14px; height: 14px; background: #ef4444; border: 2px solid white; border-radius: 50%; box-shadow: 0 0 10px #ef4444; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                            <i class="fas fa-satellite" style="color: white; font-size: 0.5rem;"></i>
                                        </div>
                                    </div>

                                    <div id="no-car-message" style="color: #64748b; font-family: monospace; font-size: 0.9rem; z-index: 1; text-align: center;">
                                        <i class="fas fa-satellite-dish" style="font-size: 2.5rem; margin-bottom: 12px; color: #475569; display: block;"></i>
                                        CONNECT LIVE GPS SATELLITE FEED TO INITIATE TRACKING
                                    </div>
                                </div>

                                <!-- Live Telemetry Feed -->
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; border-top: 1px solid #1e293b; padding-top: 15px; margin-top: 15px; font-family: monospace; font-size: 0.75rem; color: #94a3b8;">
                                    <div>
                                        LATITUDE: <span id="telemetry-lat" style="color: white;">--</span>
                                    </div>
                                    <div>
                                        LONGITUDE: <span id="telemetry-lng" style="color: white;">--</span>
                                    </div>
                                    <div>
                                        SPEED: <span id="telemetry-speed" style="color: white;">--</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- GPS Animation Styles -->
                    <style>
                        @keyframes pulse-green {
                            0% { transform: scale(0.95); opacity: 0.5; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
                            70% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
                            100% { transform: scale(0.95); opacity: 0.5; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
                        }
                        @keyframes radar-spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                        @keyframes ping-glow {
                            0% { transform: scale(0.3); opacity: 1; }
                            100% { transform: scale(1.8); opacity: 0; }
                        }
                        .tracker-item:hover {
                            border-color: #3b82f6 !important;
                            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
                        }
                        .tracker-item.active {
                            border-color: #2563eb !important;
                            background: #eff6ff !important;
                            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.15);
                        }
                    </style>

                    <!-- Tracker Logic -->
                    <script>
                        function updateSimCoords(selectEl) {
                            const val = selectEl.value;
                            const card = selectEl.closest('.tracker-item');
                            if (val) {
                                const parts = val.split(',');
                                card.querySelector('.lat-val').value = parts[0];
                                card.querySelector('.lng-val').value = parts[1];
                            }
                        }

                        let activeCarId = null;
                        let simInterval = null;

                        function focusVehicle(car) {
                            // Clear previous simulation intervals
                            if (simInterval) clearInterval(simInterval);

                            // Toggle active states on list
                            document.querySelectorAll('.tracker-item').forEach(el => el.classList.remove('active'));
                            document.getElementById('tracker-item-' + car.id).classList.add('active');

                            activeCarId = car.id;

                            // Show map controls
                            document.getElementById('no-car-message').style.display = 'none';
                            document.getElementById('live-ping-container').style.display = 'block';
                            document.getElementById('radar-sweeper').style.display = 'block';

                            // Update Map Meta Labels
                            document.getElementById('live-car-title').innerText = car.brand + ' ' + car.model;
                            document.getElementById('live-car-meta').innerText = 'Vehicle Registration: ' + car.registration_number + ' | ' + car.category;
                            document.getElementById('live-car-status').innerText = 'STATUS: ONLINE (SATELLITE CONNECTED)';
                            document.getElementById('live-car-status').style.color = '#10b981';

                            // Map coordinates to 2D pixel coordinates for simulated display (Nairobi CBD is the center -1.2833, 36.8219)
                            // We can use a offset scaling formula relative to CBD
                            const lat = parseFloat(car.latitude);
                            const lng = parseFloat(car.longitude);

                            updateTelemetry(lat, lng);

                            // Calculate mock screen positioning relative to CBD center
                            positionPingPoint(lat, lng);

                            // Simulate telemetry updating/fluctuations
                            simInterval = setInterval(() => {
                                // Add microscopic variations to simulation speed and coordinates to show a dynamic "live ping"
                                const noiseLat = (Math.random() - 0.5) * 0.0001;
                                const noiseLng = (Math.random() - 0.5) * 0.0001;
                                const currentLat = parseFloat(car.latitude) + noiseLat;
                                const currentLng = parseFloat(car.longitude) + noiseLng;

                                updateTelemetry(currentLat, currentLng);
                                positionPingPoint(currentLat, currentLng);
                            }, 3000);
                        }

                        function updateTelemetry(lat, lng) {
                            document.getElementById('telemetry-lat').innerText = lat.toFixed(5) + '° S';
                            document.getElementById('telemetry-lng').innerText = lng.toFixed(5) + '° E';
                            
                            // Mock a speed value depending on latitude/longitude (0 if CBD preset, otherwise cruising)
                            const isCBD = Math.abs(lat - (-1.2833)) < 0.001;
                            const speed = isCBD ? 0 : Math.floor(Math.random() * 40) + 40;
                            document.getElementById('telemetry-speed').innerText = speed + ' KM/H';
                        }

                        function positionPingPoint(lat, lng) {
                            // Center of Map (50% left, 50% top) represents Nairobi CBD coordinates (-1.2833, 36.8219)
                            // Let's set translation ratios:
                            const cbdLat = -1.2833;
                            const cbdLng = 36.8219;

                            const deltaLat = lat - cbdLat;
                            const deltaLng = lng - cbdLng;

                            // Scale delta into pixel percentages
                            // Latitude goes down as number decreases (more negative), so negative delta moves it downwards (lower top percentage)
                            // Longitude goes up as number increases, so positive delta moves it rightwards (higher left percentage)
                            let percentLeft = 50 + (deltaLng * 1500); 
                            let percentTop = 50 - (deltaLat * 1500);

                            // Restrict within bounds
                            percentLeft = Math.max(10, Math.min(90, percentLeft));
                            percentTop = Math.max(10, Math.min(90, percentTop));

                            const ping = document.getElementById('live-ping-container');
                            ping.style.left = percentLeft + '%';
                            ping.style.top = percentTop + '%';
                        }
                    </script>
                <?php endif; ?>


                <!-- ------------------------------------------------------------------------ -->
                <!-- AUDIT LOGS TAB -->
                <!-- ------------------------------------------------------------------------ -->
                <?php if ($tab === 'logs'): ?>
                    <div class="panel-card" style="margin: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2><i class="fas fa-list-alt"></i> System Audit Trails</h2>
                            <code style="background: #f1f5f9; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">Showing last 100 entries</code>
                        </div>

                        <div class="table-responsive">
                            <table class="custom-table" style="font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>IP Address</th>
                                        <th>Responsible User</th>
                                        <th>Role</th>
                                        <th>Logged Activity Action Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs_list)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 25px; color: var(--text-muted);">Audit log is clean. No activity recorded.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logs_list as $log): ?>
                                            <tr>
                                                <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                                                <td><code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></code></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($log['username'] ?? 'System Guest') ?></strong>
                                                </td>
                                                <td><span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);"><?= htmlspecialchars($log['role'] ?? 'guest') ?></span></td>
                                                <td style="font-family: monospace; color: var(--text-dark);"><?= htmlspecialchars($log['action']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date('Y') ?> Prestige Wheels Kenya. All rights reserved. Professional Car Hire Platform.</p>
    </footer>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
    <script>
        function openInspectionForm(bookingId, carModel, customerName) {
            document.getElementById('inspectBookingId').value = bookingId;
            document.getElementById('inspectLabelId').innerText = '#PW-' + String(bookingId).padStart(5, '0');
            document.getElementById('inspectLabelVehicle').innerText = carModel;
            document.getElementById('inspectLabelCustomer').innerText = customerName;
            
            // Show form
            const formBox = document.getElementById('inspectionFormContainer');
            formBox.style.display = 'block';
            
            // Scroll to form smoothly
            formBox.scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
