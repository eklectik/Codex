<?php
require_once __DIR__ . '/../lib/utils.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = saas_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pathInfo = $_SERVER['PATH_INFO'] ?? '';
if ($pathInfo === '') {
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $pathInfo = substr($requestUri, strlen($_SERVER['SCRIPT_NAME']));
    if ($pathInfo === false) {
        $pathInfo = '/';
    }
}
$path = rtrim($pathInfo, '/') ?: '/';

switch ($path) {
    case '/api/v1/sites':
        if ($method === 'GET') {
            if (!($_SESSION['admin'] ?? false)) {
                json_out(['error' => 'Unauthorized'], 401);
            }
            $sites = $pdo->query('SELECT id, name, domain, mode, is_active, last_deploy FROM sites ORDER BY id DESC')->fetchAll();
            json_out(['sites' => $sites]);
        }
        if ($method === 'POST') {
            if (!($_SESSION['admin'] ?? false)) {
                json_out(['error' => 'Unauthorized'], 401);
            }
            csrf_check();
            $name = trim($_POST['name'] ?? '');
            $domain = strtolower(trim($_POST['domain'] ?? ''));
            $mode = $_POST['mode'] ?? 'push';
            if ($name === '' || $domain === '') {
                json_out(['error' => 'Champs requis manquants.'], 400);
            }
            if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) {
                json_out(['error' => 'Domaine invalide.'], 422);
            }
            if (!in_array($mode, ['push', 'pull'], true)) {
                json_out(['error' => 'Mode invalide.'], 422);
            }
            try {
                $token = rand_token(64);
                $stmt = $pdo->prepare('INSERT INTO sites (name, domain, api_token, mode, theme, is_active, created_at) VALUES (:name, :domain, :token, :mode, :theme, 1, CURRENT_TIMESTAMP)');
                $stmt->execute([
                    ':name' => $name,
                    ':domain' => $domain,
                    ':token' => $token,
                    ':mode' => $mode,
                    ':theme' => 'basic',
                ]);
                json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'token' => $token]);
            } catch (PDOException $e) {
                json_out(['error' => 'Erreur SQL : ' . $e->getMessage()], 500);
            }
        }
        break;
    case '/api/v1/site/bundle':
        if ($method !== 'GET') {
            break;
        }
        $token = bearer_token();
        if (!$token) {
            json_out(['error' => 'Authorization manquante.'], 401);
        }
        $site = site_by_token($pdo, $token);
        if (!$site) {
            json_out(['error' => 'Site introuvable pour ce token.'], 404);
        }
        $bundle = bundle_for_site($pdo, (int)$site['id']);
        $bundle['site'] = [
            'id' => (int)$site['id'],
            'domain' => $site['domain'],
            'mode' => $site['mode'],
        ];
        json_out($bundle);
        break;
    case '/api/v1/push':
        if ($method !== 'POST') {
            break;
        }
        if (!($_SESSION['admin'] ?? false)) {
            json_out(['error' => 'Unauthorized'], 401);
        }
        csrf_check();
        $siteId = (int)($_POST['site_id'] ?? 0);
        if ($siteId <= 0) {
            json_out(['error' => 'Paramètre site_id requis.'], 400);
        }
        $stmt = $pdo->prepare('SELECT * FROM sites WHERE id = :id');
        $stmt->execute([':id' => $siteId]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$site) {
            json_out(['error' => 'Site inconnu.'], 404);
        }
        if (!$site['is_active']) {
            json_out(['error' => 'Site inactif.'], 400);
        }
        $bundle = bundle_for_site($pdo, $siteId);
        $payload = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $targetUrl = 'https://' . $site['domain'] . '/sync.php';
        $ch = curl_init($targetUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $site['api_token'],
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 15,
        ]);
        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $logStmt = $pdo->prepare('INSERT INTO deploy_logs (site_id, action, payload, created_at) VALUES (:sid, :action, :payload, CURRENT_TIMESTAMP)');
        $logStmt->execute([
            ':sid' => $siteId,
            ':action' => 'push',
            ':payload' => $payload,
        ]);

        if ($responseBody === false && $curlError) {
            json_out(['error' => 'cURL error: ' . $curlError], 500);
        }
        if ($httpCode >= 300 || $httpCode === 0) {
            json_out([
                'error' => 'Réponse distante invalide.',
                'status' => $httpCode,
                'body' => $responseBody,
            ], 502);
        }
        $pdo->prepare('UPDATE sites SET last_deploy = CURRENT_TIMESTAMP WHERE id = :id')->execute([':id' => $siteId]);
        json_out(['ok' => true, 'status' => $httpCode, 'body' => $responseBody]);
        break;
    default:
        json_out(['error' => 'Route introuvable', 'path' => $path], 404);
}

json_out(['error' => 'Méthode non autorisée'], 405);
