<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    die('AWS SDK is not installed.');
}

require_once $autoload;

use Aws\S3\S3Client;

$bucket =
    $_SERVER['S3_BUCKET']
    ?? getenv('S3_BUCKET')
    ?: '';

$region =
    $_SERVER['AWS_REGION']
    ?? getenv('AWS_REGION')
    ?: 'ap-south-1';


if ($bucket === '') {
    die('S3_BUCKET environment property is missing.');
}


$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $title = trim($_POST['title'] ?? '');

    $entityType =
        $_POST['entity_type']
        ?? 'OTHER';

    $entityId = filter_input(
        INPUT_POST,
        'entity_id',
        FILTER_VALIDATE_INT
    );


    $allowedEntities = [
        'VEHICLE',
        'DRIVER',
        'TOUR',
        'OTHER'
    ];


    if (!in_array(
        $entityType,
        $allowedEntities,
        true
    )) {

        $entityType = 'OTHER';
    }


    if (
        $title === ''
        ||
        !isset($_FILES['document'])
    ) {

        $error =
            'Choose a document and enter a title.';

    } else {

        $file = $_FILES['document'];


        if (
            $file['error']
            !== UPLOAD_ERR_OK
        ) {

            $error =
                'The file could not be uploaded.';

        } elseif (
            $file['size']
            > 10 * 1024 * 1024
        ) {

            $error =
                'Maximum file size is 10 MB.';

        } else {

            $finfo =
                new finfo(FILEINFO_MIME_TYPE);

            $mimeType =
                $finfo->file($file['tmp_name']);


            $allowedTypes = [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ];


            if (
                !in_array(
                    $mimeType,
                    $allowedTypes,
                    true
                )
            ) {

                $error =
                    'Only PDF, JPG and PNG files are allowed.';

            } else {

                try {

                    $s3 = new S3Client([
                        'version' => 'latest',
                        'region' => $region
                    ]);


                    $originalName =
                        basename($file['name']);


                    $safeName =
                        preg_replace(
                            '/[^A-Za-z0-9._-]/',
                            '_',
                            $originalName
                        );


                    $key =
                        strtolower($entityType)
                        . '/'
                        . date('Y/m')
                        . '/'
                        . bin2hex(random_bytes(8))
                        . '-'
                        . $safeName;


                    $s3->putObject([

                        'Bucket' => $bucket,

                        'Key' => $key,

                        'SourceFile' =>
                            $file['tmp_name'],

                        'ContentType' =>
                            $mimeType,

                        'ServerSideEncryption'
                            => 'AES256'

                    ]);


                    $stmt = $pdo->prepare("
                        INSERT INTO documents
                        (
                            entity_type,
                            entity_id,
                            title,
                            s3_key,
                            original_name,
                            mime_type,
                            size_bytes
                        )
                        VALUES
                        (
                            ?, ?, ?, ?, ?, ?, ?
                        )
                    ");


                    $stmt->execute([

                        $entityType,

                        $entityId ?: null,

                        $title,

                        $key,

                        $originalName,

                        $mimeType,

                        $file['size']

                    ]);


                    flash(
                        'success',
                        'Document uploaded to Amazon S3.'
                    );


                    redirect('index.php');


                } catch (Throwable $e) {

                    error_log(
                        'S3 upload error: '
                        . $e->getMessage()
                    );


                    $error =
                        'S3 upload failed. Check AWS permissions.';

                }

            }

        }

    }

}


page_start(
    'Upload Document',
    'documents'
);

?>


<div class="page-header">

    <div>

        <h1>Upload Document</h1>

        <p>
            Store a CloudFleet document
            securely in Amazon S3.
        </p>

    </div>

    <a
        class="btn btn-secondary"
        href="index.php"
    >
        Back
    </a>

</div>


<?php if ($error): ?>

<div class="alert error">
    <?= e($error) ?>
</div>

<?php endif; ?>


<div class="panel">

<form
    class="form-grid"
    method="POST"
    enctype="multipart/form-data"
>

    <input
        type="hidden"
        name="csrf_token"
        value="<?= e(csrf_token()) ?>"
    >


    <div class="form-group full">

        <label>
            Title *
        </label>

        <input
            name="title"
            required
        >

    </div>


    <div class="form-group">

        <label>
            Entity Type
        </label>

        <select name="entity_type">

            <option value="VEHICLE">
                VEHICLE
            </option>

            <option value="DRIVER">
                DRIVER
            </option>

            <option value="TOUR">
                TOUR
            </option>

            <option value="OTHER">
                OTHER
            </option>

        </select>

    </div>


    <div class="form-group">

        <label>
            Entity ID
        </label>

        <input
            type="number"
            name="entity_id"
            min="1"
        >

    </div>


    <div class="form-group full">

        <label>
            Document *
        </label>

        <input
            type="file"
            name="document"
            accept=".pdf,.jpg,.jpeg,.png"
            required
        >

    </div>


    <div class="form-actions">

        <button
            class="btn btn-primary"
            type="submit"
        >
            Upload to S3
        </button>

        <a
            class="btn btn-secondary"
            href="index.php"
        >
            Cancel
        </a>

    </div>

</form>

</div>


<?php page_end(); ?>