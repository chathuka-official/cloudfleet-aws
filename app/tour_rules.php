<?php

function checkTourAssignment(
    PDO $pdo,
    int $vehicleId,
    int $driverId,
    string $departure,
    string $return,
    int $passengers,
    ?int $ignoreTourId = null
): array {
    $departureTime = strtotime($departure);
    $returnTime = strtotime($return);

    if (!$departureTime || !$returnTime) {
        throw new Exception('Invalid tour date or time.');
    }

    if ($returnTime <= $departureTime) {
        throw new Exception('Return time must be after departure time.');
    }

    if ($passengers <= 0) {
        throw new Exception('Passenger count must be greater than zero.');
    }

    $departureSql = date('Y-m-d H:i:s', $departureTime);
    $returnSql = date('Y-m-d H:i:s', $returnTime);

    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        throw new Exception('Selected vehicle does not exist.');
    }

    if (in_array($vehicle['status'], ['MAINTENANCE', 'INACTIVE'], true)) {
        throw new Exception('Selected vehicle is not operational.');
    }

    if ($passengers > (int)$vehicle['capacity']) {
        throw new Exception('Passenger count exceeds vehicle capacity.');
    }

    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$driverId]);
    $driver = $stmt->fetch();

    if (!$driver) {
        throw new Exception('Selected driver does not exist.');
    }

    if ($driver['employment_status'] !== 'ACTIVE') {
        throw new Exception('Selected driver is not actively employed.');
    }

    if (in_array($driver['status'], ['ON_LEAVE', 'INACTIVE'], true)) {
        throw new Exception('Selected driver is unavailable.');
    }

    $tripEndDate = date('Y-m-d', $returnTime);

    if ($driver['license_expiry'] < $tripEndDate) {
        throw new Exception('Driver licence expires before this tour finishes.');
    }

    $vehicleSql = "
        SELECT t.id, t.tour_code, t.title
        FROM tours t
        INNER JOIN tour_assignments a ON a.tour_id = t.id
        WHERE a.vehicle_id = ?
          AND t.status IN ('SCHEDULED', 'IN_PROGRESS')
          AND t.departure_time < ?
          AND t.return_time > ?
    ";

    $vehicleParams = [$vehicleId, $returnSql, $departureSql];

    if ($ignoreTourId !== null) {
        $vehicleSql .= " AND t.id <> ?";
        $vehicleParams[] = $ignoreTourId;
    }

    $vehicleSql .= " LIMIT 1";

    $stmt = $pdo->prepare($vehicleSql);
    $stmt->execute($vehicleParams);

    if ($conflict = $stmt->fetch()) {
        throw new Exception(
            'Vehicle conflict with ' .
            $conflict['tour_code'] .
            ' - ' .
            $conflict['title']
        );
    }

    $driverSql = "
        SELECT t.id, t.tour_code, t.title
        FROM tours t
        INNER JOIN tour_assignments a ON a.tour_id = t.id
        WHERE a.driver_id = ?
          AND t.status IN ('SCHEDULED', 'IN_PROGRESS')
          AND t.departure_time < ?
          AND t.return_time > ?
    ";

    $driverParams = [$driverId, $returnSql, $departureSql];

    if ($ignoreTourId !== null) {
        $driverSql .= " AND t.id <> ?";
        $driverParams[] = $ignoreTourId;
    }

    $driverSql .= " LIMIT 1";

    $stmt = $pdo->prepare($driverSql);
    $stmt->execute($driverParams);

    if ($conflict = $stmt->fetch()) {
        throw new Exception(
            'Driver conflict with ' .
            $conflict['tour_code'] .
            ' - ' .
            $conflict['title']
        );
    }

    return [
        'vehicle' => $vehicle,
        'driver' => $driver,
        'departure' => $departureSql,
        'return' => $returnSql
    ];
}
