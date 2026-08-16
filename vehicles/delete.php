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
    die('Invalid vehicle.');
}


// Get vehicle first
$stmt = $pdo->prepare("
    SELECT id, status
    FROM vehicles
    WHERE id = ?
");

$stmt->execute([$id]);

$vehicle = $stmt->fetch();

if (!$vehicle) {
    die('Vehicle not found.');
}


// Protect vehicles currently assigned
if ($vehicle['status'] === 'ASSIGNED') {

    die(
        'This vehicle cannot be deleted because it is currently assigned.'
    );
}


try {

    $stmt = $pdo->prepare("
        DELETE FROM vehicles
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    header('Location: index.php');
    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    die('Unable to delete vehicle.');
}