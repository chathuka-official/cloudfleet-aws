<?php

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../app/tour_rules.php';


/*
|--------------------------------------------------------------------------
| TOUR ID
|--------------------------------------------------------------------------
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    die('Invalid tour.');
}


/*
|--------------------------------------------------------------------------
| LOAD TOUR
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        t.*,
        a.vehicle_id,
        a.driver_id

    FROM tours t

    LEFT JOIN tour_assignments a
        ON a.tour_id = t.id

    WHERE t.id = ?
");

$stmt->execute([$id]);

$tour = $stmt->fetch();

if (!$tour) {
    die('Tour not found.');
}


/*
|--------------------------------------------------------------------------
| ONLY SCHEDULED TOURS CAN BE EDITED
|--------------------------------------------------------------------------
*/

if ($tour['status'] !== 'SCHEDULED') {

    die(
        'Only scheduled tours can be edited.'
    );
}


/*
|--------------------------------------------------------------------------
| VEHICLE OPTIONS
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
| DRIVER OPTIONS
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


$error = '';


/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

$form = [

    'tour_code' =>
        $tour['tour_code'],

    'title' =>
        $tour['title'],

    'destination' =>
        $tour['destination'],

    'departure_time' =>
        date(
            'Y-m-d\TH:i',
            strtotime(
                $tour['departure_time']
            )
        ),

    'return_time' =>
        date(
            'Y-m-d\TH:i',
            strtotime(
                $tour['return_time']
            )
        ),

    'passenger_count' =>
        $tour['passenger_count'],

    'vehicle_id' =>
        $tour['vehicle_id'],

    'driver_id' =>
        $tour['driver_id'],

    'notes' =>
        $tour['notes'] ?? ''
];


/*
|--------------------------------------------------------------------------
| UPDATE TOUR
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $form = [

        'tour_code' =>
            trim(
                $_POST['tour_code']
                ?? ''
            ),

        'title' =>
            trim(
                $_POST['title']
                ?? ''
            ),

        'destination' =>
            trim(
                $_POST['destination']
                ?? ''
            ),

        'departure_time' =>
            $_POST['departure_time']
            ?? '',

        'return_time' =>
            $_POST['return_time']
            ?? '',

        'passenger_count' =>
            (int)(
                $_POST['passenger_count']
                ?? 0
            ),

        'vehicle_id' =>
            filter_input(
                INPUT_POST,
                'vehicle_id',
                FILTER_VALIDATE_INT
            ),

        'driver_id' =>
            filter_input(
                INPUT_POST,
                'driver_id',
                FILTER_VALIDATE_INT
            ),

        'notes' =>
            trim(
                $_POST['notes']
                ?? ''
            )
    ];


    if (
        $form['tour_code'] === '' ||
        $form['title'] === '' ||
        $form['destination'] === '' ||
        !$form['vehicle_id'] ||
        !$form['driver_id']
    ) {

        $error =
            'Please complete all required fields.';

    } else {

        try {


            /*
            |--------------------------------------------------------------------------
            | REVALIDATE EVERYTHING
            |--------------------------------------------------------------------------
            */

            $check =
                checkTourAssignment(

                    $pdo,

                    (int)$form['vehicle_id'],

                    (int)$form['driver_id'],

                    $form['departure_time'],

                    $form['return_time'],

                    (int)$form['passenger_count'],

                    $id
                );


            /*
            |--------------------------------------------------------------------------
            | TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | UPDATE TOUR
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE tours

                SET
                    tour_code = :tour_code,
                    title = :title,
                    destination = :destination,
                    departure_time = :departure,
                    return_time = :return_time,
                    passenger_count = :passengers,
                    notes = :notes

                WHERE id = :id
            ");


            $stmt->execute([

                'tour_code' =>
                    $form['tour_code'],

                'title' =>
                    $form['title'],

                'destination' =>
                    $form['destination'],

                'departure' =>
                    $check['departure'],

                'return_time' =>
                    $check['return'],

                'passengers' =>
                    $form['passenger_count'],

                'notes' =>
                    $form['notes']
                    ?: null,

                'id' =>
                    $id
            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE ASSIGNMENT
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE tour_assignments

                SET
                    vehicle_id = :vehicle_id,
                    driver_id = :driver_id

                WHERE tour_id = :tour_id
            ");


            $stmt->execute([

                'vehicle_id' =>
                    $form['vehicle_id'],

                'driver_id' =>
                    $form['driver_id'],

                'tour_id' =>
                    $id
            ]);


            $pdo->commit();


            header(
                'Location: view.php?id=' .
                $id
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
    Edit Tour | CloudFleet
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

    <h1>
        Edit Tour
    </h1>

    <p>
        <?= htmlspecialchars(
            $tour['tour_code']
        ) ?>
    </p>

</div>


<a
    href="view.php?id=<?= $id ?>"
    class="btn btn-secondary"
>
    Cancel
</a>

</div>


<?php if ($error): ?>

<div
    style="
        background:#fee2e2;
        color:#991b1b;
        padding:15px;
        border:1px solid #fecaca;
        border-radius:10px;
        margin-bottom:20px;
    "
>

    <?= htmlspecialchars(
        $error
    ) ?>

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

    value="<?= htmlspecialchars(
        $form['tour_code']
    ) ?>"

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

    value="<?= htmlspecialchars(
        $form['title']
    ) ?>"

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

    value="<?= htmlspecialchars(
        $form['destination']
    ) ?>"

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

    value="<?= htmlspecialchars(
        $form['departure_time']
    ) ?>"

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

    value="<?= htmlspecialchars(
        $form['return_time']
    ) ?>"

    required
>

</p>


<p>

<label>
    Passengers *
</label>

<br>

<input
    type="number"
    name="passenger_count"

    value="<?= (int)
        $form['passenger_count']
    ?>"

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


<?php foreach ($vehicles as $vehicle): ?>


<option

    value="<?= (int)
        $vehicle['id']
    ?>"

    <?=
        (int)$form['vehicle_id']
        ===
        (int)$vehicle['id']

        ? 'selected'
        : ''
    ?>
>

    <?= htmlspecialchars(
        $vehicle['vehicle_code']
    ) ?>

    -

    <?= htmlspecialchars(
        $vehicle['vehicle_name']
    ) ?>

    (<?= (int)
        $vehicle['capacity']
    ?> seats)

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


<?php foreach ($drivers as $driver): ?>


<option

    value="<?= (int)
        $driver['id']
    ?>"

    <?=
        (int)$form['driver_id']
        ===
        (int)$driver['id']

        ? 'selected'
        : ''
    ?>
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
    rows="5"
><?= htmlspecialchars(
    $form['notes']
) ?></textarea>

</p>


<button
    type="submit"
    class="btn btn-primary"
>

    Save Changes

</button>


<a
    href="view.php?id=<?= $id ?>"
    class="btn btn-secondary"
>

    Cancel

</a>


</form>


</div>


</main>


</body>

</html>