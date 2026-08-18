<?php

require_once __DIR__ . '/../config/database.php';

$error = '';


/*
|--------------------------------------------------------------------------
| GET VEHICLES
|--------------------------------------------------------------------------
*/

$vehicles = $pdo->query("
    SELECT *
    FROM vehicles
    WHERE status NOT IN (
        'MAINTENANCE',
        'INACTIVE'
    )
    ORDER BY vehicle_name
")->fetchAll();


/*
|--------------------------------------------------------------------------
| GET DRIVERS
|--------------------------------------------------------------------------
*/

$drivers = $pdo->query("
    SELECT *
    FROM drivers
    WHERE
        status NOT IN (
            'ON_LEAVE',
            'INACTIVE'
        )

        AND employment_status = 'ACTIVE'

    ORDER BY full_name
")->fetchAll();


/*
|--------------------------------------------------------------------------
| CREATE TOUR
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tourCode =
        trim($_POST['tour_code'] ?? '');

    $title =
        trim($_POST['title'] ?? '');

    $destination =
        trim($_POST['destination'] ?? '');

    $departure =
        $_POST['departure_time'] ?? '';

    $return =
        $_POST['return_time'] ?? '';

    $passengers =
        (int)($_POST['passenger_count'] ?? 0);

    $vehicleId =
        filter_input(
            INPUT_POST,
            'vehicle_id',
            FILTER_VALIDATE_INT
        );

    $driverId =
        filter_input(
            INPUT_POST,
            'driver_id',
            FILTER_VALIDATE_INT
        );

    $notes =
        trim($_POST['notes'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $tourCode === '' ||
        $title === '' ||
        $destination === '' ||
        $departure === '' ||
        $return === '' ||
        $passengers <= 0 ||
        !$vehicleId ||
        !$driverId
    ) {

        $error =
            'Please complete all required fields.';

    } elseif (
        strtotime($return) <=
        strtotime($departure)
    ) {

        $error =
            'Return time must be after departure time.';

    } else {

        try {


            /*
            |--------------------------------------------------------------------------
            | LOAD VEHICLE
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT *
                FROM vehicles
                WHERE id = ?
            ");

            $stmt->execute([$vehicleId]);

            $vehicle = $stmt->fetch();


            if (!$vehicle) {

                throw new Exception(
                    'Selected vehicle does not exist.'
                );
            }


            if (
                in_array(
                    $vehicle['status'],
                    [
                        'MAINTENANCE',
                        'INACTIVE'
                    ],
                    true
                )
            ) {

                throw new Exception(
                    'Selected vehicle is not operational.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CAPACITY CHECK
            |--------------------------------------------------------------------------
            */

            if (
                $passengers >
                (int)$vehicle['capacity']
            ) {

                throw new Exception(
                    'Passenger count exceeds vehicle capacity.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | LOAD DRIVER
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT *
                FROM drivers
                WHERE id = ?
            ");

            $stmt->execute([$driverId]);

            $driver = $stmt->fetch();


            if (!$driver) {

                throw new Exception(
                    'Selected driver does not exist.'
                );
            }


            if (
                $driver['employment_status']
                !== 'ACTIVE'
            ) {

                throw new Exception(
                    'Selected driver is not actively employed.'
                );
            }


            if (
                in_array(
                    $driver['status'],
                    [
                        'ON_LEAVE',
                        'INACTIVE'
                    ],
                    true
                )
            ) {

                throw new Exception(
                    'Selected driver is currently unavailable.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | DRIVER LICENCE CHECK
            |--------------------------------------------------------------------------
            */

            $tripEndDate =
                date(
                    'Y-m-d',
                    strtotime($return)
                );


            if (
                $driver['license_expiry']
                < $tripEndDate
            ) {

                throw new Exception(
                    'Driver licence expires before this tour is completed.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | VEHICLE SCHEDULE CONFLICT
            |--------------------------------------------------------------------------
            |
            | Existing:
            |    start < new return
            |
            | AND
            |
            |    existing return > new start
            |
            | means the two tours overlap.
            |
            */

            $stmt = $pdo->prepare("
                SELECT
                    t.tour_code,
                    t.title,
                    t.departure_time,
                    t.return_time

                FROM tours t

                INNER JOIN tour_assignments a
                    ON a.tour_id = t.id

                WHERE
                    a.vehicle_id = :vehicle_id

                    AND t.status IN (
                        'SCHEDULED',
                        'IN_PROGRESS'
                    )

                    AND t.departure_time < :return_time

                    AND t.return_time > :departure_time

                LIMIT 1
            ");

            $stmt->execute([

                'vehicle_id' =>
                    $vehicleId,

                'return_time' =>
                    $return,

                'departure_time' =>
                    $departure

            ]);

            $vehicleConflict =
                $stmt->fetch();


            if ($vehicleConflict) {

                throw new Exception(
                    'Vehicle already has an overlapping tour: ' .
                    $vehicleConflict['tour_code'] .
                    ' - ' .
                    $vehicleConflict['title']
                );
            }


            /*
            |--------------------------------------------------------------------------
            | DRIVER SCHEDULE CONFLICT
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    t.tour_code,
                    t.title,
                    t.departure_time,
                    t.return_time

                FROM tours t

                INNER JOIN tour_assignments a
                    ON a.tour_id = t.id

                WHERE
                    a.driver_id = :driver_id

                    AND t.status IN (
                        'SCHEDULED',
                        'IN_PROGRESS'
                    )

                    AND t.departure_time < :return_time

                    AND t.return_time > :departure_time

                LIMIT 1
            ");

            $stmt->execute([

                'driver_id' =>
                    $driverId,

                'return_time' =>
                    $return,

                'departure_time' =>
                    $departure

            ]);

            $driverConflict =
                $stmt->fetch();


            if ($driverConflict) {

                throw new Exception(
                    'Driver already has an overlapping tour: ' .
                    $driverConflict['tour_code'] .
                    ' - ' .
                    $driverConflict['title']
                );
            }


            /*
            |--------------------------------------------------------------------------
            | DATABASE TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | CREATE TOUR
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO tours (
                    tour_code,
                    title,
                    destination,
                    departure_time,
                    return_time,
                    passenger_count,
                    notes
                )

                VALUES (
                    :tour_code,
                    :title,
                    :destination,
                    :departure_time,
                    :return_time,
                    :passenger_count,
                    :notes
                )
            ");


            $stmt->execute([

                'tour_code' =>
                    $tourCode,

                'title' =>
                    $title,

                'destination' =>
                    $destination,

                'departure_time' =>
                    $departure,

                'return_time' =>
                    $return,

                'passenger_count' =>
                    $passengers,

                'notes' =>
                    $notes ?: null

            ]);


            $tourId =
                (int)$pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | CREATE ASSIGNMENT
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO tour_assignments (
                    tour_id,
                    vehicle_id,
                    driver_id
                )

                VALUES (
                    :tour_id,
                    :vehicle_id,
                    :driver_id
                )
            ");


            $stmt->execute([

                'tour_id' =>
                    $tourId,

                'vehicle_id' =>
                    $vehicleId,

                'driver_id' =>
                    $driverId

            ]);


            $pdo->commit();


            header(
                'Location: index.php'
            );

            exit;


        } catch (Throwable $e) {


            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }


            error_log(
                $e->getMessage()
            );


            $error =
                $e->getMessage();
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

    <title>
        Create Tour | CloudFleet
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/app.css"
    >

</head>

<body>


<main class="content">


<div class="page-header">

    <div>

        <h1>
            Create Tour
        </h1>

        <p>
            Schedule a tour and assign fleet resources.
        </p>

    </div>

</div>


<?php if ($error): ?>


<div
    style="
        background:#fee2e2;
        border:1px solid #fecaca;
        color:#991b1b;
        padding:15px;
        border-radius:10px;
        margin-bottom:20px;
    "
>

    <?= htmlspecialchars($error) ?>

</div>


<?php endif; ?>


<div class="panel">


<form
    method="POST"
    style="padding:25px;"
>


<p>

    <label>
        Tour Code *
    </label>

    <br>

    <input
        type="text"
        name="tour_code"
        placeholder="TR-2026-0001"
        required
    >

</p>


<p>

    <label>
        Tour Title *
    </label>

    <br>

    <input
        type="text"
        name="title"
        placeholder="NSBM Kandy Trip"
        required
    >

</p>


<p>

    <label>
        Destination *
    </label>

    <br>

    <input
        type="text"
        name="destination"
        placeholder="Kandy"
        required
    >

</p>


<p>

    <label>
        Departure *
    </label>

    <br>

    <input
        type="datetime-local"
        name="departure_time"
        required
    >

</p>


<p>

    <label>
        Return *
    </label>

    <br>

    <input
        type="datetime-local"
        name="return_time"
        required
    >

</p>


<p>

    <label>
        Passenger Count *
    </label>

    <br>

    <input
        type="number"
        name="passenger_count"
        min="1"
        required
    >

</p>


<p>

    <label>
        Vehicle *
    </label>

    <br>

    <select
        name="vehicle_id"
        required
    >

        <option value="">
            Select vehicle
        </option>


        <?php foreach ($vehicles as $vehicle): ?>


        <option
            value="<?= (int)$vehicle['id'] ?>"
        >

            <?= htmlspecialchars(
                $vehicle['vehicle_code']
            ) ?>

            -

            <?= htmlspecialchars(
                $vehicle['vehicle_name']
            ) ?>

            (

            <?= (int)$vehicle['capacity'] ?>

            seats)

        </option>


        <?php endforeach; ?>


    </select>

</p>


<p>

    <label>
        Driver *
    </label>

    <br>

    <select
        name="driver_id"
        required
    >

        <option value="">
            Select driver
        </option>


        <?php foreach ($drivers as $driver): ?>


        <option
            value="<?= (int)$driver['id'] ?>"
        >

            <?= htmlspecialchars(
                $driver['driver_code']
            ) ?>

            -

            <?= htmlspecialchars(
                $driver['full_name']
            ) ?>

        </option>


        <?php endforeach; ?>


    </select>

</p>


<p>

    <label>
        Notes
    </label>

    <br>

    <textarea
        name="notes"
        rows="4"
    ></textarea>

</p>


<button
    type="submit"
    class="btn btn-primary"
>
    Create Tour
</button>


<a
    href="index.php"
    class="btn btn-secondary"
>
    Cancel
</a>


</form>


</div>


</main>


</body>

</html>