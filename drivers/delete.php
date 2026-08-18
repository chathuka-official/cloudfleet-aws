<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    die('Method not allowed.');
}

$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {

    http_response_code(403);

    die('Invalid request.');
}

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

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

if ($driver['status'] === 'ASSIGNED') {

    die(
        'This driver cannot be deleted because the driver is currently assigned.'
    );
}

try {

    $stmt = $pdo->prepare("
        DELETE FROM drivers
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    header('Location: index.php');

    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    die('Unable to delete driver.');
}