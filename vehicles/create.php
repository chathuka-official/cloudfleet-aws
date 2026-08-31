<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = __FILE__ && basename(__FILE__) === 'edit.php';

$vehicle = [
    'vehicle_code' => '',
    'registration_number' => '',
    'vehicle_name' => '',
    'vehicle_type' => 'VAN',
    'manufacturer' => '',
    'model' => '',
    'manufacture_year' => '',
    'capacity' => '',
    'status' => 'AVAILABLE',
    'notes' => ''
];

if ($isEdit) {
    if (!$id) die('Invalid vehicle.');
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id=?");
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch();
    if (!$vehicle) die('Vehicle not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $vehicle = [
        'vehicle_code' => trim($_POST['vehicle_code'] ?? ''),
        'registration_number' => trim($_POST['registration_number'] ?? ''),
        'vehicle_name' => trim($_POST['vehicle_name'] ?? ''),
        'vehicle_type' => $_POST['vehicle_type'] ?? '',
        'manufacturer' => trim($_POST['manufacturer'] ?? ''),
        'model' => trim($_POST['model'] ?? ''),
        'manufacture_year' => $_POST['manufacture_year'] ?? '',
        'capacity' => (int)($_POST['capacity'] ?? 0),
        'status' => $_POST['status'] ?? 'AVAILABLE',
        'notes' => trim($_POST['notes'] ?? '')
    ];

    $allowedTypes = ['BUS','VAN','CAR','SUV','OTHER'];
    $allowedStatuses = ['AVAILABLE','ASSIGNED','MAINTENANCE','INACTIVE'];

    if (
        $vehicle['vehicle_code'] === '' ||
        $vehicle['registration_number'] === '' ||
        $vehicle['vehicle_name'] === '' ||
        !in_array($vehicle['vehicle_type'], $allowedTypes, true) ||
        !in_array($vehicle['status'], $allowedStatuses, true) ||
        $vehicle['capacity'] <= 0
    ) {
        $error = 'Please enter valid vehicle information.';
    } else {
        try {
            if ($isEdit) {
                $stmt = $pdo->prepare("
                    UPDATE vehicles SET
                        vehicle_code=:vehicle_code,
                        registration_number=:registration_number,
                        vehicle_name=:vehicle_name,
                        vehicle_type=:vehicle_type,
                        manufacturer=:manufacturer,
                        model=:model,
                        manufacture_year=:manufacture_year,
                        capacity=:capacity,
                        status=:status,
                        notes=:notes
                    WHERE id=:id
                ");
                $params = $vehicle;
                $params['manufacturer'] = $vehicle['manufacturer'] ?: null;
                $params['model'] = $vehicle['model'] ?: null;
                $params['manufacture_year'] = $vehicle['manufacture_year'] ?: null;
                $params['notes'] = $vehicle['notes'] ?: null;
                $params['id'] = $id;
                $stmt->execute($params);
                flash('success', 'Vehicle updated.');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO vehicles
                    (vehicle_code,registration_number,vehicle_name,vehicle_type,manufacturer,model,manufacture_year,capacity,status,notes)
                    VALUES
                    (:vehicle_code,:registration_number,:vehicle_name,:vehicle_type,:manufacturer,:model,:manufacture_year,:capacity,:status,:notes)
                ");
                $params = $vehicle;
                $params['manufacturer'] = $vehicle['manufacturer'] ?: null;
                $params['model'] = $vehicle['model'] ?: null;
                $params['manufacture_year'] = $vehicle['manufacture_year'] ?: null;
                $params['notes'] = $vehicle['notes'] ?: null;
                $stmt->execute($params);
                flash('success', 'Vehicle added.');
            }

            redirect('index.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Unable to save vehicle. Check duplicate code/registration.';
        }
    }
}

page_start($isEdit ? 'Edit Vehicle' : 'Add Vehicle', 'vehicles');
?>

<div class="page-header">
    <div><h1><?= $isEdit ? 'Edit Vehicle' : 'Add Vehicle' ?></h1><p>CloudFleet fleet record.</p></div>
    <a class="btn btn-secondary" href="index.php">Back</a>
</div>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
<form class="form-grid" method="POST">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="form-group"><label>Vehicle Code *</label><input name="vehicle_code" value="<?= e($vehicle['vehicle_code']) ?>" required></div>
    <div class="form-group"><label>Registration *</label><input name="registration_number" value="<?= e($vehicle['registration_number']) ?>" required></div>
    <div class="form-group full"><label>Vehicle Name *</label><input name="vehicle_name" value="<?= e($vehicle['vehicle_name']) ?>" required></div>
    <div class="form-group"><label>Type *</label>
        <select name="vehicle_type">
            <?php foreach (['BUS','VAN','CAR','SUV','OTHER'] as $o): ?><option <?= $vehicle['vehicle_type']===$o?'selected':'' ?>><?= e($o) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Status *</label>
        <select name="status">
            <?php foreach (['AVAILABLE','ASSIGNED','MAINTENANCE','INACTIVE'] as $o): ?><option <?= $vehicle['status']===$o?'selected':'' ?>><?= e($o) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Manufacturer</label><input name="manufacturer" value="<?= e($vehicle['manufacturer'] ?? '') ?>"></div>
    <div class="form-group"><label>Model</label><input name="model" value="<?= e($vehicle['model'] ?? '') ?>"></div>
    <div class="form-group"><label>Year</label><input type="number" name="manufacture_year" min="1900" max="2100" value="<?= e((string)($vehicle['manufacture_year'] ?? '')) ?>"></div>
    <div class="form-group"><label>Capacity *</label><input type="number" name="capacity" min="1" value="<?= e((string)$vehicle['capacity']) ?>" required></div>
    <div class="form-group full"><label>Notes</label><textarea name="notes" rows="4"><?= e($vehicle['notes'] ?? '') ?></textarea></div>
    <div class="form-actions"><button class="btn btn-primary">Save</button><a class="btn btn-secondary" href="index.php">Cancel</a></div>
</form>
</div>

<?php page_end(); ?>
