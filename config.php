<?php
$host     = 'localhost';
$username = 'root';
$password = '';          // XAMPP default — change if you set a password
$database = 'blessed_board';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$conn->set_charset('utf8mb4');

// BASE_URL — works even if the folder is renamed
if (!defined('BASE_URL')) {
    $isHttps  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $scheme   = $isHttps ? 'https' : 'http';
    $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    define('BASE_URL', $scheme . '://' . $httpHost . ($basePath ?: '') . '/');
}
