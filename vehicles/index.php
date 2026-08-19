<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$type = trim($_GET['type'] ?? '');

$allowedStatuses = ['AVAILABLE','ASSIGNED','MAINTENANCE','INACTIVE'];
$allowedTypes = ['BUS','VAN','CAR','SUV','OTHER'];

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(vehicle_code LIKE :s OR registration_number LIKE :s OR vehicle_name LIKE :s OR manufacturer LIKE :s OR model LIKE :s)";
    $params['s'] = '%' . $search . '%';
}

if (in_array($status, $allowedStatuses, true)) {
    $where[] = "status = :status";
    $params['status'] = $status;
}

if (in_array($type, $allowedTypes, true)) {
    $where[] = "vehicle_type = :type";
    $params['type'] = $type;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM vehicles {$whereSql} ORDER BY created_at DESC");
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

$stats = $pdo->query("
    SELECT COUNT(*) total,
           COALESCE(SUM(status='AVAILABLE'),0) available,
           COALESCE(SUM(status='ASSIGNED'),0) assigned,
           COALESCE(SUM(status='MAINTENANCE'),0) maintenance
    FROM vehicles
")->fetch();

page_start('Vehicles', 'vehicles');
?>

<div class="page-header">
    <div><h1>Vehicles</h1><p>Manage fleet resources and availability.</p></div>
    <a class="btn btn-primary" href="create.php">+ Add Vehicle</a>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Total</div><div class="stat-number"><?= (int)$stats['total'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Available</div><div class="stat-number"><?= (int)$stats['available'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Assigned</div><div class="stat-number"><?= (int)$stats['assigned'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Maintenance</div><div class="stat-number"><?= (int)$stats['maintenance'] ?></div></div>
</div>

<div class="panel">
    <form class="filters" method="GET">
        <input name="search" placeholder="Search vehicle..." value="<?= e($search) ?>">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach ($allowedStatuses as $s): ?><option value="<?= e($s) ?>" <?= $status===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
        <select name="type">
            <option value="">All types</option>
            <?php foreach ($allowedTypes as $t): ?><option value="<?= e($t) ?>" <?= $type===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-primary">Filter</button>
        <a class="btn btn-secondary" href="index.php">Clear</a>
    </form>

    <?php if (!$vehicles): ?>
        <div class="empty-state">No vehicles found.</div>
    <?php else: ?>
        <table>
            <thead><tr><th>Vehicle</th><th>Registration</th><th>Type</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td><div class="entity-name"><?= e($v['vehicle_name']) ?></div><div class="entity-code"><?= e($v['vehicle_code']) ?></div></td>
                    <td><?= e($v['registration_number']) ?></td>
                    <td><?= e($v['vehicle_type']) ?></td>
                    <td><?= (int)$v['capacity'] ?></td>
                    <td><span class="badge <?= status_badge($v['status']) ?>"><?= e($v['status']) ?></span></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-secondary" href="edit.php?id=<?= (int)$v['id'] ?>">Edit</a>
                            <?php if ($v['status'] !== 'ASSIGNED'): ?>
                                <form method="POST" action="delete.php" onsubmit="return confirm('Delete this vehicle?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                    <button class="btn btn-danger">Delete</button>
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

<?php page_end(); ?>
