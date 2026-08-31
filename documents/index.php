<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$documents = $pdo->query("
    SELECT *
    FROM documents
    WHERE deleted_at IS NULL
    ORDER BY created_at DESC
")->fetchAll();

$s3Bucket = $_SERVER['S3_BUCKET'] ?? getenv('S3_BUCKET') ?: '';

page_start('Documents', 'documents');
?>

<div class="page-header">
    <div>
        <h1>Documents</h1>
        <p>Private Amazon S3 document storage for CloudFleet.</p>
    </div>

    <div class="actions">
        <a class="btn btn-secondary" href="trash.php">Recycle Bin</a>
        <?php if ($s3Bucket): ?>
            <a class="btn btn-primary" href="upload.php">+ Upload Document</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$s3Bucket): ?>
    <div class="alert warning">Amazon S3 is not configured for this environment.</div>
<?php endif; ?>

<div class="panel table-panel">
<?php if (!$documents): ?>
    <div class="empty-state">No active documents.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Title</th>
                <th>Entity</th>
                <th>File</th>
                <th>Size</th>
                <th>Uploaded</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($documents as $d): ?>
                <tr>
                    <td><?= e($d['title']) ?></td>
                    <td>
                        <?= e($d['entity_type']) ?>
                        <?= !empty($d['entity_id']) ? '#' . (int)$d['entity_id'] : '' ?>
                    </td>
                    <td><?= e($d['original_name']) ?></td>
                    <td><?= !empty($d['size_bytes']) ? number_format(((int)$d['size_bytes']) / 1024, 1) . ' KB' : '-' ?></td>
                    <td><?= e($d['created_at']) ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-secondary" href="download.php?id=<?= (int)$d['id'] ?>">Download</a>

                            <form method="POST" action="delete.php" onsubmit="return confirm('Move this document to the recycle bin?')">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>

<?php page_end(); ?>
