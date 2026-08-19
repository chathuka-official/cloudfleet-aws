<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$assignments = $pdo->query("
    SELECT t.id,t.tour_code,t.title,t.destination,t.departure_time,t.return_time,t.status,
           v.vehicle_code,v.vehicle_name,
           d.driver_code,d.full_name AS driver_name
    FROM tour_assignments a
    JOIN tours t ON t.id=a.tour_id
    JOIN vehicles v ON v.id=a.vehicle_id
    JOIN drivers d ON d.id=a.driver_id
    ORDER BY t.departure_time DESC
")->fetchAll();

$vehicleUtil = $pdo->query("
    SELECT v.vehicle_code,v.vehicle_name,
           COALESCE(SUM(t.status IN ('SCHEDULED','IN_PROGRESS')),0) upcoming
    FROM vehicles v
    LEFT JOIN tour_assignments a ON a.vehicle_id=v.id
    LEFT JOIN tours t ON t.id=a.tour_id
    GROUP BY v.id
    ORDER BY upcoming DESC, v.vehicle_name
    LIMIT 8
")->fetchAll();

$driverUtil = $pdo->query("
    SELECT d.driver_code,d.full_name,
           COALESCE(SUM(t.status IN ('SCHEDULED','IN_PROGRESS')),0) upcoming
    FROM drivers d
    LEFT JOIN tour_assignments a ON a.driver_id=d.id
    LEFT JOIN tours t ON t.id=a.tour_id
    GROUP BY d.id
    ORDER BY upcoming DESC, d.full_name
    LIMIT 8
")->fetchAll();

page_start('Assignments', 'assignments');
?>

<div class="page-header"><div><h1>Assignments</h1><p>Resource utilization across tours.</p></div></div>

<div class="detail-grid" style="padding:0;margin-bottom:22px;">
    <div class="panel" style="padding:20px;">
        <h2>Vehicle Utilization</h2>
        <?php foreach ($vehicleUtil as $v): ?>
            <p><strong><?= e($v['vehicle_code']) ?></strong> — <?= e($v['vehicle_name']) ?> <span class="badge <?= $v['upcoming'] ? 'badge-info':'badge-success' ?>"><?= (int)$v['upcoming'] ?> active</span></p>
        <?php endforeach; ?>
    </div>

    <div class="panel" style="padding:20px;">
        <h2>Driver Workload</h2>
        <?php foreach ($driverUtil as $d): ?>
            <p><strong><?= e($d['driver_code']) ?></strong> — <?= e($d['full_name']) ?> <span class="badge <?= $d['upcoming'] ? 'badge-info':'badge-success' ?>"><?= (int)$d['upcoming'] ?> active</span></p>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel">
<?php if (!$assignments): ?>
    <div class="empty-state">No assignments yet.</div>
<?php else: ?>
<table>
<thead><tr><th>Tour</th><th>Vehicle</th><th>Driver</th><th>Departure</th><th>Return</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($assignments as $a): ?>
<tr>
    <td><div class="entity-name"><?= e($a['title']) ?></div><div class="entity-code"><?= e($a['tour_code'].' • '.$a['destination']) ?></div></td>
    <td><?= e($a['vehicle_name']) ?><div class="entity-code"><?= e($a['vehicle_code']) ?></div></td>
    <td><?= e($a['driver_name']) ?><div class="entity-code"><?= e($a['driver_code']) ?></div></td>
    <td><?= e(date('d M Y H:i', strtotime($a['departure_time']))) ?></td>
    <td><?= e(date('d M Y H:i', strtotime($a['return_time']))) ?></td>
    <td><span class="badge <?= status_badge($a['status']) ?>"><?= e(str_replace('_',' ',$a['status'])) ?></span></td>
    <td><a class="btn btn-secondary" href="../tours/view.php?id=<?= (int)$a['id'] ?>">View</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<?php page_end(); ?>
