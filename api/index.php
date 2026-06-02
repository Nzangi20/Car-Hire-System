<?php
// api/index.php
// Centralized serverless router for running PHP on Vercel

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($uri, PHP_URL_PATH);

// Clean up slash
$uri = ltrim($uri, '/');

// Default path redirect
if (empty($uri) || $uri === 'index.php') {
    $uri = 'index.php';
}

$target_file = __DIR__ . '/../' . $uri;

if (file_exists($target_file)) {
    // If it's a directory, append index.php
    if (is_dir($target_file)) {
        $target_file = rtrim($target_file, '/') . '/index.php';
    }
    
    // Check if the resolved path is a PHP file
    if (pathinfo($target_file, PATHINFO_EXTENSION) === 'php') {
        $_SERVER['SCRIPT_FILENAME'] = realpath($target_file);
        
        // Emulate Apache environment by shifting the working directory to the script's directory.
        // This ensures relative path includes (e.g. require_once '../backend/security.php') work perfectly.
        chdir(dirname($_SERVER['SCRIPT_FILENAME']));
        
        require $_SERVER['SCRIPT_FILENAME'];
        exit;
    }
}

// If file doesn't exist, return 404
http_response_code(404);
echo "404 Not Found: " . htmlspecialchars($uri);
?>
