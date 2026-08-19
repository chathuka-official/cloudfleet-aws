<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowedStatuses = [
    'SCHEDULED',
    'IN_PROGRESS',
    'COMPLETED',
    'CANCELLED'
];

$where = [];
$params = [];


if ($search !== '') {

    $where[] = "
        (
            t.tour_code LIKE :search1
            OR t.title LIKE :search2
            OR t.destination LIKE :search3
        )
    ";

    $searchValue = '%' . $search . '%';

    $params['search1'] = $searchValue;
    $params['search2'] = $searchValue;
    $params['search3'] = $searchValue;
}


if (
    $status !== '' &&
    in_array($status, $allowedStatuses, true)
) {

    $where[] = "t.status = :status";

    $params['status'] = $status;
}


$whereSql = $where
    ? 'WHERE ' . implode(' AND ', $where)
    : '';


/*
|--------------------------------------------------------------------------
| GET TOURS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT

        t.*,

        v.vehicle_code,
        v.vehicle_name,
        v.registration_number,

        d.driver_code,
        d.full_name AS driver_name

    FROM tours t

    LEFT JOIN tour_assignments a
        ON a.tour_id = t.id

    LEFT JOIN vehicles v
        ON v.id = a.vehicle_id

    LEFT JOIN drivers d
        ON d.id = a.driver_id

    {$whereSql}

    ORDER BY t.departure_time ASC
");

$stmt->execute($params);

$tours = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| STATISTICS
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
        ) AS completed

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

    <title>Tours | CloudFleet</title>

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


<div class="layout">


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

</aside>


<main class="content">


<div class="page-header">

    <div>

        <h1>
            Tours
        </h1>

        <p>
            Plan tours and manage fleet assignments.
        </p>

    </div>


    <a
        href="create.php"
        class="btn btn-primary"
    >
        + Create Tour
    </a>

</div>


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


<div class="panel">


<form
    method="GET"
    class="filters"
>

    <input
        type="text"
        name="search"
        placeholder="Search tour, code or destination..."
        value="<?= htmlspecialchars($search) ?>"
    >


    <select name="status">

        <option value="">
            All statuses
        </option>

        <?php foreach ($allowedStatuses as $option): ?>

        <option
            value="<?= $option ?>"
            <?= $status === $option ? 'selected' : '' ?>
        >

            <?= str_replace('_', ' ', $option) ?>

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


<?php if (!$tours): ?>


<div class="empty-state">

    <h3>
        No tours found
    </h3>

    <p>
        Create your first CloudFleet tour.
    </p>

</div>


<?php else: ?>


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


<tr>


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


<td>

    <?= htmlspecialchars(
        $tour['destination']
    ) ?>

</td>


<td>

    <strong>
        <?= date(
            'd M Y H:i',
            strtotime($tour['departure_time'])
        ) ?>
    </strong>

    <div class="vehicle-code">
        to
    </div>

    <?= date(
        'd M Y H:i',
        strtotime($tour['return_time'])
    ) ?>

</td>


<td>

    <?= htmlspecialchars(
        $tour['vehicle_name'] ?? 'Not assigned'
    ) ?>

    <?php if ($tour['vehicle_code']): ?>

    <div class="vehicle-code">

        <?= htmlspecialchars(
            $tour['vehicle_code']
        ) ?>

    </div>

    <?php endif; ?>

</td>


<td>

    <?= htmlspecialchars(
        $tour['driver_name'] ?? 'Not assigned'
    ) ?>

    <?php if ($tour['driver_code']): ?>

    <div class="vehicle-code">

        <?= htmlspecialchars(
            $tour['driver_code']
        ) ?>

    </div>

    <?php endif; ?>

</td>


<td>

    <?= (int)$tour['passenger_count'] ?>

</td>


<td>

    <span
        class="badge <?= $statusClass ?>"
    >

        <?= str_replace(
            '_',
            ' ',
            htmlspecialchars($tour['status'])
        ) ?>

    </span>

</td>
<td>

<div class="actions">

    <a
        href="view.php?id=<?= (int)$tour['id'] ?>"
        class="btn btn-secondary"
    >
        View
    </a>


    <?php if ($tour['status'] === 'SCHEDULED'): ?>

        <form method="POST" action="status.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
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


        <form
            method="POST"
            action="status.php"
            onsubmit="return confirm('Cancel this tour?');"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
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


    <?php elseif ($tour['status'] === 'IN_PROGRESS'): ?>

        <form method="POST" action="status.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
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


</div>


</main>

</div>


</body>

</html>