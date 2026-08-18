<?php

require_once __DIR__ . '/../../config/database.php';

try {

    /*
    |--------------------------------------------------------------------------
    | CREATE TOURS TABLE
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

            created_at TIMESTAMP
                DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP
                DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");


    /*
    |--------------------------------------------------------------------------
    | CREATE TOUR ASSIGNMENTS TABLE
    |--------------------------------------------------------------------------
    */

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tour_assignments (

            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            tour_id INT UNSIGNED NOT NULL,

            vehicle_id INT UNSIGNED NOT NULL,

            driver_id INT UNSIGNED NOT NULL,

            created_at TIMESTAMP
                DEFAULT CURRENT_TIMESTAMP,

            UNIQUE KEY unique_tour_assignment (
                tour_id
            ),

            INDEX idx_assignment_vehicle (
                vehicle_id
            ),

            INDEX idx_assignment_driver (
                driver_id
            ),

            CONSTRAINT fk_assignment_tour

                FOREIGN KEY (tour_id)

                REFERENCES tours(id)

                ON DELETE CASCADE,


            CONSTRAINT fk_assignment_vehicle

                FOREIGN KEY (vehicle_id)

                REFERENCES vehicles(id)

                ON DELETE RESTRICT,


            CONSTRAINT fk_assignment_driver

                FOREIGN KEY (driver_id)

                REFERENCES drivers(id)

                ON DELETE RESTRICT

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");


    echo "✅ Tours and assignments tables ready.";


} catch (PDOException $e) {

    error_log(
        "Tour migration error: " .
        $e->getMessage()
    );

    echo "❌ Tour migration failed.<br>";

    /*
     * TEMPORARY DEBUG ONLY.
     * Remove this after the migration works.
     */

    echo htmlspecialchars(
        $e->getMessage()
    );
}