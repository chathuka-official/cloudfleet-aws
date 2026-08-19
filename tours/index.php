<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowedStatuses = ['SCHEDULED','IN_PROGRESS','COMPLETED','CANCELLED'];
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(t.tour_code LIKE :s OR t.title LIKE :s OR t.destination LIKE :s OR v.vehicle_name LIKE :s OR d.full_name LIKE :s)";
    $params['s'] = '%' . $search . '%';
}

if (in_array($status, $allowedStatuses, true)) {
    $where[] = "t.status=:status";
    $params['status'] = $status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT t.*, a.vehicle_id, a.driver_id,
           v.vehicle_code, v.vehicle_name, v.registration_number, v.capacity AS vehicle_capacity,
           d.driver_code, d.full_name AS driver_name, d.license_expiry
    FROM tours t
    LEFT JOIN tour_assignments a ON a.tour_id=t.id
    LEFT JOIN vehicles v ON v.id=a.vehicle_id
    LEFT JOIN drivers d ON d.id=a.driver_id
    {$whereSql}
    ORDER BY CASE t.status WHEN 'IN_PROGRESS' THEN 1 WHEN 'SCHEDULED' THEN 2 WHEN 'COMPLETED' THEN 3 ELSE 4 END,
             t.departure_time ASC
");
$stmt->execute($params);
$tours = $stmt->fetchAll();

$stats = $pdo->query("
    SELECT COUNT(*) total,
           COALESCE(SUM(status='SCHEDULED'),0) scheduled,
           COALESCE(SUM(status='IN_PROGRESS'),0) in_progress,
           COALESCE(SUM(status='COMPLETED'),0) completed
    FROM tours
")->fetch();

page_start('Tours', 'tours');
?>

<div class="page-header">
    <div><h1>Tours</h1><p>Plan tours, assign resources and manage operations.</p></div>
    <a class="btn btn-primary" href="create.php">+ Create Tour</a>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Total</div><div class="stat-number"><?= (int)$stats['total'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Scheduled</div><div class="stat-number"><?= (int)$stats['scheduled'] ?></div></div>
    <div class="stat-card"><div class="stat-label">In Progress</div><div class="stat-number"><?= (int)$stats['in_progress'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Completed</div><div class="stat-number"><?= (int)$stats['completed'] ?></div></div>
</div>

<div class="panel">
<form class="filters" method="GET">
    <input name="search" placeholder="Search tour, destination, vehicle or driver..." value="<?= e($search) ?>">
    <select name="status">
        <option value="">All statuses</option>
        <?php foreach ($allowedStatuses as $s): ?><option value="<?= e($s) ?>" <?= $status===$s?'selected':'' ?>><?= e(str_replace('_',' ',$s)) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Filter</button>
    <a class="btn btn-secondary" href="index.php">Clear</a>
</form>

<?php if (!$tours): ?>
    <div class="empty-state">No tours found.</div>
<?php else: ?>
<table>
<thead><tr><th>Tour</th><th>Destination</th><th>Schedule</th><th>Vehicle</th><th>Driver</th><th>Passengers</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($tours as $t): ?>
<tr>
    <td><div class="entity-name"><?= e($t['title']) ?></div><div class="entity-code"><?= e($t['tour_code']) ?></div></td>
    <td><?= e($t['destination']) ?></td>
    <td><strong><?= e(date('d M Y H:i', strtotime($t['departure_time']))) ?></strong><div class="entity-code">to <?= e(date('d M Y H:i', strtotime($t['return_time']))) ?></div></td>
    <td><?= e($t['vehicle_name'] ?? 'Not assigned') ?><div class="entity-code"><?= e($t['vehicle_code'] ?? '') ?></div></td>
    <td><?= e($t['driver_name'] ?? 'Not assigned') ?><div class="entity-code"><?= e($t['driver_code'] ?? '') ?></div></td>
    <td><?= (int)$t['passenger_count'] ?><?php if ($t['vehicle_capacity']): ?><div class="entity-code">/ <?= (int)$t['vehicle_capacity'] ?> seats</div><?php endif; ?></td>
    <td><span class="badge <?= status_badge($t['status']) ?>"><?= e(str_replace('_',' ',$t['status'])) ?></span></td>
    <td><div class="actions">
        <a class="btn btn-secondary" href="view.php?id=<?= (int)$t['id'] ?>">View</a>
        <?php if ($t['status']==='SCHEDULED'): ?>
            <a class="btn btn-secondary" href="edit.php?id=<?= (int)$t['id'] ?>">Edit</a>
            <form method="POST" action="status.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="tour_id" value="<?= (int)$t['id'] ?>">
                <input type="hidden" name="action" value="start">
                <button class="btn btn-primary">Start</button>
            </form>
            <form method="POST" action="status.php" onsubmit="return confirm('Cancel this tour?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="tour_id" value="<?= (int)$t['id'] ?>">
                <input type="hidden" name="action" value="cancel">
                <button class="btn btn-danger">Cancel</button>
            </form>
        <?php elseif ($t['status']==='IN_PROGRESS'): ?>
            <form method="POST" action="status.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="tour_id" value="<?= (int)$t['id'] ?>">
                <input type="hidden" name="action" value="complete">
                <button class="btn btn-primary">Complete</button>
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
