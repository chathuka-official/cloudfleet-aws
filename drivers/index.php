<?php

session_start();

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| FILTER OPTIONS
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'AVAILABLE',
    'ASSIGNED',
    'ON_LEAVE',
    'INACTIVE'
];

$allowedEmploymentStatuses = [
    'ACTIVE',
    'SUSPENDED',
    'TERMINATED'
];


/*
|--------------------------------------------------------------------------
| GET FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$employment = trim($_GET['employment'] ?? '');


if (
    $status !== '' &&
    !in_array($status, $allowedStatuses, true)
) {
    $status = '';
}


if (
    $employment !== '' &&
    !in_array($employment, $allowedEmploymentStatuses, true)
) {
    $employment = '';
}


/*
|--------------------------------------------------------------------------
| BUILD SEARCH QUERY
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];


if ($search !== '') {

    $where[] = "
        (
            driver_code LIKE :search1
            OR full_name LIKE :search2
            OR nic_number LIKE :search3
            OR phone LIKE :search4
            OR email LIKE :search5
            OR license_number LIKE :search6
        )
    ";

    $searchValue = '%' . $search . '%';

    $params['search1'] = $searchValue;
    $params['search2'] = $searchValue;
    $params['search3'] = $searchValue;
    $params['search4'] = $searchValue;
    $params['search5'] = $searchValue;
    $params['search6'] = $searchValue;
}


if ($status !== '') {

    $where[] = "status = :status";

    $params['status'] = $status;
}


if ($employment !== '') {

    $where[] = "employment_status = :employment";

    $params['employment'] = $employment;
}


$whereSql = '';

if ($where) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
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
| COUNT FILTERED DRIVERS
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM drivers
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
| GET DRIVERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM drivers

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

$drivers = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| DRIVER STATISTICS
|--------------------------------------------------------------------------
*/

$stats = $pdo->query("
    SELECT

        COUNT(*) AS total,

        COALESCE(
            SUM(status = 'AVAILABLE'),
            0
        ) AS available,

        COALESCE(
            SUM(status = 'ASSIGNED'),
            0
        ) AS assigned,

        COALESCE(
            SUM(status = 'ON_LEAVE'),
            0
        ) AS on_leave,

        COALESCE(
            SUM(license_expiry < CURDATE()),
            0
        ) AS expired_licenses

    FROM drivers
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
        Drivers | CloudFleet
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/app.css"
    >

</head>

<body>


<!-- TOP BAR -->

<header class="topbar">

    <div class="brand">
        ☁ CloudFleet
    </div>

    <div class="environment">
        AWS Development Environment
    </div>

</header>


<div class="layout">


<!-- SIDEBAR -->

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

    <a href="../vehicles/">
        Vehicles
    </a>

    <a href="#">
        Maintenance
    </a>


    <div class="sidebar-title">
        PERSONNEL
    </div>

    <a
        href="index.php"
        class="active"
    >
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


<!-- MAIN CONTENT -->

<main class="content">


<!-- PAGE HEADER -->

<div class="page-header">

    <div>

        <h1>
            Drivers
        </h1>

        <p>
            Manage drivers, licences and operational availability.
        </p>

    </div>


    <a
        href="create.php"
        class="btn btn-primary"
    >
        + Add Driver
    </a>

</div>


<!-- STATISTICS -->

<div class="stats-grid">


    <div class="stat-card">

        <div class="stat-label">
            Total Drivers
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
            On Leave
        </div>

        <div class="stat-number">
            <?= (int)$stats['on_leave'] ?>
        </div>

    </div>

</div>


<?php if ((int)$stats['expired_licenses'] > 0): ?>

<div
    style="
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
    "
>

    ⚠

    <?= (int)$stats['expired_licenses'] ?>

    driver licence(s) have expired.

</div>

<?php endif; ?>


<!-- DRIVER PANEL -->

<div class="panel">


<!-- SEARCH & FILTERS -->

<form
    method="GET"
    class="filters"
>


    <input
        type="text"
        name="search"
        placeholder="Search driver, phone, NIC or licence..."
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


    <select name="employment">

        <option value="">
            All employment
        </option>


        <?php foreach ($allowedEmploymentStatuses as $option): ?>

        <option
            value="<?= $option ?>"
            <?= $employment === $option ? 'selected' : '' ?>
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


<!-- DRIVER TABLE -->

<?php if (!$drivers): ?>


<div class="empty-state">

    <h3>
        No drivers found
    </h3>

    <p>
        Try changing your search or filters.
    </p>

</div>


<?php else: ?>


<table>

<thead>

<tr>

    <th>Driver</th>

    <th>Contact</th>

    <th>Licence</th>

    <th>Licence Expiry</th>

    <th>Status</th>

    <th>Employment</th>

    <th>Actions</th>

</tr>

</thead>


<tbody>


<?php foreach ($drivers as $driver): ?>


<?php

/*
|--------------------------------------------------------------------------
| LICENCE EXPIRY CHECK
|--------------------------------------------------------------------------
*/

$today = new DateTime('today');

$expiryDate = new DateTime(
    $driver['license_expiry']
);

$daysRemaining = (int)$today
    ->diff($expiryDate)
    ->format('%r%a');


/*
|--------------------------------------------------------------------------
| DRIVER STATUS BADGE
|--------------------------------------------------------------------------
*/

$statusClass = match ($driver['status']) {

    'AVAILABLE'
        => 'badge-available',

    'ASSIGNED'
        => 'badge-assigned',

    'ON_LEAVE'
        => 'badge-maintenance',

    default
        => 'badge-inactive'
};


/*
|--------------------------------------------------------------------------
| EMPLOYMENT STATUS BADGE
|--------------------------------------------------------------------------
*/

$employmentClass = match (
    $driver['employment_status']
) {

    'ACTIVE'
        => 'badge-available',

    'SUSPENDED'
        => 'badge-maintenance',

    default
        => 'badge-inactive'
};

?>


<tr>


<!-- DRIVER -->

<td>

    <div class="vehicle-name">

        <?= htmlspecialchars(
            $driver['full_name']
        ) ?>

    </div>


    <div class="vehicle-code">

        <?= htmlspecialchars(
            $driver['driver_code']
        ) ?>

    </div>


    <?php if (!empty($driver['nic_number'])): ?>

    <div class="vehicle-code">

        NIC:

        <?= htmlspecialchars(
            $driver['nic_number']
        ) ?>

    </div>

    <?php endif; ?>

</td>


<!-- CONTACT -->

<td>

    <strong>

        <?= htmlspecialchars(
            $driver['phone']
        ) ?>

    </strong>


    <?php if (!empty($driver['email'])): ?>

    <div class="vehicle-code">

        <?= htmlspecialchars(
            $driver['email']
        ) ?>

    </div>

    <?php endif; ?>

</td>


<!-- LICENCE -->

<td>

    <?= htmlspecialchars(
        $driver['license_number']
    ) ?>


    <?php if (!empty($driver['license_classes'])): ?>

    <div class="vehicle-code">

        Classes:

        <?= htmlspecialchars(
            $driver['license_classes']
        ) ?>

    </div>

    <?php endif; ?>

</td>


<!-- LICENCE EXPIRY -->

<td>

    <?= htmlspecialchars(
        $driver['license_expiry']
    ) ?>

    <br><br>


    <?php if ($daysRemaining < 0): ?>


        <span class="badge badge-inactive">

            EXPIRED

        </span>


    <?php elseif ($daysRemaining === 0): ?>


        <span class="badge badge-maintenance">

            EXPIRES TODAY

        </span>


    <?php elseif ($daysRemaining <= 30): ?>


        <span class="badge badge-maintenance">

            <?= $daysRemaining ?> days left

        </span>


    <?php else: ?>


        <span class="badge badge-available">

            VALID

        </span>


    <?php endif; ?>

</td>


<!-- DRIVER STATUS -->

<td>

    <span
        class="badge <?= $statusClass ?>"
    >

        <?= str_replace(
            '_',
            ' ',
            htmlspecialchars($driver['status'])
        ) ?>

    </span>

</td>


<!-- EMPLOYMENT STATUS -->

<td>

    <span
        class="badge <?= $employmentClass ?>"
    >

        <?= htmlspecialchars(
            $driver['employment_status']
        ) ?>

    </span>

</td>


<!-- ACTIONS -->

<td>

<div class="actions">


    <a
        href="edit.php?id=<?= (int)$driver['id'] ?>"
        class="btn btn-secondary"
    >
        Edit
    </a>


    <?php if ($driver['status'] === 'ASSIGNED'): ?>


        <button
            type="button"
            class="btn btn-danger"
            disabled
            title="Assigned drivers cannot be deleted"
            style="opacity: 0.5; cursor: not-allowed;"
        >
            Delete
        </button>


    <?php else: ?>


        <form
            method="POST"
            action="delete.php"
            onsubmit="
                return confirm(
                    'Are you sure you want to delete this driver?'
                );
            "
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int)$driver['id'] ?>"
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


    <?php endif; ?>


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

        'search' => $search,

        'status' => $status,

        'employment' => $employment,

        'page' => $i
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