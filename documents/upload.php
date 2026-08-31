<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die('Document storage is unavailable.');
}
require_once $autoload;

use Aws\S3\S3Client;

$bucket = $_SERVER['S3_BUCKET'] ?? getenv('S3_BUCKET') ?: '';
$region = $_SERVER['AWS_REGION'] ?? getenv('AWS_REGION') ?: 'ap-south-1';

if ($bucket === '') {
    die('Document storage is not configured.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $entityType = $_POST['entity_type'] ?? 'OTHER';
    $entityId = filter_input(INPUT_POST, 'entity_id', FILTER_VALIDATE_INT);

    $allowedEntities = ['VEHICLE', 'DRIVER', 'TOUR', 'OTHER'];
    if (!in_array($entityType, $allowedEntities, true)) {
        $entityType = 'OTHER';
    }

    if ($title === '' || !isset($_FILES['document'])) {
        $error = 'Choose a document and enter a title.';
    } else {
        $file = $_FILES['document'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'The file could not be uploaded.';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $error = 'Maximum file size is 10 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];

            if (!in_array($mimeType, $allowedTypes, true)) {
                $error = 'Only PDF, JPG and PNG files are allowed.';
            } else {
                $s3Key = null;

                try {
                    $s3 = new S3Client([
                        'version' => 'latest',
                        'region' => $region
                    ]);

                    $originalName = basename($file['name']);
                    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                    $s3Key = strtolower($entityType) . '/' . date('Y/m') . '/' . bin2hex(random_bytes(8)) . '-' . $safeName;

                    $s3->putObject([
                        'Bucket' => $bucket,
                        'Key' => $s3Key,
                        'SourceFile' => $file['tmp_name'],
                        'ContentType' => $mimeType,
                        'ServerSideEncryption' => 'AES256'
                    ]);

                    $stmt = $pdo->prepare("
                        INSERT INTO documents
                            (entity_type, entity_id, title, s3_key, original_name, mime_type, size_bytes)
                        VALUES
                            (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $entityType,
                        $entityId ?: null,
                        $title,
                        $s3Key,
                        $originalName,
                        $mimeType,
                        $file['size']
                    ]);

                    flash('success', 'Document uploaded successfully.');
                    redirect('index.php');
                } catch (Throwable $e) {
                    error_log('S3 upload error: ' . $e->getMessage());

                    if ($s3Key) {
                        try {
                            $s3->deleteObject([
                                'Bucket' => $bucket,
                                'Key' => $s3Key
                            ]);
                        } catch (Throwable $cleanupError) {
                            error_log('S3 cleanup error: ' . $cleanupError->getMessage());
                        }
                    }

                    $error = 'Upload failed. Please try again.';
                }
            }
        }
    }
}

page_start('Upload Document', 'documents');
?>

<div class="page-header">
    <div>
        <h1>Upload Document</h1>
        <p>Upload a document for a vehicle, driver or tour.</p>
    </div>
    <a class="btn btn-secondary" href="index.php">Back</a>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<div class="panel">
    <form class="form-grid" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group full">
            <label>Title *</label>
            <input name="title" value="<?= e($_POST['title'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Entity Type</label>
            <select name="entity_type">
                <?php foreach (['VEHICLE', 'DRIVER', 'TOUR', 'OTHER'] as $option): ?>
                    <option value="<?= e($option) ?>" <?= ($_POST['entity_type'] ?? 'OTHER') === $option ? 'selected' : '' ?>>
                        <?= e($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Entity ID</label>
            <input type="number" name="entity_id" min="1" value="<?= e($_POST['entity_id'] ?? '') ?>">
        </div>

        <div class="form-group full">
            <label>Document *</label>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
            <div class="field-note">PDF, JPG or PNG. Maximum 10 MB.</div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Upload Document</button>
            <a class="btn btn-secondary" href="index.php">Cancel</a>
        </div>
    </form>
</div>

<?php page_end(); ?>
