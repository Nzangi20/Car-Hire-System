<?php
// backend/db.php
// Centralized Database Connection Configuration

$host = 'localhost';
$db   = 'prestige_wheels';
$user = 'root';
$pass = '';
$port = '3306';
$ssl = false;

// 1. Try to parse database connection URL (e.g. from Aiven or Render environment)
$db_url = getenv('DATABASE_URL') ?: (getenv('JAWSDB_URL') ?: getenv('CLEARDB_DATABASE_URL'));
if ($db_url) {
    $parsed = parse_url($db_url);
    $host = $parsed['host'] ?? $host;
    $port = $parsed['port'] ?? $port;
    $user = $parsed['user'] ?? $user;
    $pass = $parsed['pass'] ?? $pass;
    if (isset($parsed['path'])) {
        $db = ltrim($parsed['path'], '/');
    }
    // Enable SSL if the host is Aiven or ssl-mode query is specified
    if (strpos($host, 'aivencloud.com') !== false || (isset($parsed['query']) && strpos($parsed['query'], 'ssl-mode=') !== false)) {
        $ssl = true;
    }
} else {
    // 2. Fallback to individual env variables
    $host = getenv('DB_HOST') ?: $host;
    $db   = getenv('DB_NAME') ?: $db;
    $user = getenv('DB_USER') ?: $user;
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : $pass;
    $port = getenv('DB_PORT') ?: $port;
    if (getenv('DB_SSL') === 'true' || getenv('DB_SSL') === '1') {
        $ssl = true;
    }
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Configure SSL for Aiven or secure database hosting
if ($ssl) {
    $ca_file = __DIR__ . '/ca.pem';
    if (!file_exists($ca_file)) {
        $ca_content = @file_get_contents('https://api.aiven.io/v1/ca.pem');
        if ($ca_content) {
            @file_put_contents($ca_file, $ca_content);
        }
    }
    if (file_exists($ca_file) && filesize($ca_file) > 0) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ca_file;
    }
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     
     // -------------------------------------------------------
     // 1. Dynamic Schema Initialization (Auto-Migration)
     // -------------------------------------------------------
     $chkTable = $pdo->query("SHOW TABLES LIKE 'cars'");
     if (!$chkTable->fetch()) {
         $sql_file = __DIR__ . '/database.sql';
         if (file_exists($sql_file)) {
             $sql = file_get_contents($sql_file);
             
             // Strip multi-line comments and clean up SQL input
             $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
             
             $queries = [];
             $accumulator = '';
             $in_string = false;
             $string_char = '';
             $escaped = false;
             
             $len = strlen($sql);
             for ($i = 0; $i < $len; $i++) {
                 $char = $sql[$i];
                 
                 if ($escaped) {
                     $accumulator .= $char;
                     $escaped = false;
                     continue;
                 }
                 if ($char === '\\') {
                     $accumulator .= $char;
                     $escaped = true;
                     continue;
                 }
                 if (($char === '"' || $char === "'") && !$in_string) {
                     $in_string = true;
                     $string_char = $char;
                     $accumulator .= $char;
                     continue;
                 }
                 if ($in_string && $char === $string_char) {
                     $in_string = false;
                     $accumulator .= $char;
                     continue;
                 }
                 if ($char === ';' && !$in_string) {
                     $queries[] = $accumulator;
                     $accumulator = '';
                     continue;
                 }
                 $accumulator .= $char;
             }
             if (trim($accumulator) !== '') {
                 $queries[] = $accumulator;
             }
             
             foreach ($queries as $q) {
                 $q = trim($q);
                 if ($q !== '') {
                     // Skip line comments
                     if (strpos($q, '--') === 0 || strpos($q, '#') === 0) {
                         continue;
                     }
                     // Skip database creation and switching statements
                     if (stripos($q, 'CREATE DATABASE') === 0 || stripos($q, 'USE ') === 0) {
                         continue;
                     }
                     try {
                         $pdo->exec($q);
                     } catch (\PDOException $ex) {
                         // Ignore minor errors on database creation if database already exists
                         if (strpos($ex->getMessage(), 'DATABASE') === false) {
                             throw $ex;
                         }
                     }
                 }
             }
         }
     }

     // -------------------------------------------------------
     // 2. Incremental Dynamic Schema Migrations (Fallback)
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
