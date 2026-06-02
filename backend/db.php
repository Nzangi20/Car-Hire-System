<?php
// backend/db.php
// Centralized Database Connection Configuration

$host = 'localhost';
$db   = 'prestige_wheels';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     
     // -------------------------------------------------------
     // Dynamic Schema Migrations
     // -------------------------------------------------------

     // Migration 1: Add 'created_at' to users table if missing
     $chk = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
     if (!$chk->fetch()) {
         $pdo->exec("ALTER TABLE users ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
     }

     // Migration 2: Add GPS tracking columns to cars table if missing
     $chk = $pdo->query("SHOW COLUMNS FROM cars LIKE 'latitude'");
     if (!$chk->fetch()) {
         $pdo->exec("ALTER TABLE cars ADD COLUMN latitude DECIMAL(10, 8) DEFAULT -1.2921");
         $pdo->exec("ALTER TABLE cars ADD COLUMN longitude DECIMAL(11, 8) DEFAULT 36.8219");
         $pdo->exec("ALTER TABLE cars ADD COLUMN last_tracked TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
         
         // Seed coords for default cars in Nairobi
         $pdo->exec("UPDATE cars SET latitude = -1.2833, longitude = 36.8219 WHERE id = 1");
         $pdo->exec("UPDATE cars SET latitude = -1.2616, longitude = 36.8021 WHERE id = 2");
         $pdo->exec("UPDATE cars SET latitude = -1.3197, longitude = 36.9248 WHERE id = 3");
         $pdo->exec("UPDATE cars SET latitude = -1.3188, longitude = 36.8155 WHERE id = 4");
         $pdo->exec("UPDATE cars SET latitude = -1.3201, longitude = 36.7029 WHERE id = 5");
     }
} catch (\PDOException $e) {
     die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}
?>
