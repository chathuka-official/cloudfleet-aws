<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$documents = $pdo->query("SELECT * FROM documents ORDER BY created_at DESC")->fetchAll();

$s3Bucket = $_SERVER['S3_BUCKET'] ?? getenv('S3_BUCKET') ?: '';

page_start('Documents', 'documents');
?>

<div class="page-header">
    <div><h1>Documents</h1><p>Amazon S3-backed CloudFleet document storage.</p></div>
    <?php if ($s3Bucket): ?><a class="btn btn-primary" href="upload.php">+ Upload Document</a><?php endif; ?>
</div>

<?php if (!$s3Bucket): ?>
<div class="alert warning">
    S3 is not configured yet. This is intentional — the next AWS lesson is to create the S3 bucket, IAM permissions, and S3_BUCKET environment property.
</div>
<?php endif; ?>

<div class="panel">
<?php if (!$documents): ?>
    <div class="empty-state">No documents stored yet.</div>
<?php else: ?>
<table>
<thead><tr><th>Title</th><th>Entity</th><th>File</th><th>Size</th><th>Uploaded</th></tr></thead>
<tbody>
<?php foreach ($documents as $d): ?>
<tr>
    <td><?= e($d['title']) ?></td>
    <td><?= e($d['entity_type']) ?><?= $d['entity_id'] ? ' #'.(int)$d['entity_id'] : '' ?></td>
    <td><?= e($d['original_name']) ?></td>
    <td><?= $d['size_bytes'] ? number_format(((int)$d['size_bytes'])/1024,1).' KB' : '-' ?></td>
    <td><?= e($d['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<?php page_end(); ?>
