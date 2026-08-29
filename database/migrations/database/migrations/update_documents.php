<?php

require_once __DIR__ . '/../config/database.php';

try {

    $pdo->exec("
        ALTER TABLE documents
        ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL,
        ADD COLUMN delete_marker_version_id VARCHAR(255) NULL DEFAULT NULL
    ");

    $pdo->exec("
        CREATE INDEX idx_documents_deleted_at
        ON documents(deleted_at)
    ");

    echo "SUCCESS: Documents table updated.";

} catch (PDOException $e) {

    echo "ERROR: " . $e->getMessage();

}