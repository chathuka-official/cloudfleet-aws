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
| FILTERS
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$type   = trim($_GET['type'] ?? '');

$allowedStatuses = [
    'AVAILABLE',
    'ASSIGNED',
    'MAINTENANCE',
    'INACTIVE'
];

$allowedTypes = [
    'BUS',
    'VAN',
    'CAR',
    'SUV',
    'OTHER'
];

if (
    $status !== '' &&
    !in_array($status, $allowedStatuses, true)
) {
    $status = '';
}

if (
    $type !== '' &&
    !in_array($type, $allowedTypes, true)
) {
    $type = '';
}


/*
|--------------------------------------------------------------------------
| BUILD WHERE CLAUSE
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if ($search !== '') {

    $where[] = "
        (
            vehicle_code LIKE :search
            OR registration_number LIKE :search
            OR vehicle_name LIKE :search
            OR manufacturer LIKE :search
            OR model LIKE :search
        )
    ";

    $params['search'] = '%' . $search . '%';
}

if ($status !== '') {

    $where[] = "status = :status";

    $params['status'] = $status;
}

if ($type !== '') {

    $where[] = "vehicle_type = :type";

    $params['type'] = $type;
}

$whereSql = '';

if ($where) {

    $whereSql = 'WHERE ' . implode(
        ' AND ',
        $where
    );
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
| COUNT FILTERED VEHICLES
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM vehicles
    {$whereSql}
");

$countStmt->execute($params);

$totalFiltered = (int)$countStmt->fetchColumn();

$totalPages = max(
    1,
    (int)ceil($totalFiltered / $perPage)
);

if ($page > $totalPages) {

    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| GET VEHICLES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM vehicles

    {$whereSql}

    ORDER BY created_at DESC

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

$vehicles = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| VEHICLE STATISTICS
|--------------------------------------------------------------------------
*/

$stats = $pdo->query("
    SELECT

        COUNT(*) AS total,

        SUM(
            status = 'AVAILABLE'
        ) AS available,

        SUM(
            status = 'ASSIGNED'
        ) AS assigned,

        SUM(
            status = 'MAINTENANCE'
        ) AS maintenance

    FROM vehicles
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
        Vehicles | CloudFleet
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

    <a href="#">
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

    <a
        href="index.php"
        class="active"
    >
        Vehicles
    </a>

    <a href="#">
        Maintenance
    </a>


    <div class="sidebar-title">
        PERSONNEL
    </div>

    <a href="#">
        Drivers
    </a>

</aside>


<main class="content">


<div class="page-header">

    <div>

        <h1>
            Vehicles
        </h1>

        <p>
            Manage the CloudFleet vehicle fleet.
        </p>

    </div>

    <a
        href="create.php"
        class="btn btn-primary"
    >
        + Add Vehicle
    </a>

</div>


<!-- STATISTICS -->

<div class="stats-grid">

    <div class="stat-card">

        <div class="stat-label">
            Total Vehicles
        </div>

        <div class="stat-number">

            <?= (int)$stats['total'] ?>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Available
        </div>

        <div class="stat-number">

            <?= (int)$stats['available'] ?>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Assigned
        </div>

        <div class="stat-number">

            <?= (int)$stats['assigned'] ?>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-label">
            Maintenance
        </div>

        <div class="stat-number">

            <?= (int)$stats['maintenance'] ?>

        </div>

    </div>

</div>


<div class="panel">


<!-- SEARCH / FILTERS -->

<form
    method="GET"
    class="filters"
>

    <input
        type="text"
        name="search"
        placeholder="Search name, code, registration..."
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
                <?= $option ?>
            </option>

        <?php endforeach; ?>

    </select>


    <select name="type">

        <option value="">
            All vehicle types
        </option>

        <?php foreach ($allowedTypes as $option): ?>

            <option
                value="<?= $option ?>"
                <?= $type === $option ? 'selected' : '' ?>
            >
                <?= $option ?>
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


<?php if (!$vehicles): ?>


<div class="empty-state">

    No vehicles found.

</div>


<?php else: ?>


<table>

<thead>

<tr>

    <th>Vehicle</th>

    <th>Registration</th>

    <th>Type</th>

    <th>Capacity</th>

    <th>Status</th>

    <th>Actions</th>

</tr>

</thead>


<tbody>


<?php foreach ($vehicles as $vehicle): ?>


<tr>

<td>

    <div class="vehicle-name">

        <?= htmlspecialchars(
            $vehicle['vehicle_name']
        ) ?>

    </div>

    <div class="vehicle-code">

        <?= htmlspecialchars(
            $vehicle['vehicle_code']
        ) ?>

    </div>

</td>


<td>

    <?= htmlspecialchars(
        $vehicle['registration_number']
    ) ?>

</td>


<td>

    <?= htmlspecialchars(
        $vehicle['vehicle_type']
    ) ?>

</td>


<td>

    <?= (int)$vehicle['capacity'] ?>

</td>


<td>

<?php

$statusClass = match (
    $vehicle['status']
) {

    'AVAILABLE'
        => 'badge-available',

    'ASSIGNED'
        => 'badge-assigned',

    'MAINTENANCE'
        => 'badge-maintenance',

    default
        => 'badge-inactive'
};

?>

<span
    class="badge <?= $statusClass ?>"
>

    <?= htmlspecialchars(
        $vehicle['status']
    ) ?>

</span>

</td>


<td>

<div class="actions">

<a
    href="edit.php?id=<?= (int)$vehicle['id'] ?>"
    class="btn btn-secondary"
>
    Edit
</a>


<form
    method="POST"
    action="delete.php"

    onsubmit="
        return confirm(
            'Are you sure you want to delete this vehicle?'
        );
    "
>

<input
    type="hidden"
    name="id"
    value="<?= (int)$vehicle['id'] ?>"
>

<input
    type="hidden"
    name="csrf_token"

    value="<?= htmlspecialchars(
        $_SESSION['csrf_token']
    ) ?>"
>


<button
    type="submit"
    class="btn btn-danger"
>
    Delete
</button>

</form>

</div>

</td>

</tr>


<?php endforeach; ?>


</tbody>

</table>


<?php endif; ?>


<!-- PAGINATION -->

<?php if ($totalPages > 1): ?>


<div class="pagination">

<div>

    Page
    <?= $page ?>
    of
    <?= $totalPages ?>

</div>


<div class="pagination-links">


<?php

for (
    $i = 1;
    $i <= $totalPages;
    $i++
):

$query = http_build_query([
    'search' => $search,
    'status' => $status,
    'type'   => $type,
    'page'   => $i
]);

?>


<a
    href="?<?= htmlspecialchars($query) ?>"

    class="<?= $i === $page ? 'active' : '' ?>"
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