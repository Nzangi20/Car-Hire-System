<?php
// frontend/dashboard.php
require_once '../backend/db.php';
require_once '../backend/security.php';

// Access Control
require_role('customer');
check_suspension($pdo);

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Clear pending booking if requested
if (isset($_GET['cancel'])) {
    unset($_SESSION['pending_booking']);
    header("Location: dashboard.php");
    exit;
}

try {
    // Fetch user details
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch();

    // Fetch active bookings (not yet returned by admin)
    $active_stmt = $pdo->prepare("
        SELECT b.*, c.brand, c.model, c.photo, c.registration_number,
               p.transaction_id, p.amount as amount_paid, p.payment_type 
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE b.user_id = ? AND b.returned = 0
        ORDER BY b.pickup_datetime ASC
    ");
    $active_stmt->execute([$user_id]);
    $active_bookings = $active_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch booking history
    $hist_stmt = $pdo->prepare("
        SELECT b.*, c.brand, c.model, c.photo, 
               p.transaction_id, p.amount as amount_paid,
               i.inspection_status, i.penalties_amount
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        LEFT JOIN payments p ON b.id = p.booking_id
        LEFT JOIN inspections i ON b.id = i.booking_id
        WHERE b.user_id = ? AND b.returned = 1
        ORDER BY b.hire_date DESC
    ");
    $hist_stmt->execute([$user_id]);
    $history_bookings = $hist_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch system notifications
    $notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
    $notif_stmt->execute([$user_id]);
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unread notifications
    $unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->execute([$user_id]);
    $unread_count = $unread_stmt->fetchColumn();

} catch (\PDOException $e) {
    $error = "System error loading dashboard data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2.5fr;
            gap: 30px;
            margin-top: 30px;
        }
        .sidebar-panel {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        .main-panel {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        .notif-item {
            border-bottom: 1px solid #f1f5f9;
            padding: 12px 0;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .notif-item:last-child {
            border-bottom: none;
        }
        .notif-unread {
            background: #f8fafc;
            border-left: 3px solid var(--primary-light);
            padding-left: 8px;
        }
        @media (max-width: 992px) {
            .dashboard-grid {
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
            <a href="index.php" style="margin-right: 15px; color: white; text-decoration: none; font-weight: 500;">
                <i class="fas fa-car"></i> Browse Cars
            </a>
            <span>Logged in as: <strong><?= htmlspecialchars($username) ?></strong></span>
            <a href="logout.php" style="margin-left: 15px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container" style="padding-top: 40px; padding-bottom: 60px;">
        
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
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; border-radius: var(--radius-lg); padding: 35px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <h1 style="margin: 0; font-size: 2rem; font-family: 'Montserrat', sans-serif;">Hello, <?= htmlspecialchars($user_data['fullname']) ?>!</h1>
                <p style="margin: 5px 0 0 0; color: #94a3b8; font-size: 1rem;">Welcome back to your Prestige Wheels space.</p>
            </div>
            <a href="index.php#fleet" class="btn btn-primary" style="padding: 12px 25px;">
                <i class="fas fa-search"></i> Book Another Car
            </a>
        </div>

        <div class="dashboard-grid">
            
            <!-- Sidebar column: profile status, notifications -->
            <div class="sidebar-panel">
                
                <!-- Verification Widget -->
                <div class="card" style="padding: 25px;">
                    <h3 style="color: var(--text-dark); margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user-shield" style="color: var(--primary-color);"></i> Account Verification
                    </h3>
                    
                    <div style="margin-bottom: 20px;">
                        <span class="status-badge" style="
                            padding: 6px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;
                            <?php
                                if ($user_data['verification_status'] === 'verified') {
                                    echo 'background: #dcfce7; color: #15803d;';
                                } elseif ($user_data['verification_status'] === 'pending') {
                                    echo 'background: #fef9c3; color: #a16207;';
                                } elseif ($user_data['verification_status'] === 'rejected') {
                                    echo 'background: #fee2e2; color: #b91c1c;';
                                } else {
                                    echo 'background: #f1f5f9; color: #475569;';
                                }
                            ?>
                        ">
                            <?= htmlspecialchars($user_data['verification_status']) ?>
                        </span>
                    </div>

                    <?php if ($user_data['verification_status'] !== 'verified'): ?>
                        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 15px;">
                            You must upload your driving license and national ID/passport to verify your account before booking.
                        </p>
                        <a href="upload_docs.php" class="btn btn-secondary" style="width: 100%; font-size: 0.85rem; padding: 10px; justify-content: center;">
                            <i class="fas fa-upload"></i> Upload Documents
                        </a>
                    <?php else: ?>
                        <p style="font-size: 0.85rem; color: #15803d; line-height: 1.5; margin: 0;">
                            <i class="fas fa-check-circle"></i> Your documents are verified. You are clear to rent vehicles.
                        </p>
                    <?php endif; ?>
                    
                    <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 20px 0;">
                    <a href="profile.php" style="font-size: 0.9rem; color: var(--primary-light); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="fas fa-cog"></i> Edit Account Settings
                    </a>
                </div>

                <!-- Notifications Center -->
                <div class="card" style="padding: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="color: var(--text-dark); margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-bell" style="color: var(--primary-color);"></i> Notifications 
                            <?php if ($unread_count > 0): ?>
                                <span style="background: #ef4444; color: white; font-size: 0.75rem; padding: 2px 7px; border-radius: 50px;"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </h3>
                        <?php if ($unread_count > 0): ?>
                            <a href="../backend/mark_notifications.php?id=all" style="font-size: 0.75rem; color: var(--primary-light); text-decoration: none;">Mark all read</a>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; flex-direction: column; max-height: 350px; overflow-y: auto; padding-right: 5px;">
                        <?php if (empty($notifications)): ?>
                            <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 20px 0;">No system alerts at the moment.</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <div class="notif-item <?= $n['is_read'] == 0 ? 'notif-unread' : '' ?>">
                                    <div style="flex: 1;">
                                        <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 10px;">
                                            <strong style="font-size: 0.85rem; color: var(--text-dark);"><?= htmlspecialchars($n['title']) ?></strong>
                                            <?php if ($n['is_read'] == 0): ?>
                                                <a href="../backend/mark_notifications.php?id=<?= $n['id'] ?>" style="font-size: 0.7rem; color: var(--text-muted);" title="Mark read">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <p style="margin: 3px 0 0 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;"><?= htmlspecialchars($n['message']) ?></p>
                                        <span style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-top: 5px;"><?= date('M d, H:i', strtotime($n['created_at'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Main Panel: active rentals & bookings history -->
            <div class="main-panel">
                
                <!-- Active Rentals Panel -->
                <div class="card" style="padding: 30px;">
                    <h2 style="color: var(--text-dark); margin-bottom: 20px; font-size: 1.3rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-key" style="color: #2563eb;"></i> My Active Rentals
                    </h2>

                    <?php if (empty($active_bookings)): ?>
                        <div style="text-align: center; padding: 40px 20px; background: #f8fafc; border-radius: var(--radius-md); border: 1px dashed #cbd5e1;">
                            <i class="fas fa-car" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 10px;"></i>
                            <p style="color: var(--text-muted); margin: 0; font-size: 0.95rem;">You do not have any active vehicle rentals right now.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <?php foreach ($active_bookings as $ab): ?>
                                <div style="display: flex; gap: 20px; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; flex-wrap: wrap;">
                                    <img src="<?= htmlspecialchars($ab['photo']) ?>" alt="car" style="width: 140px; height: 90px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid #f1f5f9;">
                                    
                                    <div style="flex: 1; min-width: 250px;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;">
                                            <h4 style="margin: 0; font-size: 1.15rem; color: var(--text-dark);"><?= htmlspecialchars($ab['brand'] . ' ' . $ab['model']) ?></h4>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);">Ref: #PW-<?= str_pad($ab['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; font-size: 0.85rem; color: var(--text-muted);">
                                            <div>Pickup: <strong style="color: var(--text-dark);"><?= date('M d, H:i', strtotime($ab['pickup_datetime'])) ?></strong></div>
                                            <div>Return By: <strong style="color: var(--text-dark);"><?= date('M d, H:i', strtotime($ab['return_datetime'])) ?></strong></div>
                                            <div>Pickup Loc: <strong style="color: var(--text-dark);"><?= htmlspecialchars($ab['pickup_location']) ?></strong></div>
                                            <div>Return Loc: <strong style="color: var(--text-dark);"><?= htmlspecialchars($ab['return_location']) ?></strong></div>
                                        </div>

                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                                            <div>
                                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Paid Amount:</span>
                                                <strong style="color: #16a34a;">KSh <?= number_format($ab['amount_paid'] ?? 0) ?> (<?= htmlspecialchars($ab['payment_type'] ?? 'full') ?>)</strong>
                                            </div>
                                            
                                            <!-- Return Simulation Actions -->
                                            <div>
                                                <?php if ($ab['status'] === 'completed'): ?>
                                                    <span class="status-badge" style="background: #fef9c3; color: #a16207; padding: 6px 12px; font-size: 0.8rem;">
                                                        <i class="fas fa-spinner fa-spin"></i> Awaiting Admin Inspection
                                                    </span>
                                                <?php else: ?>
                                                    <a href="../backend/request_return.php?booking_id=<?= $ab['id'] ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 8px 16px;" onclick="return confirm('Simulate returning this car back to the return station?')">
                                                        <i class="fas fa-undo"></i> Return Vehicle
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="receipt.php?booking_id=<?= $ab['id'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.8rem; padding: 8px 12px; margin-left: 8px;" title="Print Invoice">
                                                    <i class="fas fa-print"></i> Invoice
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Rental History Panel -->
                <div class="card" style="padding: 30px;">
                    <h2 style="color: var(--text-dark); margin-bottom: 20px; font-size: 1.3rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-history" style="color: var(--primary-color);"></i> Rental History
                    </h2>

                    <div class="table-responsive">
                        <table class="custom-table" style="font-size: 0.9rem;">
                            <thead>
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Period</th>
                                    <th>Cost</th>
                                    <th>Inspection Status</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history_bookings)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                            You have no completed rentals in your history.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($history_bookings as $hb): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <img src="<?= htmlspecialchars($hb['photo']) ?>" alt="car" style="width: 50px; height: 35px; object-fit: cover; border-radius: var(--radius-sm);">
                                                    <strong><?= htmlspecialchars($hb['brand'] . ' ' . $hb['model']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($hb['hire_days']) ?> Days<br>
                                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?= date('M d, Y', strtotime($hb['pickup_datetime'])) ?></span>
                                            </td>
                                            <td>
                                                <strong style="color: var(--text-dark);">KSh <?= number_format($hb['total_amount']) ?></strong>
                                            </td>
                                            <td>
                                                <?php if (!empty($hb['inspection_status'])): ?>
                                                    <span class="status-badge" style="
                                                        padding: 3px 8px; font-size: 0.75rem;
                                                        <?= $hb['inspection_status'] === 'clean' ? 'background: #dcfce7; color: #15803d;' : 'background: #fee2e2; color: #b91c1c;' ?>
                                                    ">
                                                        <?= htmlspecialchars($hb['inspection_status']) ?>
                                                    </span>
                                                    <?php if ($hb['penalties_amount'] > 0): ?>
                                                        <span style="display: block; font-size: 0.75rem; color: #b91c1c;">Penalty: KSh <?= number_format($hb['penalties_amount']) ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-size: 0.8rem;">No inspection records</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge" style="background: #e2e8f0; color: #475569; padding: 3px 8px; font-size: 0.75rem;">
                                                    <?= htmlspecialchars($hb['status']) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <a href="receipt.php?booking_id=<?= $hb['id'] ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 5px 10px;">
                                                    <i class="fas fa-print"></i> Receipt
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date('Y') ?> Prestige Wheels Kenya. All rights reserved. Professional Car Hire Platform.</p>
    </footer>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>
