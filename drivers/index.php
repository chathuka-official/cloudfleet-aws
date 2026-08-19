<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(driver_code LIKE :s OR full_name LIKE :s OR phone LIKE :s OR license_number LIKE :s OR nic_number LIKE :s)";
    $params['s'] = '%' . $search . '%';
}

$allowedStatuses = ['AVAILABLE','ASSIGNED','ON_LEAVE','INACTIVE'];

if (in_array($status, $allowedStatuses, true)) {
    $where[] = "status=:status";
    $params['status'] = $status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM drivers {$whereSql} ORDER BY created_at DESC");
$stmt->execute($params);
$drivers = $stmt->fetchAll();

$stats = $pdo->query("
    SELECT COUNT(*) total,
           COALESCE(SUM(status='AVAILABLE'),0) available,
           COALESCE(SUM(status='ASSIGNED'),0) assigned,
           COALESCE(SUM(license_expiry < CURDATE()),0) expired
    FROM drivers
")->fetch();

page_start('Drivers', 'drivers');
?>

<div class="page-header">
    <div><h1>Drivers</h1><p>Manage drivers, licences and availability.</p></div>
    <a class="btn btn-primary" href="create.php">+ Add Driver</a>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Total</div><div class="stat-number"><?= (int)$stats['total'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Available</div><div class="stat-number"><?= (int)$stats['available'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Assigned</div><div class="stat-number"><?= (int)$stats['assigned'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Expired Licences</div><div class="stat-number"><?= (int)$stats['expired'] ?></div></div>
</div>

<div class="panel">
    <form class="filters" method="GET">
        <input name="search" placeholder="Search driver..." value="<?= e($search) ?>">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach ($allowedStatuses as $s): ?><option value="<?= e($s) ?>" <?= $status===$s?'selected':'' ?>><?= e(str_replace('_',' ',$s)) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-primary">Filter</button>
        <a class="btn btn-secondary" href="index.php">Clear</a>
    </form>

    <?php if (!$drivers): ?>
        <div class="empty-state">No drivers found.</div>
    <?php else: ?>
        <table>
            <thead><tr><th>Driver</th><th>Contact</th><th>Licence</th><th>Expiry</th><th>Status</th><th>Employment</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($drivers as $d): ?>
                <?php
                $today = new DateTime('today');
                $expiry = new DateTime($d['license_expiry']);
                $days = (int)$today->diff($expiry)->format('%r%a');
                ?>
                <tr>
                    <td><div class="entity-name"><?= e($d['full_name']) ?></div><div class="entity-code"><?= e($d['driver_code']) ?></div></td>
                    <td><?= e($d['phone']) ?><div class="entity-code"><?= e($d['email'] ?? '') ?></div></td>
                    <td><?= e($d['license_number']) ?><div class="entity-code"><?= e($d['license_classes'] ?? '') ?></div></td>
                    <td><?= e($d['license_expiry']) ?><div style="margin-top:6px;">
                        <?php if ($days < 0): ?><span class="badge badge-danger">EXPIRED</span>
                        <?php elseif ($days <= 30): ?><span class="badge badge-warning"><?= $days ?> days left</span>
                        <?php else: ?><span class="badge badge-success">VALID</span><?php endif; ?>
                    </div></td>
                    <td><span class="badge <?= status_badge($d['status']) ?>"><?= e(str_replace('_',' ',$d['status'])) ?></span></td>
                    <td><span class="badge <?= status_badge($d['employment_status']) ?>"><?= e($d['employment_status']) ?></span></td>
                    <td><div class="actions">
                        <a class="btn btn-secondary" href="edit.php?id=<?= (int)$d['id'] ?>">Edit</a>
                        <?php if ($d['status'] !== 'ASSIGNED'): ?>
                            <form method="POST" action="delete.php" onsubmit="return confirm('Delete this driver?')">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                <button class="btn btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php page_end(); ?>
