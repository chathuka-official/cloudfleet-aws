<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$records = $pdo->query("
    SELECT m.*, v.vehicle_code,v.vehicle_name
    FROM maintenance_records m
    JOIN vehicles v ON v.id=m.vehicle_id
    ORDER BY m.maintenance_date DESC
")->fetchAll();

page_start('Maintenance', 'maintenance');
?>

<div class="page-header">
    <div><h1>Maintenance</h1><p>Vehicle maintenance history and planned work.</p></div>
    <a class="btn btn-primary" href="create.php">+ Maintenance Record</a>
</div>

<div class="panel">
<?php if (!$records): ?>
<div class="empty-state">No maintenance records.</div>
<?php else: ?>
<table>
<thead><tr><th>Vehicle</th><th>Title</th><th>Date</th><th>Cost</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($records as $r): ?>
<tr>
    <td><?= e($r['vehicle_name']) ?><div class="entity-code"><?= e($r['vehicle_code']) ?></div></td>
    <td><div class="entity-name"><?= e($r['title']) ?></div><div class="entity-code"><?= e($r['description'] ?? '') ?></div></td>
    <td><?= e($r['maintenance_date']) ?></td>
    <td><?= $r['cost'] !== null ? 'Rs. '.number_format((float)$r['cost'],2) : '-' ?></td>
    <td><span class="badge <?= status_badge($r['status']) ?>"><?= e(str_replace('_',' ',$r['status'])) ?></span></td>
    <td><a class="btn btn-secondary" href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<?php page_end(); ?>
