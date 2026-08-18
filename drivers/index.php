<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($search !== '') {

    $where[] = "
        (
            driver_code LIKE :search
            OR full_name LIKE :search
            OR phone LIKE :search
            OR license_number LIKE :search
        )
    ";

    $params['search'] = '%' . $search . '%';
}

$allowedStatuses = [
    'AVAILABLE',
    'ASSIGNED',
    'ON_LEAVE',
    'INACTIVE'
];

if (
    $status !== '' &&
    in_array($status, $allowedStatuses, true)
) {

    $where[] = "status = :status";
    $params['status'] = $status;
}

$whereSql = $where
    ? 'WHERE ' . implode(' AND ', $where)
    : '';

$stmt = $pdo->prepare("
    SELECT *
    FROM drivers
    {$whereSql}
    ORDER BY created_at DESC
");

$stmt->execute($params);

$drivers = $stmt->fetchAll();


$stats = $pdo->query("
    SELECT

        COUNT(*) AS total,

        SUM(status = 'AVAILABLE') AS available,

        SUM(status = 'ASSIGNED') AS assigned,

        SUM(status = 'ON_LEAVE') AS on_leave

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

    <title>Drivers | CloudFleet</title>

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

    <div class="sidebar-title">
        FLEET
    </div>

    <a href="../vehicles/">
        Vehicles
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

</aside>


<main class="content">

<div class="page-header">

    <div>

        <h1>Drivers</h1>

        <p>
            Manage drivers and operational availability.
        </p>

    </div>

    <a
        href="create.php"
        class="btn btn-primary"
    >
        + Add Driver
    </a>

</div>


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


<div class="panel">

<form
    method="GET"
    class="filters"
>

    <input
        type="text"
        name="search"
        placeholder="Search driver, phone, licence..."
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


<?php if (!$drivers): ?>

<div class="empty-state">

    No drivers found.

</div>

<?php else: ?>

<table>

<thead>

<tr>

    <th>Driver</th>
    <th>Contact</th>
    <th>Licence</th>
    <th>Expiry</th>
    <th>Status</th>
    <th>Actions</th>

</tr>

</thead>


<tbody>

<?php foreach ($drivers as $driver): ?>

<tr>

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

</td>

<td>

    <?= htmlspecialchars(
        $driver['phone']
    ) ?>

</td>

<td>

    <?= htmlspecialchars(
        $driver['license_number']
    ) ?>

</td>

<td>

    <?= htmlspecialchars(
        $driver['license_expiry']
    ) ?>

</td>

<td>

    <span class="badge">

        <?= htmlspecialchars(
            $driver['status']
        ) ?>

    </span>

</td>

<td>

    <div class="actions">

        <a
            href="edit.php?id=<?= (int)$driver['id'] ?>"
            class="btn btn-secondary"
        >
            Edit
        </a>

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