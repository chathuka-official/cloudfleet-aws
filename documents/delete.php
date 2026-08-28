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
    die('Invalid document.');
}

$stmt = $pdo->prepare("
    SELECT id, s3_key, original_name
    FROM documents
    WHERE id = ?
");

$stmt->execute([$id]);

$document = $stmt->fetch();

if (!$document) {
    die('Document not found.');
}

$bucket = $_SERVER['S3_BUCKET'] ?? getenv('S3_BUCKET') ?: '';
$region = $_SERVER['AWS_REGION'] ?? getenv('AWS_REGION') ?: 'ap-south-1';

if ($bucket === '') {
    die('S3 bucket is not configured.');
}

try {

    $s3 = new S3Client([
        'version' => 'latest',
        'region' => $region
    ]);

    $s3->deleteObject([
        'Bucket' => $bucket,
        'Key' => $document['s3_key']
    ]);

    $stmt = $pdo->prepare("
        DELETE FROM documents
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    flash('success', 'Document deleted from CloudFleet.');

} catch (Throwable $e) {

    error_log($e->getMessage());

    flash(
        'error',
        'Unable to delete document. Check S3 permissions.'
    );
}

redirect('index.php');