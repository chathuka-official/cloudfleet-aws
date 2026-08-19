<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed.');
}

verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) die('Invalid vehicle.');

$stmt = $pdo->prepare("SELECT status FROM vehicles WHERE id=?");
$stmt->execute([$id]);
$vehicle = $stmt->fetch();

if (!$vehicle) die('Vehicle not found.');

if ($vehicle['status'] === 'ASSIGNED') {
    flash('error', 'Assigned vehicles cannot be deleted.');
    redirect('index.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id=?");
    $stmt->execute([$id]);
    flash('success', 'Vehicle deleted.');
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('error', 'Vehicle cannot be deleted because it is referenced by another record.');
}

redirect('index.php');
