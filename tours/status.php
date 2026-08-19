<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed.');
}

verify_csrf();

$tourId = filter_input(INPUT_POST, 'tour_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$tourId) die('Invalid tour.');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM tours WHERE id=? FOR UPDATE");
    $stmt->execute([$tourId]);
    $tour = $stmt->fetch();

    if (!$tour) throw new Exception('Tour not found.');

    $newStatus = match ($action) {
        'start' => $tour['status']==='SCHEDULED' ? 'IN_PROGRESS' : throw new Exception('Only scheduled tours can be started.'),
        'complete' => $tour['status']==='IN_PROGRESS' ? 'COMPLETED' : throw new Exception('Only tours in progress can be completed.'),
        'cancel' => $tour['status']==='SCHEDULED' ? 'CANCELLED' : throw new Exception('Only scheduled tours can be cancelled.'),
        default => throw new Exception('Invalid tour action.')
    };

    $stmt = $pdo->prepare("UPDATE tours SET status=? WHERE id=?");
    $stmt->execute([$newStatus,$tourId]);

    $pdo->commit();
    flash('success', 'Tour status changed to ' . str_replace('_',' ',$newStatus) . '.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    flash('error', $e->getMessage());
}

redirect('index.php');
