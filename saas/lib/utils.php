<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Outputs JSON and halts execution.
 */
function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Generates a random hexadecimal token.
 */
function rand_token(int $len = 64): string
{
    return bin2hex(random_bytes((int) max(1, $len / 2)));
}

/**
 * Extracts the Bearer token from the Authorization header.
 */
function bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$header && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? '';
    }
    if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

/**
 * Returns the site row that owns the provided API token.
 */
function site_by_token(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM sites WHERE api_token = :token AND is_active = 1');
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Builds the bundle payload for a given site.
 */
function bundle_for_site(PDO $pdo, int $siteId): array
{
    $pages = $pdo->prepare('SELECT slug, title, html, status, updated_at FROM pages WHERE site_id = :sid ORDER BY slug');
    $pages->execute([':sid' => $siteId]);
    $posts = $pdo->prepare('SELECT slug, title, html, tags, status, published_at, updated_at FROM posts WHERE site_id = :sid ORDER BY COALESCE(published_at, updated_at) DESC, slug');
    $posts->execute([':sid' => $siteId]);
    $menus = $pdo->prepare('SELECT location, items, updated_at FROM menus WHERE site_id = :sid');
    $menus->execute([':sid' => $siteId]);

    $menuData = [];
    foreach ($menus as $menu) {
        $items = json_decode($menu['items'] ?? '[]', true);
        if (!is_array($items)) {
            $items = [];
        }
        $menuData[] = [
            'location' => $menu['location'],
            'items' => $items,
            'updated_at' => $menu['updated_at'],
        ];
    }

    return [
        'generated_at' => gmdate('c'),
        'pages' => $pages->fetchAll(PDO::FETCH_ASSOC),
        'posts' => $posts->fetchAll(PDO::FETCH_ASSOC),
        'menus' => $menuData,
    ];
}

/**
 * Ensures the current user is authenticated as admin.
 */
function require_admin(): void
{
    if (!($_SESSION['admin'] ?? false)) {
        header('Location: auth.php');
        exit;
    }
}

/**
 * Validates credentials and sets the admin session flag.
 */
function try_admin_login(string $email, string $password): bool
{
    global $config;
    if ($email === $config['admin']['email'] && $password === $config['admin']['password']) {
        $_SESSION['admin'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        return true;
    }
    return false;
}

/**
 * Destroys the admin session.
 */
function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Outputs a hidden CSRF field.
 */
function csrf_field(): string
{
    $token = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
}

/**
 * Validates the CSRF token in a POST request.
 */
function csrf_check(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_out(['error' => 'Invalid CSRF token'], 400);
    }
}

/**
 * Simple flash messaging helpers.
 */
function flash(string $key, ?string $message = null)
{
    if ($message === null) {
        if (!empty($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    }
    $_SESSION['flash'][$key] = $message;
}
