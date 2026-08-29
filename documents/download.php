<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$autoload =
    __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    die('AWS SDK is not installed.');
}

require_once $autoload;

use Aws\S3\S3Client;


$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$id) {
    die('Invalid document.');
}


$stmt = $pdo->prepare("
    SELECT *
    FROM documents
    WHERE id = ?
    AND deleted_at IS NULL
");

$stmt->execute([$id]);

$document = $stmt->fetch();


if (!$document) {
    die('Document not found.');
}


$bucket =
    $_SERVER['S3_BUCKET']
    ?? getenv('S3_BUCKET')
    ?: '';

$region =
    $_SERVER['AWS_REGION']
    ?? getenv('AWS_REGION')
    ?: 'ap-south-1';


if ($bucket === '') {
    die('S3 bucket is not configured.');
}


try {

    $s3 = new S3Client([
        'version' => 'latest',
        'region' => $region
    ]);


    $filename =
        str_replace(
            ['"', "\r", "\n"],
            '',
            $document['original_name']
        );


    $command =
        $s3->getCommand(
            'GetObject',
            [

                'Bucket' => $bucket,

                'Key' =>
                    $document['s3_key'],

                'ResponseContentDisposition'
                    =>
                    'attachment; filename="'
                    . $filename
                    . '"'

            ]
        );


    $request =
        $s3->createPresignedRequest(
            $command,
            '+5 minutes'
        );


    header(
        'Location: '
        . (string)$request->getUri()
    );

    exit;


} catch (Throwable $e) {

    error_log(
        'S3 download error: '
        . $e->getMessage()
    );

    die(
        'Unable to create secure download link.'
    );

}