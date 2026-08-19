<?php

require_once __DIR__ . '/../config/database.php';

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    die('Invalid tour.');
}


$stmt = $pdo->prepare("
    SELECT

        t.*,

        v.vehicle_code,
        v.vehicle_name,
        v.registration_number,
        v.vehicle_type,
        v.capacity,

        d.driver_code,
        d.full_name AS driver_name,
        d.phone AS driver_phone,
        d.license_number,
        d.license_expiry

    FROM tours t

    LEFT JOIN tour_assignments a
        ON a.tour_id = t.id

    LEFT JOIN vehicles v
        ON v.id = a.vehicle_id

    LEFT JOIN drivers d
        ON d.id = a.driver_id

    WHERE t.id = ?
");

$stmt->execute([$id]);

$tour = $stmt->fetch();


if (!$tour) {
    die('Tour not found.');
}


$statusClass = match ($tour['status']) {

    'SCHEDULED'
        => 'badge-assigned',

    'IN_PROGRESS'
        => 'badge-maintenance',

    'COMPLETED'
        => 'badge-available',

    default
        => 'badge-inactive'
};

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($tour['tour_code']) ?>
        | CloudFleet
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/app.css"
    >

</head>

<body>


<header class="topbar">

    <div class="brand">
        ☁ CloudFleet
    </div>

    <div class="environment">
        AWS Development Environment
    </div>

</header>


<main class="content">


<div class="page-header">

    <div>

        <a href="index.php">
            ← Back to Tours
        </a>

        <h1>
            <?= htmlspecialchars($tour['title']) ?>
        </h1>

        <p>
            <?= htmlspecialchars($tour['tour_code']) ?>
        </p>

    </div>


    <span class="badge <?= $statusClass ?>">

        <?= str_replace(
            '_',
            ' ',
            htmlspecialchars($tour['status'])
        ) ?>

    </span>

</div>


<div class="stats-grid">


<div class="stat-card">

    <div class="stat-label">
        Destination
    </div>

    <div style="margin-top:10px;font-size:18px;font-weight:600;">

        <?= htmlspecialchars(
            $tour['destination']
        ) ?>

    </div>

</div>


<div class="stat-card">

    <div class="stat-label">
        Passengers
    </div>

    <div class="stat-number">

        <?= (int)$tour['passenger_count'] ?>

    </div>

</div>


<div class="stat-card">

    <div class="stat-label">
        Departure
    </div>

    <div style="margin-top:10px;font-weight:600;">

        <?= date(
            'd M Y H:i',
            strtotime($tour['departure_time'])
        ) ?>

    </div>

</div>


<div class="stat-card">

    <div class="stat-label">
        Return
    </div>

    <div style="margin-top:10px;font-weight:600;">

        <?= date(
            'd M Y H:i',
            strtotime($tour['return_time'])
        ) ?>

    </div>

</div>

</div>


<div class="panel" style="padding:25px;">

    <h2>
        Assigned Vehicle
    </h2>

    <p>

        <strong>
            <?= htmlspecialchars(
                $tour['vehicle_name'] ?? 'Not assigned'
            ) ?>
        </strong>

    </p>

    <?php if ($tour['vehicle_code']): ?>

        <p>
            Code:
            <?= htmlspecialchars($tour['vehicle_code']) ?>
        </p>

        <p>
            Registration:
            <?= htmlspecialchars($tour['registration_number']) ?>
        </p>

        <p>
            Type:
            <?= htmlspecialchars($tour['vehicle_type']) ?>
        </p>

        <p>
            Capacity:
            <?= (int)$tour['capacity'] ?>
        </p>

    <?php endif; ?>


    <hr>


    <h2>
        Assigned Driver
    </h2>

    <p>

        <strong>
            <?= htmlspecialchars(
                $tour['driver_name'] ?? 'Not assigned'
            ) ?>
        </strong>

    </p>

    <?php if ($tour['driver_code']): ?>

        <p>
            Code:
            <?= htmlspecialchars($tour['driver_code']) ?>
        </p>

        <p>
            Phone:
            <?= htmlspecialchars($tour['driver_phone']) ?>
        </p>

        <p>
            Licence:
            <?= htmlspecialchars($tour['license_number']) ?>
        </p>

        <p>
            Licence Expiry:
            <?= htmlspecialchars($tour['license_expiry']) ?>
        </p>

    <?php endif; ?>


    <?php if (!empty($tour['notes'])): ?>

        <hr>

        <h2>
            Notes
        </h2>

        <p>
            <?= nl2br(
                htmlspecialchars($tour['notes'])
            ) ?>
        </p>

    <?php endif; ?>

</div>


</main>

</body>
</html>