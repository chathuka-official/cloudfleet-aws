<?php

session_start();

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );
}


/*
|--------------------------------------------------------------------------
| FILTER OPTIONS
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'SCHEDULED',
    'IN_PROGRESS',
    'COMPLETED',
    'CANCELLED'
];


/*
|--------------------------------------------------------------------------
| GET FILTERS
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');


if (
    $status !== '' &&
    !in_array($status, $allowedStatuses, true)
) {

    $status = '';
}


/*
|--------------------------------------------------------------------------
| BUILD WHERE
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];


if ($search !== '') {

    $where[] = "
        (
            t.tour_code LIKE :search1
            OR t.title LIKE :search2
            OR t.destination LIKE :search3
            OR v.vehicle_name LIKE :search4
            OR v.vehicle_code LIKE :search5
            OR d.full_name LIKE :search6
            OR d.driver_code LIKE :search7
        )
    ";

    $searchValue = '%' . $search . '%';

    $params['search1'] = $searchValue;
    $params['search2'] = $searchValue;
    $params['search3'] = $searchValue;
    $params['search4'] = $searchValue;
    $params['search5'] = $searchValue;
    $params['search6'] = $searchValue;
    $params['search7'] = $searchValue;
}


if ($status !== '') {

    $where[] = "t.status = :status";

    $params['status'] = $status;
}


$whereSql = '';

if ($where) {

    $whereSql =
        'WHERE ' .
        implode(' AND ', $where);
}


/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$perPage = 10;

$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);


if (!$page || $page < 1) {

    $page = 1;
}


/*
|--------------------------------------------------------------------------
| COUNT FILTERED TOURS
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*)

    FROM tours t

    LEFT JOIN tour_assignments a
        ON a.tour_id = t.id

    LEFT JOIN vehicles v
        ON v.id = a.vehicle_id

    LEFT JOIN drivers d
        ON d.id = a.driver_id

    {$whereSql}
";


$countStmt = $pdo->prepare($countSql);

$countStmt->execute($params);

$totalFiltered =
    (int)$countStmt->fetchColumn();


$totalPages = max(
    1,
    (int)ceil(
        $totalFiltered / $perPage
    )
);


if ($page > $totalPages) {

    $page = $totalPages;
}


$offset =
    ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| GET TOURS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        t.*,

        a.vehicle_id,
        a.driver_id,

        v.vehicle_code,
        v.vehicle_name,
        v.registration_number,
        v.vehicle_type,
        v.capacity AS vehicle_capacity,

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

    {$whereSql}

    ORDER BY

        CASE t.status

            WHEN 'IN_PROGRESS' THEN 1
            WHEN 'SCHEDULED' THEN 2
            WHEN 'COMPLETED' THEN 3
            WHEN 'CANCELLED' THEN 4

        END,

        t.departure_time ASC

    LIMIT :limit
    OFFSET :offset
";


$stmt = $pdo->prepare($sql);


foreach ($params as $key => $value) {

    $stmt->bindValue(
        ':' . $key,
        $value
    );
}


$stmt->bindValue(
    ':limit',
    $perPage,
    PDO::PARAM_INT
);


$stmt->bindValue(
    ':offset',
    $offset,
    PDO::PARAM_INT
);


$stmt->execute();

$tours = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

$stats = $pdo->query("
    SELECT

        COUNT(*) AS total,

        COALESCE(
            SUM(status = 'SCHEDULED'),
            0
        ) AS scheduled,

        COALESCE(
            SUM(status = 'IN_PROGRESS'),
            0
        ) AS in_progress,

        COALESCE(
            SUM(status = 'COMPLETED'),
            0
        ) AS completed,

        COALESCE(
            SUM(status = 'CANCELLED'),
            0
        ) AS cancelled

    FROM tours
")->fetch();

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
        Tours | CloudFleet
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/app.css"
    >

</head>


<body>


<!-- =========================================================
     TOP BAR
========================================================= -->

<header class="topbar">

    <div class="brand">

        ☁ CloudFleet

    </div>


    <div class="environment">

        AWS Development Environment

    </div>

</header>



<div class="layout">


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">


    <div class="sidebar-title">

        OVERVIEW

    </div>


    <a href="../index.php">

        Dashboard

    </a>



    <div class="sidebar-title">

        OPERATIONS

    </div>


    <a
        href="index.php"
        class="active"
    >

        Tours

    </a>


    <a href="#">

        Assignments

    </a>


    <a href="#">

        Schedule

    </a>



    <div class="sidebar-title">

        FLEET

    </div>


    <a href="../vehicles/">

        Vehicles

    </a>


    <a href="#">

        Maintenance

    </a>



    <div class="sidebar-title">

        PERSONNEL

    </div>


    <a href="../drivers/">

        Drivers

    </a>



    <div class="sidebar-title">

        ADMINISTRATION

    </div>


    <a href="#">

        Users

    </a>


    <a href="#">

        Settings

    </a>


</aside>



<!-- =========================================================
     CONTENT
========================================================= -->

<main class="content">


<!-- PAGE HEADER -->

<div class="page-header">


    <div>

        <h1>
            Tours
        </h1>

        <p>
            Plan tours, assign resources and manage operations.
        </p>

    </div>


    <a
        href="create.php"
        class="btn btn-primary"
    >

        + Create Tour

    </a>


</div>



<!-- =========================================================
     STATISTICS
========================================================= -->

<div class="stats-grid">


    <div class="stat-card">

        <div class="stat-label">

            Total Tours

        </div>


        <div class="stat-number">

            <?= (int)$stats['total'] ?>

        </div>

    </div>



    <div class="stat-card">

        <div class="stat-label">

            Scheduled

        </div>


        <div class="stat-number">

            <?= (int)$stats['scheduled'] ?>

        </div>

    </div>



    <div class="stat-card">

        <div class="stat-label">

            In Progress

        </div>


        <div class="stat-number">

            <?= (int)$stats['in_progress'] ?>

        </div>

    </div>



    <div class="stat-card">

        <div class="stat-label">

            Completed

        </div>


        <div class="stat-number">

            <?= (int)$stats['completed'] ?>

        </div>

    </div>


</div>



<!-- =========================================================
     MAIN PANEL
========================================================= -->

<div class="panel">


<!-- SEARCH -->

<form
    method="GET"
    class="filters"
>


    <input
        type="text"
        name="search"

        placeholder="Search tour, destination, vehicle or driver..."

        value="<?= htmlspecialchars($search) ?>"
    >



    <select name="status">


        <option value="">

            All statuses

        </option>


        <?php foreach ($allowedStatuses as $option): ?>


            <option
                value="<?= $option ?>"

                <?= $status === $option
                    ? 'selected'
                    : ''
                ?>
            >

                <?= str_replace(
                    '_',
                    ' ',
                    $option
                ) ?>

            </option>


        <?php endforeach; ?>


    </select>



    <button
        type="submit"
        class="btn btn-primary"
    >

        Filter

    </button>



    <a
        href="index.php"
        class="btn btn-secondary"
    >

        Clear

    </a>


</form>



<!-- =========================================================
     EMPTY STATE
========================================================= -->

<?php if (!$tours): ?>


<div class="empty-state">


    <h3>
        No tours found
    </h3>


    <p>
        Create a tour or change the current filters.
    </p>


    <br>


    <a
        href="create.php"
        class="btn btn-primary"
    >

        + Create First Tour

    </a>


</div>



<?php else: ?>



<!-- =========================================================
     TOUR TABLE
========================================================= -->

<table>


<thead>


<tr>

    <th>Tour</th>

    <th>Destination</th>

    <th>Schedule</th>

    <th>Vehicle</th>

    <th>Driver</th>

    <th>Passengers</th>

    <th>Status</th>

    <th>Actions</th>

</tr>


</thead>



<tbody>



<?php foreach ($tours as $tour): ?>


<?php


/*
|--------------------------------------------------------------------------
| STATUS BADGE
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| DRIVER LICENCE STATUS
|--------------------------------------------------------------------------
*/

$driverLicenceWarning = false;


if (
    !empty($tour['license_expiry']) &&
    !empty($tour['return_time'])
) {

    $tourReturnDate =
        date(
            'Y-m-d',
            strtotime(
                $tour['return_time']
            )
        );


    if (
        $tour['license_expiry']
        < $tourReturnDate
    ) {

        $driverLicenceWarning = true;
    }
}


?>


<tr>



<!-- TOUR -->

<td>


    <div class="vehicle-name">

        <?= htmlspecialchars(
            $tour['title']
        ) ?>

    </div>


    <div class="vehicle-code">

        <?= htmlspecialchars(
            $tour['tour_code']
        ) ?>

    </div>


</td>



<!-- DESTINATION -->

<td>


    <?= htmlspecialchars(
        $tour['destination']
    ) ?>


</td>



<!-- SCHEDULE -->

<td>


    <strong>

        <?= date(
            'd M Y H:i',
            strtotime(
                $tour['departure_time']
            )
        ) ?>

    </strong>


    <div class="vehicle-code">

        to

    </div>


    <?= date(
        'd M Y H:i',
        strtotime(
            $tour['return_time']
        )
    ) ?>


</td>



<!-- VEHICLE -->

<td>


<?php if (!empty($tour['vehicle_name'])): ?>


    <div class="vehicle-name">

        <?= htmlspecialchars(
            $tour['vehicle_name']
        ) ?>

    </div>


    <div class="vehicle-code">

        <?= htmlspecialchars(
            $tour['vehicle_code']
        ) ?>

    </div>


    <div class="vehicle-code">

        <?= htmlspecialchars(
            $tour['registration_number']
        ) ?>

    </div>


<?php else: ?>


    <span class="badge badge-inactive">

        NOT ASSIGNED

    </span>


<?php endif; ?>


</td>



<!-- DRIVER -->

<td>


<?php if (!empty($tour['driver_name'])): ?>


    <div class="vehicle-name">

        <?= htmlspecialchars(
            $tour['driver_name']
        ) ?>

    </div>


    <div class="vehicle-code">

        <?= htmlspecialchars(
            $tour['driver_code']
        ) ?>

    </div>


    <?php if ($driverLicenceWarning): ?>


        <div style="margin-top:6px;">

            <span class="badge badge-inactive">

                LICENCE WARNING

            </span>

        </div>


    <?php endif; ?>


<?php else: ?>


    <span class="badge badge-inactive">

        NOT ASSIGNED

    </span>


<?php endif; ?>


</td>



<!-- PASSENGERS -->

<td>


    <strong>

        <?= (int)$tour['passenger_count'] ?>

    </strong>


    <?php if (
        !empty($tour['vehicle_capacity'])
    ): ?>


        <div class="vehicle-code">

            / <?= (int)$tour['vehicle_capacity'] ?>
            seats

        </div>


    <?php endif; ?>


</td>



<!-- STATUS -->

<td>


    <span
        class="badge <?= $statusClass ?>"
    >

        <?= str_replace(
            '_',
            ' ',
            htmlspecialchars(
                $tour['status']
            )
        ) ?>

    </span>


</td>



<!-- =========================================================
     ACTIONS
========================================================= -->

<td>


<div class="actions">



<!-- VIEW -->

<a
    href="view.php?id=<?= (int)$tour['id'] ?>"

    class="btn btn-secondary"
>

    View

</a>



<!-- =====================================================
     SCHEDULED TOUR ACTIONS
===================================================== -->

<?php if (
    $tour['status'] === 'SCHEDULED'
): ?>



<!-- EDIT -->

<a
    href="edit.php?id=<?= (int)$tour['id'] ?>"

    class="btn btn-secondary"
>

    Edit

</a>



<!-- START -->

<form
    method="POST"
    action="status.php"
>


    <input
        type="hidden"
        name="csrf_token"

        value="<?= htmlspecialchars(
            $_SESSION['csrf_token']
        ) ?>"
    >


    <input
        type="hidden"
        name="tour_id"

        value="<?= (int)$tour['id'] ?>"
    >


    <input
        type="hidden"
        name="action"

        value="start"
    >


    <button
        type="submit"
        class="btn btn-primary"
    >

        Start

    </button>


</form>



<!-- CANCEL -->

<form
    method="POST"
    action="status.php"

    onsubmit="
        return confirm(
            'Are you sure you want to cancel this tour?'
        );
    "
>


    <input
        type="hidden"
        name="csrf_token"

        value="<?= htmlspecialchars(
            $_SESSION['csrf_token']
        ) ?>"
    >


    <input
        type="hidden"
        name="tour_id"

        value="<?= (int)$tour['id'] ?>"
    >


    <input
        type="hidden"
        name="action"

        value="cancel"
    >


    <button
        type="submit"
        class="btn btn-danger"
    >

        Cancel

    </button>


</form>



<!-- =====================================================
     IN PROGRESS
===================================================== -->

<?php elseif (
    $tour['status'] === 'IN_PROGRESS'
): ?>



<form
    method="POST"
    action="status.php"

    onsubmit="
        return confirm(
            'Mark this tour as completed?'
        );
    "
>


    <input
        type="hidden"
        name="csrf_token"

        value="<?= htmlspecialchars(
            $_SESSION['csrf_token']
        ) ?>"
    >


    <input
        type="hidden"
        name="tour_id"

        value="<?= (int)$tour['id'] ?>"
    >


    <input
        type="hidden"
        name="action"

        value="complete"
    >


    <button
        type="submit"
        class="btn btn-primary"
    >

        Complete

    </button>


</form>



<?php endif; ?>


</div>


</td>



</tr>



<?php endforeach; ?>



</tbody>


</table>



<?php endif; ?>



<!-- =========================================================
     PAGINATION
========================================================= -->

<?php if ($totalPages > 1): ?>


<div class="pagination">


    <div>

        Showing page

        <strong>
            <?= $page ?>
        </strong>

        of

        <strong>
            <?= $totalPages ?>
        </strong>

    </div>



    <div class="pagination-links">


        <?php

        for (
            $i = 1;
            $i <= $totalPages;
            $i++
        ):

            $query = http_build_query([

                'search' =>
                    $search,

                'status' =>
                    $status,

                'page' =>
                    $i

            ]);

        ?>


        <a
            href="?<?= htmlspecialchars(
                $query
            ) ?>"

            class="<?= $i === $page
                ? 'active'
                : ''
            ?>"
        >

            <?= $i ?>

        </a>


        <?php endfor; ?>


    </div>


</div>


<?php endif; ?>


</div>


</main>


</div>


</body>


</html>