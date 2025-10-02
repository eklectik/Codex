<?php
require __DIR__ . '/../bootstrap.php';
$slug = $_GET['slug'] ?? '';
$page = $slug ? findPageBySlug($slug) : null;
if (!$page || (int) $page['is_published'] !== 1) {
    http_response_code(404);
    echo '<h1>Page introuvable</h1>';
    exit;
}
$menuPages = findMenuPages();
$posts = findPages(true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title']) ?> - Carburants Malins</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
<header class="site-header">
    <div class="branding">
        <h1>Carburants Malins</h1>
    </div>
    <nav class="main-nav">
        <a href="/">Accueil</a>
        <?php foreach ($menuPages as $menuPage): ?>
            <a href="/page.php?slug=<?= htmlspecialchars($menuPage['slug']) ?>" class="<?= $menuPage['slug'] === $slug ? 'active' : '' ?>">
                <?= htmlspecialchars($menuPage['menu_label'] ?: $menuPage['title']) ?>
            </a>
        <?php endforeach; ?>
        <?php if ($posts): ?>
            <a href="/blog.php">Blog</a>
        <?php endif; ?>
        <a href="/admin/index.php" class="admin-link">Administration</a>
    </nav>
</header>
<main class="page-content">
    <article class="page">
        <h2><?= htmlspecialchars($page['title']) ?></h2>
        <div class="page-body"><?= $page['content'] ?></div>
    </article>
</main>
<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> Carburants Malins</p>
</footer>
</body>
</html>
