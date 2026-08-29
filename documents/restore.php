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


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    die('Method not allowed.');
}


verify_csrf();


$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);


if (!$id) {

    flash(
        'error',
        'Invalid document.'
    );

    redirect('trash.php');
}


$stmt = $pdo->prepare("
    SELECT *
    FROM documents
    WHERE id = ?
    AND deleted_at IS NOT NULL
");

$stmt->execute([$id]);

$document = $stmt->fetch();


if (!$document) {

    flash(
        'error',
        'Deleted document not found.'
    );

    redirect('trash.php');
}


if (
    empty(
        $document[
            'delete_marker_version_id'
        ]
    )
) {

    flash(
        'error',
        'The S3 delete marker information is missing.'
    );

    redirect('trash.php');
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

    flash(
        'error',
        'S3 bucket is not configured.'
    );

    redirect('trash.php');
}


try {

    $s3 = new S3Client([
        'version' => 'latest',
        'region' => $region
    ]);


    /*
     * Delete ONLY the delete marker.
     *
     * The previous real object version then
     * becomes the current object again.
     */
    $s3->deleteObject([

        'Bucket' => $bucket,

        'Key' =>
            $document['s3_key'],

        'VersionId' =>
            $document[
                'delete_marker_version_id'
            ]

    ]);


    /*
     * Restore the RDS record.
     */
    $update = $pdo->prepare("
        UPDATE documents
        SET
            deleted_at = NULL,
            delete_marker_version_id = NULL
        WHERE id = ?
    ");


    $update->execute([$id]);


    flash(
        'success',
        'Document restored successfully from Amazon S3.'
    );


    redirect('index.php');


} catch (Throwable $e) {

    error_log(
        'Document restore error: '
        . $e->getMessage()
    );


    flash(
        'error',
        'Unable to restore document. Check the S3 IAM permissions.'
    );


    redirect('trash.php');

}