<?php
// backend/upload_documents.php
require_once 'db.php';
require_once 'security.php';

// Access Control
require_role('customer');
csrf_protect();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type = trim($_POST['document_type'] ?? '');
    
    if (!in_array($document_type, ['license', 'id_passport'])) {
        header("Location: ../frontend/upload_docs.php?error=" . urlencode("Invalid document type selected."));
        exit;
    }

    // Check if user is already verified
    $stmt = $pdo->prepare("SELECT verification_status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user && $user['verification_status'] === 'verified') {
        header("Location: ../frontend/upload_docs.php?error=" . urlencode("Your documents have already been verified and locked."));
        exit;
    }

    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['document_file']['tmp_name'];
        $fileName = $_FILES['document_file']['name'];
        $fileSize = $_FILES['document_file']['size'];
        
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (!in_array($ext, $allowedExtensions)) {
            header("Location: ../frontend/upload_docs.php?error=" . urlencode("Invalid file format. Only PDF, JPG, JPEG, and PNG are allowed."));
            exit;
        }
        
        // Limit to 5MB
        if ($fileSize > 5 * 1024 * 1024) {
            header("Location: ../frontend/upload_docs.php?error=" . urlencode("Document file size must be smaller than 5MB."));
            exit;
        }

        try {
            // Delete old file of this type if exists
            $stmt = $pdo->prepare("SELECT file_path FROM user_documents WHERE user_id = ? AND document_type = ?");
            $stmt->execute([$user_id, $document_type]);
            $oldDoc = $stmt->fetch();
            if ($oldDoc) {
                $oldPath = '../frontend/' . $oldDoc['file_path'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
                
                // Remove record
                $del_stmt = $pdo->prepare("DELETE FROM user_documents WHERE user_id = ? AND document_type = ?");
                $del_stmt->execute([$user_id, $document_type]);
            }

            $targetDir = "../frontend/uploads/verification_docs/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $newFileName = $document_type . '_' . $user_id . '_' . uniqid() . '.' . $ext;
            $destPath = $targetDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $dbPath = 'uploads/verification_docs/' . $newFileName;
                
                // Insert new record
                $ins_stmt = $pdo->prepare("INSERT INTO user_documents (user_id, document_type, file_path) VALUES (?, ?, ?)");
                $ins_stmt->execute([$user_id, $document_type, $dbPath]);

                // Check if user has uploaded both documents
                $chk_stmt = $pdo->prepare("SELECT COUNT(*) FROM user_documents WHERE user_id = ?");
                $chk_stmt->execute([$user_id]);
                $count = $chk_stmt->fetchColumn();

                if ($count >= 2) {
                    // Update user verification status to pending
                    $upd_stmt = $pdo->prepare("UPDATE users SET verification_status = 'pending' WHERE id = ?");
                    $upd_stmt->execute([$user_id]);
                    log_activity($pdo, $user_id, "User submitted both verification documents; status set to pending");
                    
                    header("Location: ../frontend/upload_docs.php?success=" . urlencode("Document uploaded successfully. Your verification status is now 'pending' awaiting review."));
                } else {
                    log_activity($pdo, $user_id, "User uploaded driving license or ID document");
                    header("Location: ../frontend/upload_docs.php?success=" . urlencode("Document uploaded successfully. Please upload the remaining document."));
                }
                exit;
            } else {
                header("Location: ../frontend/upload_docs.php?error=" . urlencode("Could not save uploaded document."));
                exit;
            }
        } catch (\PDOException $e) {
            header("Location: ../frontend/upload_docs.php?error=" . urlencode("Database error handling documents."));
            exit;
        }
    } else {
        header("Location: ../frontend/upload_docs.php?error=" . urlencode("Please select a document file to upload."));
        exit;
    }
} else {
    header("Location: ../frontend/upload_docs.php");
    exit;
}
?>
