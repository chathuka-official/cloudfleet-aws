<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = basename(__FILE__) === 'edit.php';

$driver = [
    'driver_code' => '',
    'full_name' => '',
    'nic_number' => '',
    'phone' => '',
    'email' => '',
    'license_number' => '',
    'license_expiry' => '',
    'license_classes' => '',
    'status' => 'AVAILABLE',
    'employment_status' => 'ACTIVE',
    'emergency_contact_name' => '',
    'emergency_contact_phone' => '',
    'notes' => ''
];

if ($isEdit) {
    if (!$id) die('Invalid driver.');
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id=?");
    $stmt->execute([$id]);
    $driver = $stmt->fetch();
    if (!$driver) die('Driver not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = [
        'driver_code' => trim($_POST['driver_code'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'nic_number' => trim($_POST['nic_number'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'license_number' => trim($_POST['license_number'] ?? ''),
        'license_expiry' => $_POST['license_expiry'] ?? '',
        'license_classes' => trim($_POST['license_classes'] ?? ''),
        'status' => $_POST['status'] ?? 'AVAILABLE',
        'employment_status' => $_POST['employment_status'] ?? 'ACTIVE',
        'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
        'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? ''),
        'notes' => trim($_POST['notes'] ?? '')
    ];

    if (
        $driver['driver_code'] === '' ||
        $driver['full_name'] === '' ||
        $driver['phone'] === '' ||
        $driver['license_number'] === '' ||
        $driver['license_expiry'] === ''
    ) {
        $error = 'Please complete all required fields.';
    } else {
        try {
            $params = $driver;
            foreach (['nic_number','email','license_classes','emergency_contact_name','emergency_contact_phone','notes'] as $nullable) {
                $params[$nullable] = $params[$nullable] ?: null;
            }

            if ($isEdit) {
                $stmt = $pdo->prepare("
                    UPDATE drivers SET
                        driver_code=:driver_code,
                        full_name=:full_name,
                        nic_number=:nic_number,
                        phone=:phone,
                        email=:email,
                        license_number=:license_number,
                        license_expiry=:license_expiry,
                        license_classes=:license_classes,
                        status=:status,
                        employment_status=:employment_status,
                        emergency_contact_name=:emergency_contact_name,
                        emergency_contact_phone=:emergency_contact_phone,
                        notes=:notes
                    WHERE id=:id
                ");
                $params['id'] = $id;
                $stmt->execute($params);
                flash('success', 'Driver updated.');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO drivers
                    (driver_code,full_name,nic_number,phone,email,license_number,license_expiry,license_classes,status,employment_status,emergency_contact_name,emergency_contact_phone,notes)
                    VALUES
                    (:driver_code,:full_name,:nic_number,:phone,:email,:license_number,:license_expiry,:license_classes,:status,:employment_status,:emergency_contact_name,:emergency_contact_phone,:notes)
                ");
                $stmt->execute($params);
                flash('success', 'Driver added.');
            }

            redirect('index.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Unable to save driver. Check duplicate driver/licence/NIC values.';
        }
    }
}

page_start($isEdit ? 'Edit Driver' : 'Add Driver', 'drivers');
?>

<div class="page-header">
    <div><h1><?= $isEdit ? 'Edit Driver' : 'Add Driver' ?></h1><p>Driver and licence record.</p></div>
    <a class="btn btn-secondary" href="index.php">Back</a>
</div>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
<form class="form-grid" method="POST">
    <div class="form-group"><label>Driver Code *</label><input name="driver_code" value="<?= e($driver['driver_code']) ?>" required></div>
    <div class="form-group"><label>Full Name *</label><input name="full_name" value="<?= e($driver['full_name']) ?>" required></div>
    <div class="form-group"><label>NIC / Employee ID</label><input name="nic_number" value="<?= e($driver['nic_number'] ?? '') ?>"></div>
    <div class="form-group"><label>Phone *</label><input name="phone" value="<?= e($driver['phone']) ?>" required></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($driver['email'] ?? '') ?>"></div>
    <div class="form-group"><label>Licence Number *</label><input name="license_number" value="<?= e($driver['license_number']) ?>" required></div>
    <div class="form-group"><label>Licence Expiry *</label><input type="date" name="license_expiry" value="<?= e($driver['license_expiry']) ?>" required></div>
    <div class="form-group"><label>Licence Classes</label><input name="license_classes" value="<?= e($driver['license_classes'] ?? '') ?>"></div>
    <div class="form-group"><label>Status</label><select name="status"><?php foreach (['AVAILABLE','ASSIGNED','ON_LEAVE','INACTIVE'] as $o): ?><option <?= $driver['status']===$o?'selected':'' ?>><?= e($o) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Employment</label><select name="employment_status"><?php foreach (['ACTIVE','SUSPENDED','TERMINATED'] as $o): ?><option <?= $driver['employment_status']===$o?'selected':'' ?>><?= e($o) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Emergency Contact Name</label><input name="emergency_contact_name" value="<?= e($driver['emergency_contact_name'] ?? '') ?>"></div>
    <div class="form-group"><label>Emergency Contact Phone</label><input name="emergency_contact_phone" value="<?= e($driver['emergency_contact_phone'] ?? '') ?>"></div>
    <div class="form-group full"><label>Notes</label><textarea name="notes" rows="4"><?= e($driver['notes'] ?? '') ?></textarea></div>
    <div class="form-actions"><button class="btn btn-primary">Save</button><a class="btn btn-secondary" href="index.php">Cancel</a></div>
</form>
</div>

<?php page_end(); ?>
