<?php

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Invalid vehicle.');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM vehicles
    WHERE id = ?
");

$stmt->execute([$id]);

$vehicle = $stmt->fetch();

if (!$vehicle) {
    die('Vehicle not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $vehicleCode = trim($_POST['vehicle_code'] ?? '');
    $registration = trim($_POST['registration_number'] ?? '');
    $vehicleName = trim($_POST['vehicle_name'] ?? '');
    $vehicleType = $_POST['vehicle_type'] ?? '';
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = $_POST['manufacture_year'] ?? null;
    $capacity = (int)($_POST['capacity'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowedTypes = [
        'BUS',
        'VAN',
        'CAR',
        'SUV',
        'OTHER'
    ];

    $allowedStatuses = [
        'AVAILABLE',
        'ASSIGNED',
        'MAINTENANCE',
        'INACTIVE'
    ];

    if (
        $vehicleCode === '' ||
        $registration === '' ||
        $vehicleName === '' ||
        $capacity <= 0 ||
        !in_array($vehicleType, $allowedTypes, true) ||
        !in_array($status, $allowedStatuses, true)
    ) {
        $error = 'Please enter valid vehicle information.';
    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE vehicles

                SET
                    vehicle_code = :vehicle_code,
                    registration_number = :registration,
                    vehicle_name = :vehicle_name,
                    vehicle_type = :vehicle_type,
                    manufacturer = :manufacturer,
                    model = :model,
                    manufacture_year = :year,
                    capacity = :capacity,
                    status = :status

                WHERE id = :id
            ");

            $stmt->execute([
                'vehicle_code' => $vehicleCode,
                'registration' => $registration,
                'vehicle_name' => $vehicleName,
                'vehicle_type' => $vehicleType,
                'manufacturer' => $manufacturer ?: null,
                'model' => $model ?: null,
                'year' => $year ?: null,
                'capacity' => $capacity,
                'status' => $status,
                'id' => $id
            ]);

            header('Location: index.php');
            exit;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $error = 'Unable to update vehicle.';
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Vehicle | CloudFleet</title>

</head>

<body>

<h1>Edit Vehicle</h1>

<p>
    <a href="index.php">
        ← Back to Vehicles
    </a>
</p>

<?php if ($error): ?>

    <p>
        <?= htmlspecialchars($error) ?>
    </p>

<?php endif; ?>

<form method="POST">

    <p>
        <label>Vehicle Code</label><br>

        <input
            type="text"
            name="vehicle_code"
            value="<?= htmlspecialchars($vehicle['vehicle_code']) ?>"
            required
        >
    </p>

    <p>
        <label>Registration Number</label><br>

        <input
            type="text"
            name="registration_number"
            value="<?= htmlspecialchars($vehicle['registration_number']) ?>"
            required
        >
    </p>

    <p>
        <label>Vehicle Name</label><br>

        <input
            type="text"
            name="vehicle_name"
            value="<?= htmlspecialchars($vehicle['vehicle_name']) ?>"
            required
        >
    </p>

    <p>
        <label>Vehicle Type</label><br>

        <select name="vehicle_type" required>

            <?php

            $types = [
                'BUS',
                'VAN',
                'CAR',
                'SUV',
                'OTHER'
            ];

            foreach ($types as $type):

            ?>

                <option
                    value="<?= $type ?>"
                    <?= $vehicle['vehicle_type'] === $type ? 'selected' : '' ?>
                >
                    <?= $type ?>
                </option>

            <?php endforeach; ?>

        </select>
    </p>

    <p>
        <label>Manufacturer</label><br>

        <input
            type="text"
            name="manufacturer"
            value="<?= htmlspecialchars($vehicle['manufacturer'] ?? '') ?>"
        >
    </p>

    <p>
        <label>Model</label><br>

        <input
            type="text"
            name="model"
            value="<?= htmlspecialchars($vehicle['model'] ?? '') ?>"
        >
    </p>

    <p>
        <label>Manufacture Year</label><br>

        <input
            type="number"
            name="manufacture_year"
            min="1900"
            max="2100"
            value="<?= htmlspecialchars($vehicle['manufacture_year'] ?? '') ?>"
        >
    </p>

    <p>
        <label>Capacity</label><br>

        <input
            type="number"
            name="capacity"
            min="1"
            value="<?= (int)$vehicle['capacity'] ?>"
            required
        >
    </p>

    <p>
        <label>Status</label><br>

        <select name="status">

            <?php

            $statuses = [
                'AVAILABLE',
                'ASSIGNED',
                'MAINTENANCE',
                'INACTIVE'
            ];

            foreach ($statuses as $status):

            ?>

                <option
                    value="<?= $status ?>"
                    <?= $vehicle['status'] === $status ? 'selected' : '' ?>
                >
                    <?= $status ?>
                </option>

            <?php endforeach; ?>

        </select>
    </p>

    <button type="submit">
        Save Changes
    </button>

</form>

</body>

</html>