<?php
/**
 * Theme helpers for the site-node frontend.
 */

function theme_header(string $pageTitle, array $menuItems = []): void
{
    $siteTitle = 'PBN Site';
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?> - <?= htmlspecialchars($siteTitle, ENT_QUOTES) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/"><?= htmlspecialchars($siteTitle, ENT_QUOTES) ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php foreach ($menuItems as $item): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($item['href'] ?? '#', ENT_QUOTES) ?>"><?= htmlspecialchars($item['label'] ?? 'Lien', ENT_QUOTES) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container py-4">
    <?php
}

function theme_footer(): void
{
    ?>
    </main>
    <footer class="bg-light py-4 mt-auto border-top">
        <div class="container text-center text-muted small">&copy; <?= date('Y') ?> PBN Site - Propulsé par le SaaS</div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
