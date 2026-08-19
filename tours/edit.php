<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../app/tour_rules.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = basename(__FILE__) === 'edit.php';

$tour = [
    'tour_code' => '',
    'title' => '',
    'destination' => '',
    'departure_time' => '',
    'return_time' => '',
    'passenger_count' => '',
    'vehicle_id' => '',
    'driver_id' => '',
    'notes' => ''
];

if ($isEdit) {
    if (!$id) die('Invalid tour.');
    $stmt = $pdo->prepare("
        SELECT t.*, a.vehicle_id, a.driver_id
        FROM tours t
        LEFT JOIN tour_assignments a ON a.tour_id=t.id
        WHERE t.id=?
    ");
    $stmt->execute([$id]);
    $tour = $stmt->fetch();
    if (!$tour) die('Tour not found.');
    if ($tour['status'] !== 'SCHEDULED') die('Only scheduled tours can be edited.');

    $tour['departure_time'] = date('Y-m-d\TH:i', strtotime($tour['departure_time']));
    $tour['return_time'] = date('Y-m-d\TH:i', strtotime($tour['return_time']));
}

$vehicles = $pdo->query("SELECT * FROM vehicles WHERE status NOT IN ('MAINTENANCE','INACTIVE') ORDER BY vehicle_name")->fetchAll();
$drivers = $pdo->query("SELECT * FROM drivers WHERE status NOT IN ('ON_LEAVE','INACTIVE') AND employment_status='ACTIVE' ORDER BY full_name")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tour = [
        'tour_code' => trim($_POST['tour_code'] ?? ''),
        'title' => trim($_POST['title'] ?? ''),
        'destination' => trim($_POST['destination'] ?? ''),
        'departure_time' => $_POST['departure_time'] ?? '',
        'return_time' => $_POST['return_time'] ?? '',
        'passenger_count' => (int)($_POST['passenger_count'] ?? 0),
        'vehicle_id' => filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT),
        'driver_id' => filter_input(INPUT_POST, 'driver_id', FILTER_VALIDATE_INT),
        'notes' => trim($_POST['notes'] ?? '')
    ];

    if (
        $tour['tour_code']==='' || $tour['title']==='' || $tour['destination']==='' ||
        !$tour['vehicle_id'] || !$tour['driver_id']
    ) {
        $error = 'Please complete all required fields.';
    } else {
        try {
            $check = checkTourAssignment(
                $pdo,
                (int)$tour['vehicle_id'],
                (int)$tour['driver_id'],
                $tour['departure_time'],
                $tour['return_time'],
                (int)$tour['passenger_count'],
                $isEdit ? $id : null
            );

            $pdo->beginTransaction();

            if ($isEdit) {
                $stmt = $pdo->prepare("
                    UPDATE tours SET
                        tour_code=:tour_code,title=:title,destination=:destination,
                        departure_time=:departure_time,return_time=:return_time,
                        passenger_count=:passenger_count,notes=:notes
                    WHERE id=:id
                ");
                $stmt->execute([
                    'tour_code'=>$tour['tour_code'],
                    'title'=>$tour['title'],
                    'destination'=>$tour['destination'],
                    'departure_time'=>$check['departure'],
                    'return_time'=>$check['return'],
                    'passenger_count'=>$tour['passenger_count'],
                    'notes'=>$tour['notes'] ?: null,
                    'id'=>$id
                ]);

                $stmt = $pdo->prepare("UPDATE tour_assignments SET vehicle_id=?, driver_id=? WHERE tour_id=?");
                $stmt->execute([$tour['vehicle_id'],$tour['driver_id'],$id]);

                flash('success', 'Tour updated.');
                $targetId = $id;
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO tours
                    (tour_code,title,destination,departure_time,return_time,passenger_count,notes)
                    VALUES
                    (:tour_code,:title,:destination,:departure_time,:return_time,:passenger_count,:notes)
                ");
                $stmt->execute([
                    'tour_code'=>$tour['tour_code'],
                    'title'=>$tour['title'],
                    'destination'=>$tour['destination'],
                    'departure_time'=>$check['departure'],
                    'return_time'=>$check['return'],
                    'passenger_count'=>$tour['passenger_count'],
                    'notes'=>$tour['notes'] ?: null
                ]);

                $targetId = (int)$pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO tour_assignments (tour_id,vehicle_id,driver_id) VALUES (?,?,?)");
                $stmt->execute([$targetId,$tour['vehicle_id'],$tour['driver_id']]);

                flash('success', 'Tour created.');
            }

            $pdo->commit();
            redirect('view.php?id=' . $targetId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($e->getMessage());
            $error = $e->getMessage();
        }
    }
}

page_start($isEdit ? 'Edit Tour' : 'Create Tour', 'tours');
?>

<div class="page-header">
    <div><h1><?= $isEdit ? 'Edit Tour' : 'Create Tour' ?></h1><p>Schedule a tour and assign fleet resources.</p></div>
    <a class="btn btn-secondary" href="index.php">Back</a>
</div>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
<form class="form-grid" method="POST">
    <div class="form-group"><label>Tour Code *</label><input name="tour_code" value="<?= e($tour['tour_code']) ?>" required></div>
    <div class="form-group"><label>Title *</label><input name="title" value="<?= e($tour['title']) ?>" required></div>
    <div class="form-group full"><label>Destination *</label><input name="destination" value="<?= e($tour['destination']) ?>" required></div>
    <div class="form-group"><label>Departure *</label><input type="datetime-local" name="departure_time" value="<?= e($tour['departure_time']) ?>" required></div>
    <div class="form-group"><label>Return *</label><input type="datetime-local" name="return_time" value="<?= e($tour['return_time']) ?>" required></div>
    <div class="form-group"><label>Passengers *</label><input type="number" name="passenger_count" min="1" value="<?= e((string)$tour['passenger_count']) ?>" required></div>

    <div class="form-group"><label>Vehicle *</label>
        <select name="vehicle_id" required>
            <option value="">Select vehicle</option>
            <?php foreach ($vehicles as $v): ?>
                <option value="<?= (int)$v['id'] ?>" <?= (int)$tour['vehicle_id']===(int)$v['id']?'selected':'' ?>>
                    <?= e($v['vehicle_code'].' - '.$v['vehicle_name'].' ('.$v['capacity'].' seats)') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group full"><label>Driver *</label>
        <select name="driver_id" required>
            <option value="">Select driver</option>
            <?php foreach ($drivers as $d): ?>
                <option value="<?= (int)$d['id'] ?>" <?= (int)$tour['driver_id']===(int)$d['id']?'selected':'' ?>>
                    <?= e($d['driver_code'].' - '.$d['full_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group full"><label>Notes</label><textarea name="notes" rows="4"><?= e($tour['notes'] ?? '') ?></textarea></div>
    <div class="form-actions"><button class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Tour' ?></button><a class="btn btn-secondary" href="index.php">Cancel</a></div>
</form>
</div>

<?php page_end(); ?>
