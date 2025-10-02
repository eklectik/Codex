<?php
$config = require __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $dbPath = $config['db_path'];
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0777, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    migrate($pdo);

    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS stations (
        id TEXT PRIMARY KEY,
        name TEXT,
        address TEXT,
        postal_code TEXT,
        city TEXT,
        latitude REAL,
        longitude REAL,
        last_updated TEXT
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS fuels (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        station_id TEXT NOT NULL,
        fuel_code TEXT NOT NULL,
        fuel_name TEXT NOT NULL,
        price REAL NOT NULL,
        last_update TEXT,
        FOREIGN KEY(station_id) REFERENCES stations(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT UNIQUE NOT NULL,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        is_blog INTEGER NOT NULL DEFAULT 0,
        is_published INTEGER NOT NULL DEFAULT 1,
        menu_label TEXT,
        menu_order INTEGER DEFAULT 0,
        show_in_menu INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )');
}

function findPages(bool $isBlog = false): array
{
    $stmt = db()->prepare('SELECT * FROM pages WHERE is_blog = :is_blog AND is_published = 1 ORDER BY is_blog, menu_order, title');
    $stmt->execute(['is_blog' => $isBlog ? 1 : 0]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function findMenuPages(): array
{
    $stmt = db()->query('SELECT * FROM pages WHERE show_in_menu = 1 AND is_published = 1 AND is_blog = 0 ORDER BY menu_order ASC, title ASC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function findPageBySlug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM pages WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    return $page ?: null;
}

function slugify(string $text): string
{
    $text = preg_replace('~[\p{Pd}\s]+~u', '-', $text);
    $converted = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    if ($converted !== false) {
        $text = $converted;
    }
    $text = preg_replace('~[^\w-]+~', '', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return $text ?: 'page-' . bin2hex(random_bytes(3));
}

function formatPrice(?float $price): string
{
    return $price === null ? 'N/A' : number_format($price, 3, ',', ' ') . ' €';
}

function respondJson(array $data): void
{
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

