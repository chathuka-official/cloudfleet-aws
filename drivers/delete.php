<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed.');
}

verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) die('Invalid driver.');

$stmt = $pdo->prepare("SELECT status FROM drivers WHERE id=?");
$stmt->execute([$id]);
$driver = $stmt->fetch();

if (!$driver) die('Driver not found.');

if ($driver['status'] === 'ASSIGNED') {
    flash('error', 'Assigned drivers cannot be deleted.');
    redirect('index.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM drivers WHERE id=?");
    $stmt->execute([$id]);
    flash('success', 'Driver deleted.');
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('error', 'Driver cannot be deleted because it is referenced by another record.');
}

redirect('index.php');
