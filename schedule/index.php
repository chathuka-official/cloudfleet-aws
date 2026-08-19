<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$view = $_GET['view'] ?? 'week';
if (!in_array($view, ['today','week','month','all'], true)) $view = 'week';

$vehicleId = filter_input(INPUT_GET, 'vehicle_id', FILTER_VALIDATE_INT);
$driverId = filter_input(INPUT_GET, 'driver_id', FILTER_VALIDATE_INT);

$where = ["t.status IN ('SCHEDULED','IN_PROGRESS')"];
$params = [];

$today = new DateTime('today');

if ($view !== 'all') {
    $days = match ($view) {
        'today' => 1,
        'week' => 7,
        'month' => 30,
        default => 7
    };

    $end = (clone $today)->modify("+{$days} days");
    $where[] = "t.departure_time >= :start_date AND t.departure_time < :end_date";
    $params['start_date'] = $today->format('Y-m-d H:i:s');
    $params['end_date'] = $end->format('Y-m-d H:i:s');
}

if ($vehicleId) {
    $where[] = "a.vehicle_id=:vehicle_id";
    $params['vehicle_id'] = $vehicleId;
}

if ($driverId) {
    $where[] = "a.driver_id=:driver_id";
    $params['driver_id'] = $driverId;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT t.*, v.vehicle_code,v.vehicle_name,d.driver_code,d.full_name AS driver_name
    FROM tours t
    JOIN tour_assignments a ON a.tour_id=t.id
    JOIN vehicles v ON v.id=a.vehicle_id
    JOIN drivers d ON d.id=a.driver_id
    {$whereSql}
    ORDER BY t.departure_time ASC
");
$stmt->execute($params);
$tours = $stmt->fetchAll();

$vehicles = $pdo->query("SELECT id,vehicle_code,vehicle_name FROM vehicles ORDER BY vehicle_name")->fetchAll();
$drivers = $pdo->query("SELECT id,driver_code,full_name FROM drivers ORDER BY full_name")->fetchAll();

$grouped = [];
foreach ($tours as $tour) {
    $date = date('Y-m-d', strtotime($tour['departure_time']));
    $grouped[$date][] = $tour;
}

page_start('Schedule', 'schedule');
?>

<div class="page-header"><div><h1>Operations Schedule</h1><p>Upcoming tours, vehicles and driver assignments.</p></div></div>

<div class="actions" style="margin-bottom:16px;">
    <?php foreach (['today'=>'Today','week'=>'Next 7 Days','month'=>'Next 30 Days','all'=>'All'] as $k=>$label): ?>
        <a class="btn <?= $view===$k?'btn-primary':'btn-secondary' ?>" href="?<?= http_build_query(['view'=>$k,'vehicle_id'=>$vehicleId ?: '','driver_id'=>$driverId ?: '']) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="panel" style="margin-bottom:22px;">
<form class="filters" method="GET">
    <input type="hidden" name="view" value="<?= e($view) ?>">
    <select name="vehicle_id">
        <option value="">All vehicles</option>
        <?php foreach ($vehicles as $v): ?><option value="<?= (int)$v['id'] ?>" <?= (int)$vehicleId===(int)$v['id']?'selected':'' ?>><?= e($v['vehicle_code'].' - '.$v['vehicle_name']) ?></option><?php endforeach; ?>
    </select>
    <select name="driver_id">
        <option value="">All drivers</option>
        <?php foreach ($drivers as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (int)$driverId===(int)$d['id']?'selected':'' ?>><?= e($d['driver_code'].' - '.$d['full_name']) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Apply</button>
    <a class="btn btn-secondary" href="?view=<?= e($view) ?>">Clear</a>
</form>
</div>

<?php if (!$grouped): ?>
    <div class="panel"><div class="empty-state">No scheduled operations for this view.</div></div>
<?php else: ?>
    <?php foreach ($grouped as $date=>$items): ?>
        <div class="timeline-day">
            <h2><?= e(strtoupper(date('D, d M Y', strtotime($date)))) ?></h2>
            <?php foreach ($items as $t): ?>
                <div class="timeline-item">
                    <div><strong><?= e(date('H:i', strtotime($t['departure_time']))) ?></strong><div class="entity-code">to <?= e(date('H:i', strtotime($t['return_time']))) ?></div></div>
                    <div><div class="entity-name"><?= e($t['title']) ?></div><div class="entity-code"><?= e($t['tour_code'].' • '.$t['destination']) ?></div></div>
                    <div><div class="entity-code">VEHICLE</div><strong><?= e($t['vehicle_name']) ?></strong><div class="entity-code"><?= e($t['vehicle_code']) ?></div></div>
                    <div><div class="entity-code">DRIVER</div><strong><?= e($t['driver_name']) ?></strong><div class="entity-code"><?= e($t['driver_code']) ?></div></div>
                    <div><span class="badge <?= status_badge($t['status']) ?>"><?= e(str_replace('_',' ',$t['status'])) ?></span><div style="margin-top:8px;"><a class="btn btn-secondary" href="../tours/view.php?id=<?= (int)$t['id'] ?>">View</a></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php page_end(); ?>
