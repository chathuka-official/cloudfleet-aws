<?php

require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';

$allowHttp =
    ($_SERVER['ALLOW_MIGRATIONS']
    ?? getenv('ALLOW_MIGRATIONS')
    ?: '') === '1';

if (!$isCli && !$allowHttp) {

    http_response_code(403);

    die(
        'Migrations are disabled. '
        . 'Set ALLOW_MIGRATIONS=1 temporarily.'
    );
}

try {

    /*
     * Check if deleted_at exists
     */
    $stmt = $pdo->query("
        SHOW COLUMNS
        FROM documents
        LIKE 'deleted_at'
    ");

    if (!$stmt->fetch()) {

        $pdo->exec("
            ALTER TABLE documents
            ADD COLUMN deleted_at
            DATETIME NULL DEFAULT NULL
        ");

        echo "Added deleted_at.<br>";
    }


    /*
     * Check if delete_marker_version_id exists
     */
    $stmt = $pdo->query("
        SHOW COLUMNS
        FROM documents
        LIKE 'delete_marker_version_id'
    ");

    if (!$stmt->fetch()) {

        $pdo->exec("
            ALTER TABLE documents
            ADD COLUMN delete_marker_version_id
            VARCHAR(255) NULL DEFAULT NULL
        ");

        echo "Added delete_marker_version_id.<br>";
    }


    /*
     * Check if index exists
     */
    $stmt = $pdo->query("
        SHOW INDEX
        FROM documents
        WHERE Key_name = 'idx_documents_deleted_at'
    ");

    if (!$stmt->fetch()) {

        $pdo->exec("
            CREATE INDEX idx_documents_deleted_at
            ON documents(deleted_at)
        ");

        echo "Added deleted_at index.<br>";
    }


    echo "<br><strong>CloudFleet migration completed successfully.</strong>";


} catch (PDOException $e) {

    error_log(
        'Migration error: '
        . $e->getMessage()
    );

    http_response_code(500);

    echo "Migration failed: "
        . htmlspecialchars($e->getMessage());
}