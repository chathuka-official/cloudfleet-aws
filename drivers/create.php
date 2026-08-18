<?php

require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $code = trim($_POST['driver_code'] ?? '');
    $name = trim($_POST['full_name'] ?? '');
    $nic = trim($_POST['nic_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $license = trim(
        $_POST['license_number'] ?? ''
    );

    $expiry =
        $_POST['license_expiry'] ?? '';

    $classes = trim(
        $_POST['license_classes'] ?? ''
    );


    if (
        $code === '' ||
        $name === '' ||
        $phone === '' ||
        $license === '' ||
        $expiry === ''
    ) {

        $error =
            'Please complete all required fields.';

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO drivers (
                    driver_code,
                    full_name,
                    nic_number,
                    phone,
                    email,
                    license_number,
                    license_expiry,
                    license_classes
                )

                VALUES (
                    :driver_code,
                    :full_name,
                    :nic_number,
                    :phone,
                    :email,
                    :license_number,
                    :license_expiry,
                    :license_classes
                )
            ");

            $stmt->execute([

                'driver_code' => $code,

                'full_name' => $name,

                'nic_number' =>
                    $nic ?: null,

                'phone' => $phone,

                'email' =>
                    $email ?: null,

                'license_number' =>
                    $license,

                'license_expiry' =>
                    $expiry,

                'license_classes' =>
                    $classes ?: null

            ]);

            header(
                'Location: index.php'
            );

            exit;

        } catch (PDOException $e) {

            error_log(
                $e->getMessage()
            );

            $error =
                'Unable to add driver.';
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>
        Add Driver | CloudFleet
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/app.css"
    >

</head>

<body>

<main class="content">

<div class="page-header">

    <div>

        <h1>
            Add Driver
        </h1>

        <p>
            Register a new CloudFleet driver.
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

        <label>
            Driver Code *
        </label>

        <br>

        <input
            type="text"
            name="driver_code"
            placeholder="DRV-0001"
            required
        >

    </p>


    <p>

        <label>
            Full Name *
        </label>

        <br>

        <input
            type="text"
            name="full_name"
            required
        >

    </p>


    <p>

        <label>
            NIC / Employee ID
        </label>

        <br>

        <input
            type="text"
            name="nic_number"
        >

    </p>


    <p>

        <label>
            Phone *
        </label>

        <br>

        <input
            type="text"
            name="phone"
            required
        >

    </p>


    <p>

        <label>
            Email
        </label>

        <br>

        <input
            type="email"
            name="email"
        >

    </p>


    <p>

        <label>
            Licence Number *
        </label>

        <br>

        <input
            type="text"
            name="license_number"
            required
        >

    </p>


    <p>

        <label>
            Licence Expiry *
        </label>

        <br>

        <input
            type="date"
            name="license_expiry"
            required
        >

    </p>


    <p>

        <label>
            Licence Classes
        </label>

        <br>

        <input
            type="text"
            name="license_classes"
            placeholder="B, D"
        >

    </p>


    <button
        type="submit"
        class="btn btn-primary"
    >
        Add Driver
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