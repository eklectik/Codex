<?php
$config = require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Authorization manquante'], JSON_UNESCAPED_UNICODE);
    exit;
}
$token = trim($matches[1]);
if (!hash_equals($config['site_token'], $token)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Token invalide'], JSON_UNESCAPED_UNICODE);
    exit;
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON invalide'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pages = $data['pages'] ?? [];
$posts = $data['posts'] ?? [];
$menus = $data['menus'] ?? [];

if (!is_array($pages) || !is_array($posts) || !is_array($menus)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Schéma inattendu'], JSON_UNESCAPED_UNICODE);
    exit;
}

$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

file_put_contents($storageDir . '/pages.json', json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
file_put_contents($storageDir . '/posts.json', json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
file_put_contents($storageDir . '/menus.json', json_encode($menus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo json_encode([
    'ok' => true,
    'stored' => [
        'pages' => count($pages),
        'posts' => count($posts),
        'menus' => count($menus),
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
