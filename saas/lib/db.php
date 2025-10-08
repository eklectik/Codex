<?php
/**
 * PDO connection helpers.
 */

$config = require __DIR__ . '/../config.php';

/**
 * Returns a singleton PDO instance.
 */
function saas_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $config;
    $driver = $config['database']['driver'] ?? 'sqlite';

    if ($driver === 'mysql') {
        $mysql = $config['database']['mysql'];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $mysql['host'],
            $mysql['port'],
            $mysql['database'],
            $mysql['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $mysql['username'], $mysql['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        $sqlitePath = $config['database']['sqlite']['path'];
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $firstCreate = !file_exists($sqlitePath);
        $pdo = new PDO('sqlite:' . $sqlitePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        if ($firstCreate) {
            saas_bootstrap_sqlite($pdo);
        }
    }

    return $pdo;
}

/**
 * Applies the SQLite schema and demo seed if the database is new.
 */
function saas_bootstrap_sqlite(PDO $pdo): void
{
    $schemaFile = __DIR__ . '/../sql/schema.sqlite.sql';
    if (!file_exists($schemaFile)) {
        return;
    }
    $sql = file_get_contents($schemaFile);
    $pdo->exec($sql);
}
