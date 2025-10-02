<?php
require __DIR__ . '/../bootstrap.php';
$menuPages = findMenuPages();
$posts = findPages(true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carburants Malins</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-sA+e2atE9QyX36F0gxFqNXWx1Z45Z5QfO5x3/qt0PCc=" crossorigin="" />
    <link rel="stylesheet" href="/css/styles.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-vA0bM6sknKwM16DyXZT7yyWJY1AbczLFp3GtM/2y+7E=" crossorigin=""></script>
</head>
<body>
<header class="site-header">
    <div class="branding">
        <h1>Carburants Malins</h1>
        <p>Trouvez le carburant le moins cher près de vous.</p>
    </div>
    <nav class="main-nav">
        <a href="/">Accueil</a>
        <?php foreach ($menuPages as $page): ?>
            <a href="/page.php?slug=<?= htmlspecialchars($page['slug']) ?>"><?= htmlspecialchars($page['menu_label'] ?: $page['title']) ?></a>
        <?php endforeach; ?>
        <?php if ($posts): ?>
            <a href="/blog.php">Blog</a>
        <?php endif; ?>
        <a href="/admin/index.php" class="admin-link">Administration</a>
    </nav>
</header>

<main class="layout">
    <section class="search-panel">
        <h2>Stations à proximité</h2>
        <form id="search-form">
            <div class="form-group">
                <label for="radius">Rayon (km)</label>
                <input type="range" id="radius" name="radius" min="1" max="100" value="10">
                <span id="radius-value">10 km</span>
            </div>
            <div class="form-group">
                <label for="fuel">Type de carburant</label>
                <select id="fuel" name="fuel">
                    <option value="">Tous</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Trouver les stations</button>
        </form>
        <div class="cheapest-fuels" id="cheapest-fuels"></div>
    </section>
    <section class="map-panel">
        <div id="map"></div>
        <div class="station-results">
            <h3>Stations trouvées</h3>
            <div id="station-list"></div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> Carburants Malins - Données officielles data.gouv.fr</p>
</footer>

<script src="/js/app.js"></script>
</body>
</html>
