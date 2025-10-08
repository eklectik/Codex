<?php
require_once __DIR__ . '/../lib/utils.php';

require_admin();

$pdo = saas_pdo();
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_site') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Jeton CSRF invalide.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $domain = strtolower(trim($_POST['domain'] ?? ''));
        $mode = $_POST['mode'] ?? 'push';
        if ($name === '' || $domain === '') {
            $error = 'Le nom et le domaine sont obligatoires.';
        } elseif (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) {
            $error = 'Le domaine doit être valide (ex: exemple.fr).';
        } elseif (!in_array($mode, ['push', 'pull'], true)) {
            $error = 'Mode invalide.';
        } else {
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
                $message = 'Site ajouté avec succès. Token : ' . $token;
            } catch (PDOException $e) {
                $error = 'Erreur lors de la création : ' . $e->getMessage();
            }
        }
    }
}

$sites = $pdo->query('SELECT * FROM sites ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Dashboard - PBN Control SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">PBN Control</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white-50 small">Connecté</span>
            <a class="btn btn-outline-light btn-sm" href="auth.php?action=logout">Déconnexion</a>
        </div>
    </div>
</nav>
<div class="container py-4">
    <div id="push-feedback" class="flash">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
        <?php endif; ?>
    </div>
    <div class="card mb-4">
        <div class="card-header">Ajouter un site</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="create_site">
                <?= csrf_field() ?>
                <div class="col-md-4">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Domaine</label>
                    <input type="text" name="domain" class="form-control" placeholder="exemple.com" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mode</label>
                    <select name="mode" class="form-select">
                        <option value="push">Push</option>
                        <option value="pull">Pull</option>
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Sites</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Domaine</th>
                    <th>Mode</th>
                    <th>Actif</th>
                    <th>Dernier déploiement</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sites as $site): ?>
                    <tr>
                        <td><?= (int)$site['id'] ?></td>
                        <td><?= htmlspecialchars($site['name'], ENT_QUOTES) ?></td>
                        <td><a href="https://<?= htmlspecialchars($site['domain'], ENT_QUOTES) ?>" target="_blank" rel="noopener">https://<?= htmlspecialchars($site['domain'], ENT_QUOTES) ?></a></td>
                        <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($site['mode'], ENT_QUOTES) ?></span></td>
                        <td><?= $site['is_active'] ? 'Oui' : 'Non' ?></td>
                        <td><?= $site['last_deploy'] ?: '—' ?></td>
                        <td class="d-flex gap-2">
                            <a class="btn btn-outline-primary btn-sm" href="content.php?site_id=<?= (int)$site['id'] ?>">Contenu</a>
                            <form method="post" action="api.php/api/v1/push" class="js-push" data-feedback="#push-feedback">
                                <?= csrf_field() ?>
                                <input type="hidden" name="site_id" value="<?= (int)$site['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">Push</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
