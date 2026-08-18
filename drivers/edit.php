<?php

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Invalid driver.');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM drivers
    WHERE id = ?
");

$stmt->execute([$id]);

$driver = $stmt->fetch();

if (!$driver) {
    die('Driver not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $code = trim($_POST['driver_code'] ?? '');
    $name = trim($_POST['full_name'] ?? '');
    $nic = trim($_POST['nic_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $license = trim($_POST['license_number'] ?? '');
    $expiry = $_POST['license_expiry'] ?? '';
    $classes = trim($_POST['license_classes'] ?? '');
    $status = $_POST['status'] ?? '';
    $employment = $_POST['employment_status'] ?? '';

    $allowedStatuses = [
        'AVAILABLE',
        'ASSIGNED',
        'ON_LEAVE',
        'INACTIVE'
    ];

    $allowedEmployment = [
        'ACTIVE',
        'SUSPENDED',
        'TERMINATED'
    ];

    if (
        $code === '' ||
        $name === '' ||
        $phone === '' ||
        $license === '' ||
        $expiry === '' ||
        !in_array($status, $allowedStatuses, true) ||
        !in_array($employment, $allowedEmployment, true)
    ) {

        $error = 'Please enter valid driver information.';

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE drivers
                SET
                    driver_code = :driver_code,
                    full_name = :full_name,
                    nic_number = :nic_number,
                    phone = :phone,
                    email = :email,
                    license_number = :license_number,
                    license_expiry = :license_expiry,
                    license_classes = :license_classes,
                    status = :status,
                    employment_status = :employment_status
                WHERE id = :id
            ");

            $stmt->execute([
                'driver_code' => $code,
                'full_name' => $name,
                'nic_number' => $nic ?: null,
                'phone' => $phone,
                'email' => $email ?: null,
                'license_number' => $license,
                'license_expiry' => $expiry,
                'license_classes' => $classes ?: null,
                'status' => $status,
                'employment_status' => $employment,
                'id' => $id
            ]);

            header('Location: index.php');
            exit;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $error = 'Unable to update driver.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Driver | CloudFleet</title>

    <link
        rel="stylesheet"
        href="../assets/css/app.css"
    >

</head>

<body>

<main class="content">

<div class="page-header">

    <div>

        <h1>Edit Driver</h1>

        <p>
            Update driver information and availability.
        </p>

    </div>

</div>

<?php if ($error): ?>

<p>
    <?= htmlspecialchars($error) ?>
</p>

<?php endif; ?>

<div class="panel">

<form
    method="POST"
    style="padding: 25px;"
>

    <p>
        <label>Driver Code</label><br>

        <input
            type="text"
            name="driver_code"
            value="<?= htmlspecialchars($driver['driver_code']) ?>"
            required
        >
    </p>

    <p>
        <label>Full Name</label><br>

        <input
            type="text"
            name="full_name"
            value="<?= htmlspecialchars($driver['full_name']) ?>"
            required
        >
    </p>

    <p>
        <label>NIC / Employee ID</label><br>

        <input
            type="text"
            name="nic_number"
            value="<?= htmlspecialchars($driver['nic_number'] ?? '') ?>"
        >
    </p>

    <p>
        <label>Phone</label><br>

        <input
            type="text"
            name="phone"
            value="<?= htmlspecialchars($driver['phone']) ?>"
            required
        >
    </p>

    <p>
        <label>Email</label><br>

        <input
            type="email"
            name="email"
            value="<?= htmlspecialchars($driver['email'] ?? '') ?>"
        >
    </p>

    <p>
        <label>Licence Number</label><br>

        <input
            type="text"
            name="license_number"
            value="<?= htmlspecialchars($driver['license_number']) ?>"
            required
        >
    </p>

    <p>
        <label>Licence Expiry</label><br>

        <input
            type="date"
            name="license_expiry"
            value="<?= htmlspecialchars($driver['license_expiry']) ?>"
            required
        >
    </p>

    <p>
        <label>Licence Classes</label><br>

        <input
            type="text"
            name="license_classes"
            value="<?= htmlspecialchars($driver['license_classes'] ?? '') ?>"
        >
    </p>

    <p>
        <label>Status</label><br>

        <select name="status">

            <?php

            $statuses = [
                'AVAILABLE',
                'ASSIGNED',
                'ON_LEAVE',
                'INACTIVE'
            ];

            foreach ($statuses as $option):

            ?>

            <option
                value="<?= $option ?>"
                <?= $driver['status'] === $option ? 'selected' : '' ?>
            >
                <?= $option ?>
            </option>

            <?php endforeach; ?>

        </select>
    </p>

    <p>
        <label>Employment Status</label><br>

        <select name="employment_status">

            <?php

            $employmentStatuses = [
                'ACTIVE',
                'SUSPENDED',
                'TERMINATED'
            ];

            foreach ($employmentStatuses as $option):

            ?>

            <option
                value="<?= $option ?>"
                <?= $driver['employment_status'] === $option ? 'selected' : '' ?>
            >
                <?= $option ?>
            </option>

            <?php endforeach; ?>

        </select>
    </p>

    <button
        type="submit"
        class="btn btn-primary"
    >
        Save Changes
    </button>

    <a
        href="index.php"
        class="btn btn-secondary"
    >
        Cancel
    </a>

</form>

</div>

</main>

</body>
</html>