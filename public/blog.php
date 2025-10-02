<?php
require __DIR__ . '/../bootstrap.php';
$posts = findPages(true);
$menuPages = findMenuPages();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - Carburants Malins</title>
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
            <a href="/page.php?slug=<?= htmlspecialchars($menuPage['slug']) ?>"><?= htmlspecialchars($menuPage['menu_label'] ?: $menuPage['title']) ?></a>
        <?php endforeach; ?>
        <a href="/blog.php" class="active">Blog</a>
        <a href="/admin/index.php" class="admin-link">Administration</a>
    </nav>
</header>
<main class="page-content">
    <h2>Blog</h2>
    <?php if (!$posts): ?>
        <p>Aucun article publié pour le moment.</p>
    <?php else: ?>
        <div class="blog-list">
            <?php foreach ($posts as $post): ?>
                <article class="blog-post">
                    <h3><a href="/page.php?slug=<?= htmlspecialchars($post['slug']) ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
                    <p class="meta">Publié le <?= date('d/m/Y', strtotime($post['created_at'])) ?></p>
                    <div class="excerpt"><?= mb_substr(strip_tags($post['content']), 0, 200) ?>...</div>
                    <a class="btn-secondary" href="/page.php?slug=<?= htmlspecialchars($post['slug']) ?>">Lire la suite</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> Carburants Malins</p>
</footer>
</body>
</html>
