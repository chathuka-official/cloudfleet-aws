<?php

$host = $_SERVER['RDS_HOSTNAME'] ?? getenv('RDS_HOSTNAME') ?: '';
$port = $_SERVER['RDS_PORT'] ?? getenv('RDS_PORT') ?: '3306';
$db   = $_SERVER['RDS_DB_NAME'] ?? getenv('RDS_DB_NAME') ?: '';
$user = $_SERVER['RDS_USERNAME'] ?? getenv('RDS_USERNAME') ?: '';
$pass = $_SERVER['RDS_PASSWORD'] ?? getenv('RDS_PASSWORD') ?: '';

if ($host === '' || $db === '' || $user === '') {
    http_response_code(500);
    die('Database configuration is missing.');
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed.');
}
