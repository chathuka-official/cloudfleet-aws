<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    die('AWS SDK is not installed.');
}

require_once $autoload;

use Aws\S3\S3Client;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed.');
}

verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Invalid document ID.');
}

$stmt = $pdo->prepare("
    SELECT id, s3_key
    FROM documents
    WHERE id = ?
");

$stmt->execute([$id]);

$document = $stmt->fetch();

if (!$document) {
    flash('error', 'Document not found.');
    redirect('index.php');
}

$bucket = $_SERVER['S3_BUCKET'] ?? getenv('S3_BUCKET') ?: '';
$region = $_SERVER['AWS_REGION'] ?? getenv('AWS_REGION') ?: 'ap-south-1';

if ($bucket === '') {
    flash('error', 'S3 bucket is not configured.');
    redirect('index.php');
}

try {

    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => $region
    ]);

    // Delete object from S3
    $s3->deleteObject([
        'Bucket' => $bucket,
        'Key'    => $document['s3_key']
    ]);

    // Delete record from database
    $delete = $pdo->prepare("
        DELETE FROM documents
        WHERE id = ?
    ");

    $delete->execute([$id]);

    if ($delete->rowCount() === 1) {
        flash('success', 'Document deleted successfully.');
    } else {
        flash('error', 'S3 object was deleted, but database record was not removed.');
    }

} catch (Throwable $e) {

    error_log('Document delete error: ' . $e->getMessage());

    flash(
        'error',
        'Unable to delete document. Check AWS permissions.'
    );
}

redirect('index.php');