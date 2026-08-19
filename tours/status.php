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


$tourId = filter_input(
    INPUT_POST,
    'tour_id',
    FILTER_VALIDATE_INT
);

$action = $_POST['action'] ?? '';


if (!$tourId) {
    die('Invalid tour.');
}


try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | LOAD TOUR AND LOCK IT
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT *
        FROM tours
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$tourId]);

    $tour = $stmt->fetch();


    if (!$tour) {

        throw new Exception(
            'Tour not found.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS TRANSITIONS
    |--------------------------------------------------------------------------
    */

    if ($action === 'start') {

        if ($tour['status'] !== 'SCHEDULED') {

            throw new Exception(
                'Only scheduled tours can be started.'
            );
        }

        $newStatus = 'IN_PROGRESS';

    } elseif ($action === 'complete') {

        if ($tour['status'] !== 'IN_PROGRESS') {

            throw new Exception(
                'Only tours in progress can be completed.'
            );
        }

        $newStatus = 'COMPLETED';

    } elseif ($action === 'cancel') {

        if ($tour['status'] !== 'SCHEDULED') {

            throw new Exception(
                'Only scheduled tours can be cancelled.'
            );
        }

        $newStatus = 'CANCELLED';

    } else {

        throw new Exception(
            'Invalid tour action.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE TOUR
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE tours
        SET status = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $newStatus,
        $tourId
    ]);


    $pdo->commit();


    header('Location: index.php');
    exit;


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    die(
        htmlspecialchars(
            $e->getMessage()
        )
    );
}