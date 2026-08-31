<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = basename(__FILE__) === 'edit.php';

$vehicles = $pdo->query("SELECT id,vehicle_code,vehicle_name FROM vehicles ORDER BY vehicle_name")->fetchAll();

$record = [
    'vehicle_id'=>'',
    'title'=>'',
    'description'=>'',
    'maintenance_date'=>date('Y-m-d'),
    'cost'=>'',
    'status'=>'PLANNED'
];

if ($isEdit) {
    if (!$id) die('Invalid maintenance record.');
    $stmt = $pdo->prepare("SELECT * FROM maintenance_records WHERE id=?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    if (!$record) die('Record not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $record = [
        'vehicle_id'=>filter_input(INPUT_POST,'vehicle_id',FILTER_VALIDATE_INT),
        'title'=>trim($_POST['title'] ?? ''),
        'description'=>trim($_POST['description'] ?? ''),
        'maintenance_date'=>$_POST['maintenance_date'] ?? '',
        'cost'=>$_POST['cost'] ?? '',
        'status'=>$_POST['status'] ?? 'PLANNED'
    ];

    if (!$record['vehicle_id'] || $record['title']==='' || $record['maintenance_date']==='') {
        $error = 'Please complete required fields.';
    } else {
        try {
            if ($isEdit) {
                $stmt=$pdo->prepare("UPDATE maintenance_records SET vehicle_id=:vehicle_id,title=:title,description=:description,maintenance_date=:maintenance_date,cost=:cost,status=:status WHERE id=:id");
                $params=$record; $params['id']=$id; $params['description']=$record['description'] ?: null; $params['cost']=$record['cost'] !== '' ? $record['cost'] : null;
                $stmt->execute($params);
                flash('success','Maintenance record updated.');
            } else {
                $stmt=$pdo->prepare("INSERT INTO maintenance_records(vehicle_id,title,description,maintenance_date,cost,status) VALUES(:vehicle_id,:title,:description,:maintenance_date,:cost,:status)");
                $params=$record; $params['description']=$record['description'] ?: null; $params['cost']=$record['cost'] !== '' ? $record['cost'] : null;
                $stmt->execute($params);
                flash('success','Maintenance record created.');
            }
            redirect('index.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error='Unable to save maintenance record.';
        }
    }
}

page_start($isEdit ? 'Edit Maintenance' : 'Add Maintenance', 'maintenance');
?>

<div class="page-header"><div><h1><?= $isEdit?'Edit':'Add' ?> Maintenance</h1><p>Track maintenance work and cost.</p></div><a class="btn btn-secondary" href="index.php">Back</a></div>
<?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<div class="panel">
<form class="form-grid" method="POST">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="form-group full"><label>Vehicle *</label><select name="vehicle_id" required><option value="">Select</option><?php foreach($vehicles as $v): ?><option value="<?= (int)$v['id'] ?>" <?= (int)$record['vehicle_id']===(int)$v['id']?'selected':'' ?>><?= e($v['vehicle_code'].' - '.$v['vehicle_name']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group full"><label>Title *</label><input name="title" value="<?= e($record['title']) ?>" required></div>
    <div class="form-group"><label>Date *</label><input type="date" name="maintenance_date" value="<?= e($record['maintenance_date']) ?>" required></div>
    <div class="form-group"><label>Cost</label><input type="number" step="0.01" name="cost" value="<?= e((string)$record['cost']) ?>"></div>
    <div class="form-group"><label>Status</label><select name="status"><?php foreach(['PLANNED','IN_PROGRESS','COMPLETED'] as $o): ?><option <?= $record['status']===$o?'selected':'' ?>><?= e($o) ?></option><?php endforeach; ?></select></div>
    <div class="form-group full"><label>Description</label><textarea rows="4" name="description"><?= e($record['description'] ?? '') ?></textarea></div>
    <div class="form-actions"><button class="btn btn-primary">Save</button><a class="btn btn-secondary" href="index.php">Cancel</a></div>
</form>
</div>
<?php page_end(); ?>
