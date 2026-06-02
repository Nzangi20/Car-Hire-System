<?php
// frontend/car_details.php
require_once '../backend/db.php';
require_once '../backend/security.php';

$car_id = intval($_GET['id'] ?? 0);

try {
    // Get car details
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();

    if (!$car) {
        header("Location: index.php");
        exit;
    }

    // Get additional images
    $img_stmt = $pdo->prepare("SELECT photo_path FROM car_images WHERE car_id = ?");
    $img_stmt->execute([$car_id]);
    $additional_images = $img_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get reviews
    $rev_stmt = $pdo->prepare("
        SELECT r.*, u.username, u.fullname, u.profile_picture 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.car_id = ? 
        ORDER BY r.created_at DESC
    ");
    $rev_stmt->execute([$car_id]);
    $reviews = $rev_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate rating stats
    $avg_rating = 0.0;
    $rating_count = count($reviews);
    if ($rating_count > 0) {
        $total_rating = array_sum(array_column($reviews, 'rating'));
        $avg_rating = round($total_rating / $rating_count, 1);
    }

    // Check if user has completed rental for this car to allow reviews
    $can_review = false;
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer') {
        $bk_stmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND car_id = ? AND status = 'completed' LIMIT 1");
        $bk_stmt->execute([$_SESSION['user_id'], $car_id]);
        if ($bk_stmt->fetch()) {
            $can_review = true;
        }
    }
} catch (\PDOException $e) {
    die("System error loading car details.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?> - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .details-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 40px;
            margin-top: 30px;
        }
        .gallery-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .main-img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: var(--radius-lg);
            border: 1px solid #e2e8f0;
        }
        .thumbnails {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .thumb {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            cursor: pointer;
            border: 2px solid transparent;
            transition: all var(--transition-speed);
        }
        .thumb.active, .thumb:hover {
            border-color: var(--primary-color);
        }
        .spec-item {
            background: #f8fafc;
            border-radius: var(--radius-md);
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .spec-item i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        .star-rating i {
            color: #fbbf24;
        }
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            .main-img {
                height: 250px;
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
            <a href="index.php#fleet" style="margin-right: 15px; color: white; text-decoration: none; font-weight: 500;">
                <i class="fas fa-arrow-left"></i> Back to Fleet
            </a>
            <?php if (isset($_SESSION['username'])): ?>
                <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <a href="<?= in_array($_SESSION['role'], ['super_admin','manager','staff']) ? 'admin.php' : 'dashboard.php' ?>">Dashboard</a>
            <?php else: ?>
                <a href="login.php" style="background: transparent; border-color: rgba(255,255,255,0.3);"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="main-container" style="padding-top: 40px; padding-bottom: 60px;">
        
        <div class="details-grid">
            
            <!-- Left Side: Images & Gallery -->
            <div class="gallery-container">
                <img id="mainGalleryImage" class="main-img" src="<?= htmlspecialchars($car['photo']) ?>" alt="<?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?>">
                
                <div class="thumbnails">
                    <!-- Base Photo -->
                    <img class="thumb active" src="<?= htmlspecialchars($car['photo']) ?>" alt="Base View" onclick="setMainImage(this)">
                    
                    <!-- Additional Photos -->
                    <?php foreach ($additional_images as $img): ?>
                        <img class="thumb" src="<?= htmlspecialchars($img) ?>" alt="Detail View" onclick="setMainImage(this)">
                    <?php endforeach; ?>
                </div>

                <div class="card" style="padding: 25px; margin-top: 20px;">
                    <h3 style="margin-bottom: 15px; color: var(--text-dark);">Description</h3>
                    <p style="color: var(--text-muted); line-height: 1.6;"><?= nl2br(htmlspecialchars($car['description'] ?? 'No description provided.')) ?></p>
                </div>
            </div>

            <!-- Right Side: Details & Specs -->
            <div style="display: flex; flex-direction: column; gap: 30px;">
                
                <div class="card" style="padding: 30px;">
                    <span style="font-size: 0.9rem; text-transform: uppercase; color: var(--primary-color); font-weight: 700;"><?= htmlspecialchars($car['category'] ?? 'Sedan') ?></span>
                    <h1 style="font-size: 2.2rem; color: var(--text-dark); font-weight: 800; margin-top: 5px; margin-bottom: 10px;"><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></h1>
                    
                    <!-- Rating Summary -->
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                        <div class="star-rating">
                            <?php for ($i=1; $i<=5; $i++): ?>
                                <i class="<?= $i <= round($avg_rating) ? 'fas' : 'far' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <strong style="color: var(--text-dark);"><?= $avg_rating ?></strong>
                        <span style="color: var(--text-muted);"> (<?= $rating_count ?> reviews)</span>
                    </div>

                    <div style="background: #f8fafc; padding: 20px; border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <div>
                            <span style="font-size: 0.85rem; color: var(--text-muted); display: block;">Daily Rental Charge</span>
                            <span style="font-size: 1.8rem; font-weight: 800; color: var(--text-dark);">KSh <?= number_format($car['charge_per_day']) ?></span>
                        </div>
                        <span class="status-badge" style="
                            padding: 8px 16px; border-radius: 50px; font-weight: 700;
                            <?= $car['status'] === 'available' ? 'background: #dcfce7; color: #15803d;' : 'background: #fee2e2; color: #b91c1c;' ?>
                        ">
                            <?= htmlspecialchars($car['status']) ?>
                        </span>
                    </div>

                    <!-- Specs Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px;">
                        <div class="spec-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Year</span>
                                <strong style="color: var(--text-dark);"><?= htmlspecialchars($car['year'] ?? 'N/A') ?></strong>
                            </div>
                        </div>

                        <div class="spec-item">
                            <i class="fas fa-cogs"></i>
                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Transmission</span>
                                <strong style="color: var(--text-dark); text-transform: capitalize;"><?= htmlspecialchars($car['transmission']) ?></strong>
                            </div>
                        </div>

                        <div class="spec-item">
                            <i class="fas fa-gas-pump"></i>
                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Fuel Type</span>
                                <strong style="color: var(--text-dark);"><?= htmlspecialchars($car['fuel_type'] ?? 'Petrol') ?></strong>
                            </div>
                        </div>

                        <div class="spec-item">
                            <i class="fas fa-users"></i>
                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Capacity</span>
                                <strong style="color: var(--text-dark);"><?= htmlspecialchars($car['capacity']) ?> Seats</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Book Action -->
                    <?php if ($car['status'] === 'available'): ?>
                        <a href="book_car.php?car_id=<?= $car['id'] ?>" class="btn btn-primary" style="width: 100%; padding: 15px; justify-content: center; font-size: 1.1rem;">
                            <i class="fas fa-calendar-check"></i> Book This Car
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary" disabled style="width: 100%; padding: 15px; justify-content: center; font-size: 1.1rem; background: #94a3b8; border-color: #94a3b8; cursor: not-allowed;">
                            Currently Under Maintenance
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Reviews and ratings -->
                <div class="card" style="padding: 30px;">
                    <h3 style="margin-bottom: 25px; color: var(--text-dark); border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                        <i class="fas fa-star" style="color: #fbbf24;"></i> Guest Reviews
                    </h3>
                    
                    <?php if ($can_review): ?>
                        <!-- Write a review form -->
                        <form action="../backend/submit_review.php" method="POST" style="margin-bottom: 35px; background: #f8fafc; padding: 20px; border-radius: var(--radius-md);">
                            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                            <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                            
                            <h4 style="margin-bottom: 15px; color: var(--text-dark);">Share Your Experience</h4>
                            
                            <div class="form-group">
                                <label for="rating" style="font-size: 0.85rem; font-weight: 600;">Rating (1 to 5 Stars)</label>
                                <select name="rating" id="rating" class="form-control" style="padding: 10px;" required>
                                    <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                                    <option value="4">⭐⭐⭐⭐ (4 - Good)</option>
                                    <option value="3">⭐⭐⭐ (3 - Average)</option>
                                    <option value="2">⭐⭐ (2 - Poor)</option>
                                    <option value="1">⭐ (1 - Terrible)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="comment" style="font-size: 0.85rem; font-weight: 600;">Your Review Comment</label>
                                <textarea name="comment" id="comment" class="form-control" placeholder="Write your feedback..." rows="3" required style="padding: 10px;"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.9rem;">
                                <i class="fas fa-paper-plane"></i> Submit Review
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Reviews List -->
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php if (empty($reviews)): ?>
                            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center;">No reviews yet for this vehicle. Be the first to rent and review!</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $rev): ?>
                                <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; display: flex; gap: 15px;">
                                    <?php if (!empty($rev['profile_picture'])): ?>
                                        <img src="<?= htmlspecialchars($rev['profile_picture']) ?>" alt="User" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 45px; height: 45px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user" style="color: #94a3b8;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div style="flex: 1;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <strong style="color: var(--text-dark);"><?= htmlspecialchars($rev['fullname']) ?></strong>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                                        </div>
                                        <div class="star-rating" style="margin: 5px 0;">
                                            <?php for ($i=1; $i<=5; $i++): ?>
                                                <i class="<?= $i <= $rev['rating'] ? 'fas' : 'far' ?> fa-star" style="font-size: 0.8rem;"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; line-height: 1.5;"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- JS Image Switcher -->
    <script>
        function setMainImage(thumbnail) {
            // Update src
            document.getElementById('mainGalleryImage').src = thumbnail.src;
            
            // Remove active classes
            const thumbs = document.querySelectorAll('.thumb');
            thumbs.forEach(t => t.classList.remove('active'));
            
            // Add active class
            thumbnail.classList.add('active');
        }
    </script>
</body>
</html>
