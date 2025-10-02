<?php
require __DIR__ . '/../../bootstrap.php';

$pages = db()->query('SELECT * FROM pages WHERE is_blog = 0 ORDER BY menu_order, title')->fetchAll(PDO::FETCH_ASSOC);
$posts = db()->query('SELECT * FROM pages WHERE is_blog = 1 ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Carburants Malins</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="admin">
<header class="site-header">
    <div class="branding">
        <h1>Administration</h1>
    </div>
    <nav class="main-nav">
        <a href="/">Retour au site</a>
        <a href="/admin/index.php" class="active">Tableau de bord</a>
    </nav>
</header>
<main class="admin-content">
    <section>
        <div class="section-header">
            <h2>Pages</h2>
            <a class="btn-primary" href="/admin/page_form.php?type=page">Créer une page</a>
        </div>
        <?php if (!$pages): ?>
            <p>Aucune page pour le moment.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Titre</th>
                    <th>Slug</th>
                    <th>Menu</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><?= htmlspecialchars($page['title']) ?></td>
                        <td><?= htmlspecialchars($page['slug']) ?></td>
                        <td><?= (int)$page['show_in_menu'] === 1 ? 'Oui' : 'Non' ?></td>
                        <td><?= (int)$page['is_published'] === 1 ? 'Publié' : 'Brouillon' ?></td>
                        <td>
                            <a class="btn-secondary" href="/page.php?slug=<?= htmlspecialchars($page['slug']) ?>" target="_blank">Voir</a>
                            <a class="btn-secondary" href="/admin/page_form.php?id=<?= $page['id'] ?>&type=page">Modifier</a>
                            <form action="/admin/page_remove.php" method="post" class="inline-form" onsubmit="return confirm('Supprimer cette page ?');">
                                <input type="hidden" name="id" value="<?= $page['id'] ?>">
                                <button type="submit" class="btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <section>
        <div class="section-header">
            <h2>Articles du blog</h2>
            <a class="btn-primary" href="/admin/page_form.php?type=post">Nouvel article</a>
        </div>
        <?php if (!$posts): ?>
            <p>Aucun article publié.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Titre</th>
                    <th>Créé le</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?= htmlspecialchars($post['title']) ?></td>
                        <td><?= date('d/m/Y', strtotime($post['created_at'])) ?></td>
                        <td><?= (int)$post['is_published'] === 1 ? 'Publié' : 'Brouillon' ?></td>
                        <td>
                            <a class="btn-secondary" href="/page.php?slug=<?= htmlspecialchars($post['slug']) ?>" target="_blank">Voir</a>
                            <a class="btn-secondary" href="/admin/page_form.php?id=<?= $post['id'] ?>&type=post">Modifier</a>
                            <form action="/admin/page_remove.php" method="post" class="inline-form" onsubmit="return confirm('Supprimer cet article ?');">
                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                <button type="submit" class="btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
