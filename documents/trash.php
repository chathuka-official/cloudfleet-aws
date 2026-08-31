<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$documents = $pdo->query("
    SELECT *
    FROM documents
    WHERE deleted_at IS NOT NULL
    ORDER BY deleted_at DESC
")->fetchAll();

page_start('Document Recycle Bin', 'documents');
?>

<div class="page-header">
    <div>
        <h1>Recycle Bin</h1>
        <p>Deleted S3 documents that can still be restored.</p>
    </div>
    <a class="btn btn-secondary" href="index.php">Back to Documents</a>
</div>

<div class="panel table-panel">
<?php if (!$documents): ?>
    <div class="empty-state">Recycle bin is empty.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Title</th>
                <th>Entity</th>
                <th>File</th>
                <th>Deleted</th>
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
                    <td><?= e($d['deleted_at']) ?></td>
                    <td>
                        <form method="POST" action="restore.php" onsubmit="return confirm('Restore this document?')">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                            <button type="submit" class="btn btn-primary">Restore</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>

<?php page_end(); ?>
