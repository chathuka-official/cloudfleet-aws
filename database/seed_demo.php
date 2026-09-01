<?php

require_once __DIR__ . '/../config/database.php';

$allowed = ($_SERVER['ALLOW_DEMO_SEED'] ?? getenv('ALLOW_DEMO_SEED') ?: '') === '1';

if (!$allowed) {
    http_response_code(403);
    die('Demo seeding is disabled. Set ALLOW_DEMO_SEED=1 temporarily in Elastic Beanstalk.');
}

function upsert(PDO $pdo, string $sql, array $rows): void
{
    $stmt = $pdo->prepare($sql);
    foreach ($rows as $row) {
        $stmt->execute($row);
    }
}

function idByCode(PDO $pdo, string $table, string $column, string $code): int
{
    $allowed = [
        'vehicles' => 'vehicle_code',
        'drivers' => 'driver_code',
        'tours' => 'tour_code',
    ];

    if (!isset($allowed[$table]) || $allowed[$table] !== $column) {
        throw new RuntimeException('Invalid lookup.');
    }

    $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE {$column} = ? LIMIT 1");
    $stmt->execute([$code]);
    $id = $stmt->fetchColumn();

    if (!$id) {
        throw new RuntimeException("Could not find {$code}.");
    }

    return (int)$id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Remove only the obvious placeholder tour used while testing the UI.
        $stmt = $pdo->prepare("DELETE FROM tours WHERE title = ? OR destination = ?");
        $stmt->execute(['fwfef', 'rf4weffw']);

        $vehicles = [
            ['VEH-0001', 'CF-1001', 'Toyota Coaster 01', 'BUS', 'Toyota', 'Coaster', 2022, 22, 'ASSIGNED', 'Main vehicle used for group tours.'],
            ['VEH-0002', 'CF-1002', 'Toyota HiAce 01', 'VAN', 'Toyota', 'HiAce', 2021, 15, 'AVAILABLE', 'Used for small group tours and transfers.'],
            ['VEH-0003', 'CF-1003', 'Mitsubishi Montero 01', 'SUV', 'Mitsubishi', 'Montero', 2020, 7, 'AVAILABLE', 'Used for smaller long-distance tours.'],
            ['VEH-0004', 'CF-1004', 'Nissan Caravan 01', 'VAN', 'Nissan', 'Caravan', 2019, 12, 'MAINTENANCE', 'Temporarily unavailable for scheduled maintenance.'],
        ];

        upsert($pdo, "
            INSERT INTO vehicles
                (vehicle_code, registration_number, vehicle_name, vehicle_type, manufacturer, model, manufacture_year, capacity, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                registration_number = VALUES(registration_number),
                vehicle_name = VALUES(vehicle_name),
                vehicle_type = VALUES(vehicle_type),
                manufacturer = VALUES(manufacturer),
                model = VALUES(model),
                manufacture_year = VALUES(manufacture_year),
                capacity = VALUES(capacity),
                status = VALUES(status),
                notes = VALUES(notes)
        ", $vehicles);

        $drivers = [
            ['DRV-0001', 'Kasun Perera', null, '0700001001', 'kasun.perera@example.com', 'CF-DEMO-LIC-001', '2031-06-15', 'B, D', 'ASSIGNED', 'ACTIVE', 'Nimal Perera', '0700091001', 'Demo driver profile.'],
            ['DRV-0002', 'Nuwan Silva', null, '0700001002', 'nuwan.silva@example.com', 'CF-DEMO-LIC-002', '2030-11-20', 'B, D', 'AVAILABLE', 'ACTIVE', 'Saman Silva', '0700091002', 'Demo driver profile.'],
            ['DRV-0003', 'Dilshan Fernando', null, '0700001003', 'dilshan.fernando@example.com', 'CF-DEMO-LIC-003', '2032-02-08', 'B, B1, D', 'AVAILABLE', 'ACTIVE', 'Ruwan Fernando', '0700091003', 'Demo driver profile.'],
            ['DRV-0004', 'Tharindu Jayasinghe', null, '0700001004', 'tharindu.j@example.com', 'CF-DEMO-LIC-004', '2029-08-12', 'B, D', 'ON_LEAVE', 'ACTIVE', 'Sunil Jayasinghe', '0700091004', 'Demo driver profile.'],
            ['DRV-0005', 'Akila Madushan', null, '0700001005', 'akila.madushan@example.com', 'CF-DEMO-LIC-005', '2030-04-25', 'B, D', 'AVAILABLE', 'ACTIVE', 'Janaka Madushan', '0700091005', 'Demo driver profile.'],
        ];

        upsert($pdo, "
            INSERT INTO drivers
                (driver_code, full_name, nic_number, phone, email, license_number, license_expiry, license_classes, status, employment_status, emergency_contact_name, emergency_contact_phone, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name),
                nic_number = VALUES(nic_number),
                phone = VALUES(phone),
                email = VALUES(email),
                license_number = VALUES(license_number),
                license_expiry = VALUES(license_expiry),
                license_classes = VALUES(license_classes),
                status = VALUES(status),
                employment_status = VALUES(employment_status),
                emergency_contact_name = VALUES(emergency_contact_name),
                emergency_contact_phone = VALUES(emergency_contact_phone),
                notes = VALUES(notes)
        ", $drivers);

        $tours = [
            ['TR-2026-001', 'Kandy Day Tour', 'Kandy', '2026-09-05 07:00:00', '2026-09-05 20:00:00', 18, 'SCHEDULED', 'Temple of the Tooth and city sightseeing.'],
            ['TR-2026-002', 'Galle Heritage Tour', 'Galle', '2026-08-24 06:30:00', '2026-08-24 19:30:00', 12, 'COMPLETED', 'Galle Fort and southern coast day trip.'],
            ['TR-2026-003', 'Ella Scenic Tour', 'Ella', '2026-09-07 05:30:00', '2026-09-07 21:30:00', 6, 'SCHEDULED', 'Nine Arch Bridge and Ella sightseeing.'],
            ['TR-2026-004', 'Sigiriya & Dambulla Tour', 'Sigiriya', '2026-08-18 05:00:00', '2026-08-18 21:00:00', 16, 'COMPLETED', 'Sigiriya Rock Fortress and Dambulla cave temple.'],
            ['TR-2026-005', 'Negombo Airport Transfer', 'Negombo', '2026-08-30 10:00:00', '2026-08-30 13:00:00', 5, 'CANCELLED', 'Demo cancelled transfer.'],
            ['TR-2026-006', 'Colombo City Tour', 'Colombo', '2026-09-12 08:00:00', '2026-09-12 18:00:00', 9, 'SCHEDULED', 'Colombo city highlights and waterfront.'],
        ];

        upsert($pdo, "
            INSERT INTO tours
                (tour_code, title, destination, departure_time, return_time, passenger_count, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                destination = VALUES(destination),
                departure_time = VALUES(departure_time),
                return_time = VALUES(return_time),
                passenger_count = VALUES(passenger_count),
                status = VALUES(status),
                notes = VALUES(notes)
        ", $tours);

        $assignmentMap = [
            ['TR-2026-001', 'VEH-0001', 'DRV-0001'],
            ['TR-2026-002', 'VEH-0002', 'DRV-0002'],
            ['TR-2026-003', 'VEH-0003', 'DRV-0003'],
            ['TR-2026-004', 'VEH-0001', 'DRV-0001'],
            ['TR-2026-006', 'VEH-0002', 'DRV-0005'],
        ];

        $assignmentStmt = $pdo->prepare("
            INSERT INTO tour_assignments (tour_id, vehicle_id, driver_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                vehicle_id = VALUES(vehicle_id),
                driver_id = VALUES(driver_id)
        ");

        foreach ($assignmentMap as [$tourCode, $vehicleCode, $driverCode]) {
            $assignmentStmt->execute([
                idByCode($pdo, 'tours', 'tour_code', $tourCode),
                idByCode($pdo, 'vehicles', 'vehicle_code', $vehicleCode),
                idByCode($pdo, 'drivers', 'driver_code', $driverCode),
            ]);
        }

        // Refresh only maintenance rows that this demo seeder owns.
        $pdo->exec("DELETE FROM maintenance_records WHERE description LIKE 'Portfolio demo data.%'");

        $maintenanceStmt = $pdo->prepare("
            INSERT INTO maintenance_records
                (vehicle_id, title, description, maintenance_date, cost, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $maintenanceRows = [
            ['VEH-0004', 'Brake inspection', 'Portfolio demo data. Planned brake and safety inspection.', '2026-09-03', 18000.00, 'PLANNED'],
            ['VEH-0002', 'Engine oil & filter', 'Portfolio demo data. Routine oil and filter replacement.', '2026-08-20', 24500.00, 'COMPLETED'],
            ['VEH-0001', 'Tyre rotation', 'Portfolio demo data. Routine tyre rotation and pressure check.', '2026-08-15', 8500.00, 'COMPLETED'],
        ];

        foreach ($maintenanceRows as [$vehicleCode, $title, $description, $date, $cost, $status]) {
            $maintenanceStmt->execute([
                idByCode($pdo, 'vehicles', 'vehicle_code', $vehicleCode),
                $title,
                $description,
                $date,
                $cost,
                $status,
            ]);
        }

        $pdo->commit();

        $counts = [
            'Vehicles' => (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn(),
            'Drivers' => (int)$pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn(),
            'Tours' => (int)$pdo->query("SELECT COUNT(*) FROM tours")->fetchColumn(),
            'Assignments' => (int)$pdo->query("SELECT COUNT(*) FROM tour_assignments")->fetchColumn(),
            'Maintenance records' => (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records")->fetchColumn(),
        ];

        echo '<!doctype html><meta charset="utf-8"><title>CloudFleet Demo Seed</title>';
        echo '<style>body{font-family:Arial,sans-serif;max-width:720px;margin:50px auto;padding:20px;line-height:1.5} .ok{padding:16px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px} code{background:#f3f4f6;padding:2px 5px;border-radius:4px}</style>';
        echo '<h1>CloudFleet demo data loaded</h1>';
        echo '<div class="ok">The RDS database now has clean portfolio demo data.</div>';
        echo '<ul>';
        foreach ($counts as $label => $count) {
            echo '<li>' . htmlspecialchars($label) . ': ' . $count . '</li>';
        }
        echo '</ul>';
        echo '<p><strong>Next:</strong> remove <code>ALLOW_DEMO_SEED</code> from Elastic Beanstalk and delete <code>database/seed_demo.php</code> from the repository.</p>';
        echo '<p><a href="../index.php">Open CloudFleet dashboard</a></p>';
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Demo seed failed: ' . $e->getMessage());
        http_response_code(500);
        die('Demo seed failed. Check the application logs.');
    }
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CloudFleet Demo Seed</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f6f7fb;color:#111827;margin:0;padding:40px 20px}
        .card{max-width:680px;margin:auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:28px}
        button{background:#4f46e5;color:white;border:0;border-radius:8px;padding:12px 18px;font-weight:700;cursor:pointer}
        p{color:#4b5563;line-height:1.6}
    </style>
</head>
<body>
<div class="card">
    <h1>Load CloudFleet demo data</h1>
    <p>This will add/update a small set of fictional vehicles, drivers, tours, assignments and maintenance records for portfolio screenshots. It does not delete S3 documents.</p>
    <form method="post" onsubmit="return confirm('Load the demo data into CloudFleet RDS?');">
        <button type="submit">Load demo data</button>
    </form>
</div>
</body>
</html>
