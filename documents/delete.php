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
    flash('error', 'Invalid document.');
    redirect('index.php');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM documents
    WHERE id = ? AND deleted_at IS NULL
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
        'region' => $region
    ]);

    $result = $s3->deleteObject([
        'Bucket' => $bucket,
        'Key' => $document['s3_key']
    ]);

    $deleteMarkerVersionId = $result['VersionId'] ?? null;
    if (!$deleteMarkerVersionId) {
        throw new RuntimeException('S3 did not return a delete marker version ID.');
    }

    $update = $pdo->prepare("
        UPDATE documents
        SET deleted_at = NOW(), delete_marker_version_id = ?
        WHERE id = ?
    ");
    $update->execute([$deleteMarkerVersionId, $id]);

    flash('success', 'Document moved to the recycle bin.');
} catch (Throwable $e) {
    error_log('Document delete error: ' . $e->getMessage());
    flash('error', 'Unable to delete the document. Check S3 permissions and versioning.');
}

redirect('index.php');
