<?php
$config = require __DIR__ . '/config.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

if (($config['mode'] ?? 'push') !== 'pull') {
    echo "Site configuré en mode push, aucune action.\n";
    exit;
}

$endpoint = rtrim($config['saas_api_url'], '/') . '/api/v1/site/bundle';
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $config['site_token'],
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 20,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo "Erreur cURL : {$error}\n";
    exit(1);
}

if ($httpCode >= 300 || $httpCode === 0) {
    echo "Erreur HTTP {$httpCode} : {$response}\n";
    exit(1);
}

$data = json_decode($response, true);
if (!is_array($data)) {
    echo "Réponse JSON invalide.\n";
    exit(1);
}

$pages = $data['pages'] ?? [];
$posts = $data['posts'] ?? [];
$menus = $data['menus'] ?? [];

$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

file_put_contents($storageDir . '/pages.json', json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
file_put_contents($storageDir . '/posts.json', json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
file_put_contents($storageDir . '/menus.json', json_encode($menus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo sprintf("updated %d pages / %d posts / %d menus\n", count($pages), count($posts), count($menus));
