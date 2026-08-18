<?php

require_once __DIR__ . '/../../config/database.php';

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | TOURS
    |--------------------------------------------------------------------------
    */

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tours (

            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            tour_code VARCHAR(30) NOT NULL UNIQUE,

            title VARCHAR(150) NOT NULL,

            destination VARCHAR(150) NOT NULL,

            departure_time DATETIME NOT NULL,

            return_time DATETIME NOT NULL,

            passenger_count SMALLINT UNSIGNED NOT NULL,

            status ENUM(
                'SCHEDULED',
                'IN_PROGRESS',
                'COMPLETED',
                'CANCELLED'
            ) NOT NULL DEFAULT 'SCHEDULED',

            notes TEXT NULL,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP
                DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");


    /*
    |--------------------------------------------------------------------------
    | TOUR ASSIGNMENTS
    |--------------------------------------------------------------------------
    */

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tour_assignments (

            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            tour_id INT UNSIGNED NOT NULL,

            vehicle_id INT UNSIGNED NOT NULL,

            driver_id INT UNSIGNED NOT NULL,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            UNIQUE KEY unique_tour_assignment (tour_id),

            CONSTRAINT fk_assignment_tour
                FOREIGN KEY (tour_id)
                REFERENCES tours(id)
                ON DELETE CASCADE,

            CONSTRAINT fk_assignment_vehicle
                FOREIGN KEY (vehicle_id)
                REFERENCES vehicles(id),

            CONSTRAINT fk_assignment_driver
                FOREIGN KEY (driver_id)
                REFERENCES drivers(id)

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");


    $pdo->commit();

    echo "✅ Tours and assignments tables ready.";

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    die("❌ Tour migration failed.");
}