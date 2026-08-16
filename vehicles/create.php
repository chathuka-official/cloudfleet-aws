<?php

require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $vehicleCode = trim($_POST['vehicle_code'] ?? '');
    $registration = trim($_POST['registration_number'] ?? '');
    $vehicleName = trim($_POST['vehicle_name'] ?? '');
    $vehicleType = $_POST['vehicle_type'] ?? '';
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = $_POST['manufacture_year'] ?? null;
    $capacity = $_POST['capacity'] ?? 0;

    if (
        $vehicleCode === '' ||
        $registration === '' ||
        $vehicleName === '' ||
        $vehicleType === '' ||
        (int)$capacity <= 0
    ) {

        $error = 'Please complete all required fields.';

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO vehicles (
                    vehicle_code,
                    registration_number,
                    vehicle_name,
                    vehicle_type,
                    manufacturer,
                    model,
                    manufacture_year,
                    capacity
                )

                VALUES (
                    :vehicle_code,
                    :registration_number,
                    :vehicle_name,
                    :vehicle_type,
                    :manufacturer,
                    :model,
                    :manufacture_year,
                    :capacity
                )
            ");

            $stmt->execute([
                'vehicle_code' => $vehicleCode,
                'registration_number' => $registration,
                'vehicle_name' => $vehicleName,
                'vehicle_type' => $vehicleType,
                'manufacturer' => $manufacturer ?: null,
                'model' => $model ?: null,
                'manufacture_year' => $year ?: null,
                'capacity' => (int)$capacity
            ]);

            header('Location: index.php');
            exit;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $error = 'Unable to add vehicle.';
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

    <title>Add Vehicle | CloudFleet</title>

</head>

<body>

<h1>Add Vehicle</h1>

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
        <label>Vehicle Code *</label><br>

        <input
            type="text"
            name="vehicle_code"
            placeholder="VEH-0001"
            required
        >
    </p>

    <p>
        <label>Registration Number *</label><br>

        <input
            type="text"
            name="registration_number"
            placeholder="NC-4587"
            required
        >
    </p>

    <p>
        <label>Vehicle Name *</label><br>

        <input
            type="text"
            name="vehicle_name"
            placeholder="Toyota Coaster 01"
            required
        >
    </p>

    <p>
        <label>Vehicle Type *</label><br>

        <select name="vehicle_type" required>

            <option value="">
                Select type
            </option>

            <option value="BUS">Bus</option>

            <option value="VAN">Van</option>

            <option value="CAR">Car</option>

            <option value="SUV">SUV</option>

            <option value="OTHER">Other</option>

        </select>
    </p>

    <p>
        <label>Manufacturer</label><br>

        <input
            type="text"
            name="manufacturer"
            placeholder="Toyota"
        >
    </p>

    <p>
        <label>Model</label><br>

        <input
            type="text"
            name="model"
            placeholder="Coaster"
        >
    </p>

    <p>
        <label>Manufacture Year</label><br>

        <input
            type="number"
            name="manufacture_year"
            min="1900"
            max="2100"
        >
    </p>

    <p>
        <label>Passenger Capacity *</label><br>

        <input
            type="number"
            name="capacity"
            min="1"
            required
        >
    </p>

    <button type="submit">
        Add Vehicle
    </button>

</form>

</body>

</html>