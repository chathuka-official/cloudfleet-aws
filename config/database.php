<?php

$host = $_SERVER['RDS_HOSTNAME'] ?? '';
$port = $_SERVER['RDS_PORT'] ?? '3306';
$db   = $_SERVER['RDS_DB_NAME'] ?? '';
$user = $_SERVER['RDS_USERNAME'] ?? '';
$pass = $_SERVER['RDS_PASSWORD'] ?? '';

try {

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {

    error_log("Database connection failed: " . $e->getMessage());

    die("Database connection failed.");
}