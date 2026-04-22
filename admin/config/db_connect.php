<?php
declare(strict_types=1);

// if (!defined('DB_HOST')) {
//     define('DB_HOST', 'localhost');
// }

// if (!defined('DB_NAME')) {
//     define('DB_NAME', 'online_furniture_store');
// }

// if (!defined('DB_USER')) {
//     define('DB_USER', 'root');
// }

// if (!defined('DB_PASS')) {
//     define('DB_PASS', '');
// }

$dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'online_furniture_store';
$dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';

if (!isset($pdo) || !($pdo instanceof PDO)) {
   
    // $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        // $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    } catch (PDOException $exception) {
        http_response_code(500);
        exit('Database connection failed. Please verify the local database settings.');
    }
}
