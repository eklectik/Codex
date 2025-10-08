<?php
require_once __DIR__ . '/../lib/utils.php';

require_admin();

$pdo = saas_pdo();
$siteId = (int)($_GET['site_id'] ?? 0);
if ($siteId <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM sites WHERE id = :id');
$stmt->execute([':id' => $siteId]);
$site = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$site) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        flash('error', 'Jeton CSRF invalide.');
        header('Location: content.php?site_id=' . $siteId);
        exit;
    }
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create_page':
                $slug = strtolower(trim($_POST['slug'] ?? ''));
                $title = trim($_POST['title'] ?? '');
                $html = trim($_POST['html'] ?? '');
                $status = $_POST['status'] ?? 'published';
                if ($slug === '' || $title === '') {
                    throw new RuntimeException('Slug et titre requis.');
                }
                $stmt = $pdo->prepare('INSERT INTO pages (site_id, slug, title, html, status, updated_at) VALUES (:sid, :slug, :title, :html, :status, CURRENT_TIMESTAMP)');
                $stmt->execute([
                    ':sid' => $siteId,
                    ':slug' => $slug,
                    ':title' => $title,
                    ':html' => $html,
                    ':status' => $status,
                ]);
                flash('success', 'Page créée.');
                break;
            case 'update_page':
                $pageId = (int)($_POST['id'] ?? 0);
                $slug = strtolower(trim($_POST['slug'] ?? ''));
                $title = trim($_POST['title'] ?? '');
                $html = trim($_POST['html'] ?? '');
                $status = $_POST['status'] ?? 'published';
                if ($pageId <= 0) {
                    throw new RuntimeException('Page inconnue.');
                }
                $stmt = $pdo->prepare('UPDATE pages SET slug = :slug, title = :title, html = :html, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND site_id = :sid');
                $stmt->execute([
                    ':id' => $pageId,
                    ':sid' => $siteId,
                    ':slug' => $slug,
                    ':title' => $title,
                    ':html' => $html,
                    ':status' => $status,
                ]);
                flash('success', 'Page mise à jour.');
                break;
            case 'delete_page':
                $pageId = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM pages WHERE id = :id AND site_id = :sid');
                $stmt->execute([':id' => $pageId, ':sid' => $siteId]);
                flash('success', 'Page supprimée.');
                break;
            case 'create_post':
                $slug = strtolower(trim($_POST['slug'] ?? ''));
                $title = trim($_POST['title'] ?? '');
                $html = trim($_POST['html'] ?? '');
                $tags = trim($_POST['tags'] ?? '');
                $status = $_POST['status'] ?? 'published';
                $publishedAt = trim($_POST['published_at'] ?? '');
                if ($slug === '' || $title === '') {
                    throw new RuntimeException('Slug et titre requis.');
                }
                $stmt = $pdo->prepare('INSERT INTO posts (site_id, slug, title, html, tags, status, published_at, updated_at) VALUES (:sid, :slug, :title, :html, :tags, :status, :published_at, CURRENT_TIMESTAMP)');
                $stmt->execute([
                    ':sid' => $siteId,
                    ':slug' => $slug,
                    ':title' => $title,
                    ':html' => $html,
                    ':tags' => $tags,
                    ':status' => $status,
                    ':published_at' => $publishedAt !== '' ? $publishedAt : null,
                ]);
                flash('success', 'Article créé.');
                break;
            case 'update_post':
                $postId = (int)($_POST['id'] ?? 0);
                $slug = strtolower(trim($_POST['slug'] ?? ''));
                $title = trim($_POST['title'] ?? '');
                $html = trim($_POST['html'] ?? '');
                $tags = trim($_POST['tags'] ?? '');
                $status = $_POST['status'] ?? 'published';
                $publishedAt = trim($_POST['published_at'] ?? '');
                if ($postId <= 0) {
                    throw new RuntimeException('Article inconnu.');
                }
                $stmt = $pdo->prepare('UPDATE posts SET slug = :slug, title = :title, html = :html, tags = :tags, status = :status, published_at = :published_at, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND site_id = :sid');
                $stmt->execute([
                    ':id' => $postId,
                    ':sid' => $siteId,
                    ':slug' => $slug,
                    ':title' => $title,
                    ':html' => $html,
                    ':tags' => $tags,
                    ':status' => $status,
                    ':published_at' => $publishedAt !== '' ? $publishedAt : null,
                ]);
                flash('success', 'Article mis à jour.');
                break;
            case 'delete_post':
                $postId = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id AND site_id = :sid');
                $stmt->execute([':id' => $postId, ':sid' => $siteId]);
                flash('success', 'Article supprimé.');
                break;
            case 'update_menu':
                $itemsRaw = $_POST['items'] ?? '[]';
                $items = json_decode($itemsRaw, true);
                if (!is_array($items)) {
                    throw new RuntimeException('JSON du menu invalide.');
                }
                $stmt = $pdo->prepare('UPDATE menus SET items = :items, updated_at = CURRENT_TIMESTAMP WHERE site_id = :sid AND location = :loc');
                $stmt->execute([
                    ':items' => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ':sid' => $siteId,
                    ':loc' => 'primary',
                ]);
                if ($stmt->rowCount() === 0) {
                    $insert = $pdo->prepare('INSERT INTO menus (site_id, location, items, updated_at) VALUES (:sid, :loc, :items, CURRENT_TIMESTAMP)');
                    $insert->execute([
                        ':sid' => $siteId,
                        ':loc' => 'primary',
                        ':items' => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
                flash('success', 'Menu enregistré.');
                break;
            default:
                flash('error', 'Action inconnue.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    header('Location: content.php?site_id=' . $siteId);
    exit;
}

$pages = $pdo->prepare('SELECT * FROM pages WHERE site_id = :sid ORDER BY slug');
$pages->execute([':sid' => $siteId]);
$pages = $pages->fetchAll(PDO::FETCH_ASSOC);

$posts = $pdo->prepare('SELECT * FROM posts WHERE site_id = :sid ORDER BY COALESCE(published_at, updated_at) DESC');
$posts->execute([':sid' => $siteId]);
$posts = $posts->fetchAll(PDO::FETCH_ASSOC);

$menuStmt = $pdo->prepare('SELECT * FROM menus WHERE site_id = :sid AND location = :loc');
$menuStmt->execute([':sid' => $siteId, ':loc' => 'primary']);
$menu = $menuStmt->fetch(PDO::FETCH_ASSOC);
if ($menu) {
    $menuItemsArray = json_decode($menu['items'] ?: '[]', true);
    if (!is_array($menuItemsArray)) {
        $menuItemsArray = [];
    }
} else {
    $menuItemsArray = [
        ['label' => 'Accueil', 'href' => '/'],
        ['label' => 'Blog', 'href' => '/blog'],
    ];
}
$menuItems = json_encode($menuItemsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$success = flash('success');
$error = flash('error');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contenu du site - PBN Control SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">PBN Control</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white-50 small">Site #<?= (int)$site['id'] ?> - <?= htmlspecialchars($site['name'], ENT_QUOTES) ?></span>
            <a class="btn btn-outline-light btn-sm" href="auth.php?action=logout">Déconnexion</a>
        </div>
    </div>
</nav>
<div class="container py-4">
    <div id="push-feedback" class="flash">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
        <?php endif; ?>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pages" type="button" role="tab">Pages</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">Articles</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#menus" type="button" role="tab">Menus</button>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="pages" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Pages</h2>
                <form method="post" action="api.php/api/v1/push" class="js-push" data-feedback="#push-feedback">
                    <?= csrf_field() ?>
                    <input type="hidden" name="site_id" value="<?= (int)$siteId ?>">
                    <button class="btn btn-success btn-sm" type="submit">Push maintenant</button>
                </form>
            </div>
            <div class="card mb-4">
                <div class="card-header">Nouvelle page</div>
                <div class="card-body">
                    <form method="post" class="vstack gap-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_page">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Titre</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Statut</label>
                                <select name="status" class="form-select">
                                    <option value="published">Publié</option>
                                    <option value="draft">Brouillon</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">HTML</label>
                            <textarea name="html" class="form-control" rows="4" placeholder="<h1>Titre</h1>"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Créer la page</button>
                    </form>
                </div>
            </div>
            <?php foreach ($pages as $page): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?= htmlspecialchars($page['title'], ENT_QUOTES) ?></strong>
                        <form method="post" onsubmit="return confirm('Supprimer cette page ?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_page">
                            <input type="hidden" name="id" value="<?= (int)$page['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Supprimer</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <form method="post" class="vstack gap-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_page">
                            <input type="hidden" name="id" value="<?= (int)$page['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($page['slug'], ENT_QUOTES) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Titre</label>
                                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($page['title'], ENT_QUOTES) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Statut</label>
                                    <select name="status" class="form-select">
                                        <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Publié</option>
                                        <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">HTML</label>
                                <textarea name="html" class="form-control" rows="4"><?= htmlspecialchars($page['html'], ENT_QUOTES) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Mettre à jour</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="tab-pane fade" id="posts" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Articles</h2>
                <form method="post" action="api.php/api/v1/push" class="js-push" data-feedback="#push-feedback">
                    <?= csrf_field() ?>
                    <input type="hidden" name="site_id" value="<?= (int)$siteId ?>">
                    <button class="btn btn-success btn-sm" type="submit">Push maintenant</button>
                </form>
            </div>
            <div class="card mb-4">
                <div class="card-header">Nouvel article</div>
                <div class="card-body">
                    <form method="post" class="vstack gap-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_post">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Titre</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tags (séparés par des virgules)</label>
                                <input type="text" name="tags" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Statut</label>
                                <select name="status" class="form-select">
                                    <option value="published">Publié</option>
                                    <option value="draft">Brouillon</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Date publication</label>
                                <input type="datetime-local" name="published_at" class="form-control">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">HTML</label>
                            <textarea name="html" class="form-control" rows="4"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Créer l'article</button>
                    </form>
                </div>
            </div>
            <?php foreach ($posts as $post): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?= htmlspecialchars($post['title'], ENT_QUOTES) ?></strong>
                        <form method="post" onsubmit="return confirm('Supprimer cet article ?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_post">
                            <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Supprimer</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <form method="post" class="vstack gap-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_post">
                            <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($post['slug'], ENT_QUOTES) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Titre</label>
                                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tags</label>
                                    <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars($post['tags'], ENT_QUOTES) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Statut</label>
                                    <select name="status" class="form-select">
                                        <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Publié</option>
                                        <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Date publication</label>
                                    <input type="datetime-local" name="published_at" class="form-control" value="<?= $post['published_at'] ? htmlspecialchars(str_replace(' ', 'T', substr($post['published_at'], 0, 16)), ENT_QUOTES) : '' ?>">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">HTML</label>
                                <textarea name="html" class="form-control" rows="4"><?= htmlspecialchars($post['html'], ENT_QUOTES) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Mettre à jour</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="tab-pane fade" id="menus" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Menu principal</h2>
                <form method="post" action="api.php/api/v1/push" class="js-push" data-feedback="#push-feedback">
                    <?= csrf_field() ?>
                    <input type="hidden" name="site_id" value="<?= (int)$siteId ?>">
                    <button class="btn btn-success btn-sm" type="submit">Push maintenant</button>
                </form>
            </div>
            <div class="card">
                <div class="card-body">
                    <form method="post" class="vstack gap-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_menu">
                        <label class="form-label">Items (JSON)</label>
                        <textarea name="items" class="form-control" rows="8"><?= htmlspecialchars($menuItems, ENT_QUOTES) ?></textarea>
                        <button class="btn btn-primary" type="submit">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
