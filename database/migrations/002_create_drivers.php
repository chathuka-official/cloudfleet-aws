<?php

require_once __DIR__ . '/../../config/database.php';

$sql = "
CREATE TABLE IF NOT EXISTS drivers (

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    driver_code VARCHAR(20) NOT NULL UNIQUE,

    full_name VARCHAR(120) NOT NULL,

    nic_number VARCHAR(30) NULL UNIQUE,

    phone VARCHAR(30) NOT NULL,

    email VARCHAR(120) NULL,

    license_number VARCHAR(50) NOT NULL UNIQUE,

    license_expiry DATE NOT NULL,

    license_classes VARCHAR(100) NULL,

    status ENUM(
        'AVAILABLE',
        'ASSIGNED',
        'ON_LEAVE',
        'INACTIVE'
    ) NOT NULL DEFAULT 'AVAILABLE',

    employment_status ENUM(
        'ACTIVE',
        'SUSPENDED',
        'TERMINATED'
    ) NOT NULL DEFAULT 'ACTIVE',

    emergency_contact_name VARCHAR(120) NULL,

    emergency_contact_phone VARCHAR(30) NULL,

    notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
";

try {

    $pdo->exec($sql);

    echo "Drivers table ready.";

} catch (PDOException $e) {

    error_log($e->getMessage());

    die("Driver migration failed.");
}