<?php
declare(strict_types=1);

/**
 * Loads config.php once. Stops with a JSON 500 when the CMS has not been
 * configured, rather than failing later with a less obvious error.
 */
function cms_config(): array
{
    static $config = null;
    if ($config === null) {
        $file = __DIR__ . '/config.php';
        if (!is_file($file)) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'CMS is not configured: copy config.sample.php to config.php.']);
            exit;
        }
        $config = require $file;
    }
    return $config;
}

/**
 * Shared PDO connection. Native prepared statements (EMULATE_PREPARES off) so
 * parameters are always sent separately from the SQL text.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = cms_config()['db'];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) ($c['port'] ?? 3306), $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
    }
    return $pdo;
}
