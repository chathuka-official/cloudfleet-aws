<?php

require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$allowHttp = ($_SERVER['ALLOW_MIGRATIONS'] ?? getenv('ALLOW_MIGRATIONS') ?: '') === '1';

if (!$isCli && !$allowHttp) {
    http_response_code(403);
    die('Migrations are disabled. Set ALLOW_MIGRATIONS=1 temporarily, then remove it after setup.');
}

$sql = file_get_contents(__DIR__ . '/migrations/001_cloudfleet_schema.sql');
$statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql)));

try {
    foreach ($statements as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }

    echo "CloudFleet database schema is ready.";
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    die('Migration failed: ' . htmlspecialchars($e->getMessage()));
}
