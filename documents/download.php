<?php

require_once __DIR__ . '/../config/database.php';

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    die('AWS SDK is not installed.');
}

require_once $autoload;

use Aws\S3\S3Client;

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Invalid document.');
}

$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
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

    $command = $s3->getCommand('GetObject', [
        'Bucket' => $bucket,
        'Key' => $document['s3_key'],
        'ResponseContentDisposition' =>
            'attachment; filename="' .
            str_replace('"', '', $document['original_name']) .
            '"'
    ]);

    $request = $s3->createPresignedRequest(
        $command,
        '+5 minutes'
    );

    header('Location: ' . (string)$request->getUri());
    exit;

} catch (Throwable $e) {

    error_log($e->getMessage());

    die('Unable to create secure download link.');
}