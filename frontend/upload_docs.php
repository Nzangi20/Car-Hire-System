<?php
// frontend/upload_docs.php
require_once '../backend/db.php';
require_once '../backend/security.php';

// Access Control
require_role('customer');
check_suspension($pdo);

$user_id = $_SESSION['user_id'];
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

try {
    // Get user details
    $stmt = $pdo->prepare("SELECT verification_status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Get documents
    $doc_stmt = $pdo->prepare("SELECT * FROM user_documents WHERE user_id = ?");
    $doc_stmt->execute([$user_id]);
    $docs = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $license_doc = null;
    $id_doc = null;
    
    foreach ($docs as $d) {
        if ($d['document_type'] === 'license') {
            $license_doc = $d;
        } elseif ($d['document_type'] === 'id_passport') {
            $id_doc = $d;
        }
    }
} catch (\PDOException $e) {
    $error = "Failed to load documents.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Documents - Prestige Wheels</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
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
            <a href="dashboard.php" style="margin-right: 15px; color: white; text-decoration: none; font-weight: 500;">
                <i class="fas fa-columns"></i> Dashboard
            </a>
            <span>Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
            <a href="logout.php" style="margin-left: 15px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="main-container" style="padding-top: 40px; padding-bottom: 60px;">
        
        <div class="card" style="margin-bottom: 30px; padding: 30px;">
            <h2 style="margin-bottom: 10px; color: var(--primary-color);">Document Verification Portal</h2>
            <p style="color: var(--text-muted); margin-bottom: 20px;">To rent vehicles, your profile must be verified by our administrative team. Please upload your driving license and national ID or passport.</p>
            
            <div style="display: inline-block;">
                <span class="status-badge" style="
                    padding: 8px 16px; 
                    border-radius: 50px; 
                    font-weight: 700; 
                    font-size: 0.9rem;
                    text-transform: uppercase;
                    <?php
                        if ($user['verification_status'] === 'verified') {
                            echo 'background: #dcfce7; color: #15803d;';
                        } elseif ($user['verification_status'] === 'pending') {
                            echo 'background: #fef9c3; color: #a16207;';
                        } elseif ($user['verification_status'] === 'rejected') {
                            echo 'background: #fee2e2; color: #b91c1c;';
                        } else {
                            echo 'background: #f1f5f9; color: #475569;';
                        }
                    ?>
                ">
                    <i class="fas <?php
                        if ($user['verification_status'] === 'verified') {
                            echo 'fa-check-circle';
                        } elseif ($user['verification_status'] === 'pending') {
                            echo 'fa-clock';
                        } elseif ($user['verification_status'] === 'rejected') {
                            echo 'fa-times-circle';
                        } else {
                            echo 'fa-info-circle';
                        }
                    ?>"></i>
                    Verification Status: <?= htmlspecialchars($user['verification_status']) ?>
                </span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
            
            <!-- Driving License Column -->
            <div class="card" style="padding: 30px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 style="margin-bottom: 15px; color: var(--text-dark); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-id-card-alt"></i> Driving License
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">Upload a clear scanned copy of your valid driving license. Only PDF, JPG, JPEG, or PNG formats up to 5MB are accepted.</p>
                    
                    <!-- Preview -->
                    <div style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: var(--radius-md); padding: 15px; text-align: center; margin-bottom: 25px; min-height: 180px; display: flex; align-items: center; justify-content: center;">
                        <?php if ($license_doc): ?>
                            <?php 
                            $ext = strtolower(pathinfo($license_doc['file_path'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png'])): 
                            ?>
                                <img src="<?= htmlspecialchars($license_doc['file_path']) ?>" alt="Driving License" style="max-width: 100%; max-height: 160px; object-fit: contain; border-radius: var(--radius-sm);">
                            <?php else: ?>
                                <div style="text-align: center;">
                                    <i class="fas fa-file-pdf" style="font-size: 3.5rem; color: #ef4444; margin-bottom: 10px;"></i>
                                    <p style="font-size: 0.85rem; color: var(--text-dark); font-weight: 500;">Driving_License.pdf</p>
                                    <a href="<?= htmlspecialchars($license_doc['file_path']) ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.8rem; padding: 6px 12px; margin-top: 5px;">View File</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="color: var(--text-muted);">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; margin-bottom: 10px; color: #94a3b8;"></i>
                                <p style="font-size: 0.85rem;">No document uploaded yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($user['verification_status'] !== 'verified'): ?>
                    <form action="../backend/upload_documents.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        <input type="hidden" name="document_type" value="license">
                        <div class="form-group">
                            <input type="file" name="document_file" class="form-control" accept="image/*,application/pdf" required style="padding: 8px;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-upload"></i> <?= $license_doc ? 'Replace Document' : 'Upload Document' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success" style="margin: 0; text-align: center;">
                        <i class="fas fa-check-double"></i> Verified and Locked
                    </div>
                <?php endif; ?>
            </div>

            <!-- National ID / Passport Column -->
            <div class="card" style="padding: 30px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 style="margin-bottom: 15px; color: var(--text-dark); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-passport"></i> National ID / Passport
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">Upload a clear scanned copy of your national ID or passport page. Only PDF, JPG, JPEG, or PNG formats up to 5MB are accepted.</p>
                    
                    <!-- Preview -->
                    <div style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: var(--radius-md); padding: 15px; text-align: center; margin-bottom: 25px; min-height: 180px; display: flex; align-items: center; justify-content: center;">
                        <?php if ($id_doc): ?>
                            <?php 
                            $ext = strtolower(pathinfo($id_doc['file_path'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png'])): 
                            ?>
                                <img src="<?= htmlspecialchars($id_doc['file_path']) ?>" alt="National ID / Passport" style="max-width: 100%; max-height: 160px; object-fit: contain; border-radius: var(--radius-sm);">
                            <?php else: ?>
                                <div style="text-align: center;">
                                    <i class="fas fa-file-pdf" style="font-size: 3.5rem; color: #ef4444; margin-bottom: 10px;"></i>
                                    <p style="font-size: 0.85rem; color: var(--text-dark); font-weight: 500;">National_ID.pdf</p>
                                    <a href="<?= htmlspecialchars($id_doc['file_path']) ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.8rem; padding: 6px 12px; margin-top: 5px;">View File</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="color: var(--text-muted);">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; margin-bottom: 10px; color: #94a3b8;"></i>
                                <p style="font-size: 0.85rem;">No document uploaded yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($user['verification_status'] !== 'verified'): ?>
                    <form action="../backend/upload_documents.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        <input type="hidden" name="document_type" value="id_passport">
                        <div class="form-group">
                            <input type="file" name="document_file" class="form-control" accept="image/*,application/pdf" required style="padding: 8px;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-upload"></i> <?= $id_doc ? 'Replace Document' : 'Upload Document' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success" style="margin: 0; text-align: center;">
                        <i class="fas fa-check-double"></i> Verified and Locked
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>
