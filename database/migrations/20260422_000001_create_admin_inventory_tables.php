<?php
declare(strict_types=1);

function migrationEnv(string $key, string $default = ''): string
{
    $value = getenv($key);
    return is_string($value) && $value !== '' ? $value : $default;
}

$dbHost = migrationEnv('DB_HOST', 'localhost');
$dbPort = migrationEnv('DB_PORT', '3306');
$dbName = migrationEnv('DB_NAME', 'online_furniture_store');
$dbUser = migrationEnv('DB_USER', 'root');
$dbPass = migrationEnv('DB_PASS', '');

$dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbHost, $dbPort);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$statements = [
    sprintf(
        "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        str_replace('`', '``', $dbName)
    ),
    sprintf("USE `%s`", str_replace('`', '``', $dbName)),
    "CREATE TABLE IF NOT EXISTS `users` (
        `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `full_name` VARCHAR(120) NOT NULL,
        `email` VARCHAR(190) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` VARCHAR(50) NOT NULL DEFAULT 'admin',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_id`),
        UNIQUE KEY `users_email_unique` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `categories` (
        `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `category_name` VARCHAR(120) NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`category_id`),
        UNIQUE KEY `categories_name_unique` (`category_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `furniture_items` (
        `item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `item_name` VARCHAR(100) NOT NULL,
        `description` TEXT NOT NULL,
        `price` DECIMAL(10, 2) NOT NULL,
        `stock_quantity` INT NOT NULL DEFAULT 0,
        `image` VARCHAR(255) DEFAULT NULL,
        `category_id` INT UNSIGNED DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`item_id`),
        KEY `furniture_items_category_id_index` (`category_id`),
        CONSTRAINT `furniture_items_category_id_foreign`
            FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
            ON UPDATE CASCADE
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "INSERT INTO `categories` (`category_name`)
     VALUES
        ('Living Room'),
        ('Bedroom'),
        ('Dining Room'),
        ('Office')
     ON DUPLICATE KEY UPDATE `category_name` = VALUES(`category_name`)",
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    $message = 'Migration completed successfully.';

    if (PHP_SAPI === 'cli') {
        fwrite(STDOUT, $message . PHP_EOL);
        exit(0);
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
} catch (Throwable $exception) {
    $message = 'Migration failed: ' . $exception->getMessage();

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
}
