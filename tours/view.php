<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) die('Invalid tour.');

$stmt = $pdo->prepare("
    SELECT t.*,
           v.vehicle_code,v.vehicle_name,v.registration_number,v.vehicle_type,v.capacity,
           d.driver_code,d.full_name AS driver_name,d.phone AS driver_phone,d.license_number,d.license_expiry
    FROM tours t
    LEFT JOIN tour_assignments a ON a.tour_id=t.id
    LEFT JOIN vehicles v ON v.id=a.vehicle_id
    LEFT JOIN drivers d ON d.id=a.driver_id
    WHERE t.id=?
");
$stmt->execute([$id]);
$tour = $stmt->fetch();

if (!$tour) die('Tour not found.');

page_start('Tour Details', 'tours');
?>

<div class="page-header">
    <div><h1><?= e($tour['title']) ?></h1><p><?= e($tour['tour_code']) ?></p></div>
    <div class="actions">
        <?php if ($tour['status']==='SCHEDULED'): ?><a class="btn btn-secondary" href="edit.php?id=<?= (int)$tour['id'] ?>">Edit</a><?php endif; ?>
        <a class="btn btn-secondary" href="index.php">Back</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Destination</div><div style="font-size:18px;font-weight:800;margin-top:8px;"><?= e($tour['destination']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Passengers</div><div class="stat-number"><?= (int)$tour['passenger_count'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Departure</div><div style="font-weight:800;margin-top:8px;"><?= e(date('d M Y H:i', strtotime($tour['departure_time']))) ?></div></div>
    <div class="stat-card"><div class="stat-label">Status</div><div style="margin-top:10px;"><span class="badge <?= status_badge($tour['status']) ?>"><?= e(str_replace('_',' ',$tour['status'])) ?></span></div></div>
</div>

<div class="panel">
<div class="detail-grid">
    <div class="detail-card">
        <h3>Assigned Vehicle</h3>
        <p><strong><?= e($tour['vehicle_name'] ?? 'Not assigned') ?></strong></p>
        <p>Code: <?= e($tour['vehicle_code'] ?? '') ?></p>
        <p>Registration: <?= e($tour['registration_number'] ?? '') ?></p>
        <p>Capacity: <?= (int)($tour['capacity'] ?? 0) ?></p>
    </div>

    <div class="detail-card">
        <h3>Assigned Driver</h3>
        <p><strong><?= e($tour['driver_name'] ?? 'Not assigned') ?></strong></p>
        <p>Code: <?= e($tour['driver_code'] ?? '') ?></p>
        <p>Phone: <?= e($tour['driver_phone'] ?? '') ?></p>
        <p>Licence: <?= e($tour['license_number'] ?? '') ?></p>
    </div>

    <div class="detail-card">
        <h3>Return</h3>
        <p><?= e(date('d M Y H:i', strtotime($tour['return_time']))) ?></p>
    </div>

    <div class="detail-card">
        <h3>Notes</h3>
        <p><?= nl2br(e($tour['notes'] ?? 'No notes')) ?></p>
    </div>
</div>
</div>

<?php page_end(); ?>
