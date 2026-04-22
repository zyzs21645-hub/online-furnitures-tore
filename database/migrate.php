<?php
declare(strict_types=1);

$migrationFile = __DIR__ . '/migrations/20260422_000001_create_admin_inventory_tables.php';

if (!is_file($migrationFile)) {
    fwrite(STDERR, "Migration file not found: {$migrationFile}" . PHP_EOL);
    exit(1);
}

require_once $migrationFile;

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'online_furniture_store';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbHost, $dbPort);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    runAdminInventoryMigration($pdo, $dbName);
    fwrite(STDOUT, 'Migration completed successfully.' . PHP_EOL);
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
