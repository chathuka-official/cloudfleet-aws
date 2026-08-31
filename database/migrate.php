<?php

require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$allowHttp = ($_SERVER['ALLOW_MIGRATIONS'] ?? getenv('ALLOW_MIGRATIONS') ?: '') === '1';

if (!$isCli && !$allowHttp) {
    http_response_code(403);
    die('Migrations are disabled. Set ALLOW_MIGRATIONS=1 temporarily, run the migration, then remove it.');
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetch();
}

function index_exists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
    $stmt->execute([$index]);
    return (bool)$stmt->fetch();
}

try {
    $schemaFile = __DIR__ . '/migrations/001_cloudfleet_schema.sql';
    $sql = file_get_contents($schemaFile);
    $statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql)));

    foreach ($statements as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }

    if (!column_exists($pdo, 'documents', 'deleted_at')) {
        $pdo->exec("ALTER TABLE documents ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL");
    }

    if (!column_exists($pdo, 'documents', 'delete_marker_version_id')) {
        $pdo->exec("ALTER TABLE documents ADD COLUMN delete_marker_version_id VARCHAR(255) NULL DEFAULT NULL");
    }

    if (!index_exists($pdo, 'documents', 'idx_documents_deleted_at')) {
        $pdo->exec("CREATE INDEX idx_documents_deleted_at ON documents(deleted_at)");
    }

    echo 'CloudFleet database migration completed successfully.';
} catch (PDOException $e) {
    error_log('Migration failed: ' . $e->getMessage());
    http_response_code(500);
    die('Migration failed. Check the server logs for details.');
}
