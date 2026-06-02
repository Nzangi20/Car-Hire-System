<?php
// frontend/receipt.php
require_once '../backend/db.php';
require_once '../backend/security.php';

// Access Control
require_login();
check_suspension($pdo);

$booking_id = intval($_GET['booking_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    // Fetch booking details
    $stmt = $pdo->prepare("
        SELECT b.*, c.brand, c.model, c.registration_number, c.charge_per_day, c.category,
               u.fullname, u.email, u.phone as uphone, u.id_number 
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        die("Receipt not found.");
    }

    // Ownership check: Customer can only view their own receipts. Admins/Managers/Staff can view all.
    if ($role === 'customer' && intval($booking['user_id']) !== $user_id) {
        header("HTTP/1.1 403 Forbidden");
        die("403 Forbidden: You do not have permission to view this receipt.");
    }

    // Fetch payment details
    $pay_stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ?");
    $pay_stmt->execute([$booking_id]);
    $payments = $pay_stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_paid = array_sum(array_column($payments, 'amount'));
    $balance_due = max(0, $booking['total_amount'] - $total_paid);

} catch (\PDOException $e) {
    die("Database error loading receipt details.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #PW-<?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT) ?> - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary-color: #1e293b;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius-md: 8px;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: var(--text-dark);
            margin: 0;
            padding: 40px 20px;
        }
        .receipt-container {
            max-width: 750px;
            background: white;
            margin: 0 auto;
            border-radius: var(--radius-md);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 50px;
            border: 1px solid var(--border-color);
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 30px;
            margin-bottom: 40px;
        }
        .receipt-logo h1 {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            font-size: 1.8rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .receipt-title {
            text-align: right;
        }
        .receipt-title h2 {
            margin: 0;
            font-size: 1.6rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .receipt-title p {
            margin: 5px 0 0 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .grid-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        .grid-col h3 {
            margin: 0 0 12px 0;
            font-size: 0.95rem;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }
        .grid-col p {
            margin: 5px 0;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .table-charges {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .table-charges th {
            border-bottom: 2px solid var(--border-color);
            text-align: left;
            padding: 12px;
            font-weight: 700;
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .table-charges td {
            border-bottom: 1px solid var(--border-color);
            padding: 15px 12px;
            font-size: 0.95rem;
        }
        .receipt-summary {
            width: 320px;
            margin-left: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 0.95rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
        }
        .summary-row.total {
            font-size: 1.2rem;
            font-weight: 800;
            border-top: 1px solid var(--border-color);
            padding-top: 10px;
            margin-top: 5px;
        }
        .floating-actions {
            max-width: 750px;
            margin: 20px auto 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: #0f172a;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            text-decoration: none;
        }
        .btn-back {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border: none;
                padding: 0;
            }
            .floating-actions {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        
        <!-- Header -->
        <div class="receipt-header">
            <div class="receipt-logo">
                <h1><i class="fas fa-car-side" style="color: #2563eb;"></i> Prestige Wheels</h1>
                <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                    101 Airport Road, Nairobi, Kenya<br>
                    info@prestigewheels.com | +254 711 223344
                </p>
            </div>
            <div class="receipt-title">
                <h2>Invoice Receipt</h2>
                <p>Invoice #: <strong>PW-<?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT) ?></strong></p>
                <p>Date: <?= date('M d, Y', strtotime($booking['hire_date'])) ?></p>
            </div>
        </div>

        <!-- Billing Info -->
        <div class="grid-details">
            <div class="grid-col">
                <h3>Billed To</h3>
                <p><strong><?= htmlspecialchars($booking['fullname']) ?></strong></p>
                <p>ID/Passport: <?= htmlspecialchars($booking['id_number']) ?></p>
                <p>Email: <?= htmlspecialchars($booking['email']) ?></p>
                <p>Phone: <?= htmlspecialchars($booking['phone']) ?></p>
            </div>
            
            <div class="grid-col">
                <h3>Rental Period</h3>
                <p>Pickup: <strong><?= date('M d, Y H:i', strtotime($booking['pickup_datetime'])) ?></strong></p>
                <p>Return: <strong><?= date('M d, Y H:i', strtotime($booking['return_datetime'])) ?></strong></p>
                <p>Pickup Office: <?= htmlspecialchars($booking['pickup_location']) ?></p>
                <p>Return Office: <?= htmlspecialchars($booking['return_location']) ?></p>
            </div>
        </div>

        <!-- Charges Table -->
        <table class="table-charges">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>Rate</th>
                    <th>Duration</th>
                    <th style="text-align: right;">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($booking['brand'] . ' ' . $booking['model']) ?></strong>
                        <span style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-top: 3px;">Plate: <?= htmlspecialchars($booking['registration_number'] ?? 'N/A') ?> | Category: <?= htmlspecialchars($booking['category']) ?></span>
                    </td>
                    <td>KSh <?= number_format($booking['charge_per_day']) ?> / day</td>
                    <td><?= htmlspecialchars($booking['hire_days']) ?> Days</td>
                    <td style="text-align: right; font-weight: 700;">KSh <?= number_format($booking['total_amount']) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Summary & Balance -->
        <div class="receipt-summary">
            <div class="summary-row">
                <span>Subtotal Due:</span>
                <strong>KSh <?= number_format($booking['total_amount']) ?></strong>
            </div>
            
            <?php foreach ($payments as $i => $pay): ?>
                <div class="summary-row" style="color: #15803d; font-size: 0.9rem;">
                    <span>Payment <?= $i + 1 ?> (<?= htmlspecialchars($pay['payment_type']) ?> - <?= htmlspecialchars($pay['transaction_id']) ?>):</span>
                    <strong>- KSh <?= number_format($pay['amount']) ?></strong>
                </div>
            <?php endforeach; ?>

            <div class="summary-row total">
                <span>Balance Due:</span>
                <span style="color: <?= $balance_due > 0 ? '#b91c1c' : '#15803d' ?>;">KSh <?= number_format($balance_due) ?></span>
            </div>

            <div class="summary-row" style="margin-top: 20px; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">
                <span>Booking Status:</span>
                <strong style="color: #1e293b;"><?= htmlspecialchars($booking['status']) ?></strong>
            </div>
        </div>

    </div>

    <!-- Actions -->
    <div class="floating-actions">
        <a href="<?= $role === 'customer' ? 'dashboard.php' : 'admin.php' ?>" class="btn-back">
            <i class="fas fa-chevron-left"></i> Return to Dashboard
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Print Receipt
        </button>
    </div>

</body>
</html>
