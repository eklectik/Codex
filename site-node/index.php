<?php
$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/theme.php';

$pages = json_decode(@file_get_contents(__DIR__ . '/storage/pages.json') ?: '[]', true) ?: [];
$posts = json_decode(@file_get_contents(__DIR__ . '/storage/posts.json') ?: '[]', true) ?: [];
$menus = json_decode(@file_get_contents(__DIR__ . '/storage/menus.json') ?: '[]', true) ?: [];

$primaryMenu = [];
foreach ($menus as $menu) {
    if (($menu['location'] ?? '') === 'primary') {
        $primaryMenu = $menu['items'] ?? [];
    }
}

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($requestUri, '/');
if ($path === '//') {
    $path = '/';
}

function find_by_slug(array $collection, string $slug): ?array
{
    foreach ($collection as $item) {
        if (($item['slug'] ?? '') === $slug) {
            return $item;
        }
    }
    return null;
}

switch (true) {
    case $path === '/' || $path === '/index.php':
        $home = find_by_slug($pages, 'accueil');
        if ($home) {
            theme_header($home['title'] ?? 'Accueil', $primaryMenu);
            echo '<article class="mb-4">' . ($home['html'] ?? '') . '</article>';
        } else {
            theme_header('Blog', $primaryMenu);
            echo '<h1 class="mb-4">Articles récents</h1>';
            if (empty($posts)) {
                echo '<p>Aucun article pour le moment.</p>';
            }
            foreach ($posts as $post) {
                if (($post['status'] ?? 'published') !== 'published') {
                    continue;
                }
                $date = $post['published_at'] ?? $post['updated_at'] ?? '';
                echo '<article class="mb-4">';
                echo '<h2><a href="/post/' . htmlspecialchars($post['slug'], ENT_QUOTES) . '">' . htmlspecialchars($post['title'], ENT_QUOTES) . '</a></h2>';
                if ($date) {
                    echo '<div class="text-muted small mb-2">' . htmlspecialchars($date, ENT_QUOTES) . '</div>';
                }
                echo '<div>' . ($post['html'] ?? '') . '</div>';
                echo '</article>';
            }
        }
        theme_footer();
        break;

    case str_starts_with($path, '/page/'):
        $slug = substr($path, strlen('/page/'));
        $page = find_by_slug($pages, $slug);
        if (!$page) {
            http_response_code(404);
            theme_header('Page introuvable', $primaryMenu);
            echo '<h1>404</h1><p>Cette page n\'existe pas.</p>';
            theme_footer();
            break;
        }
        theme_header($page['title'] ?? $slug, $primaryMenu);
        echo '<article>' . ($page['html'] ?? '') . '</article>';
        theme_footer();
        break;

    case $path === '/blog':
        theme_header('Blog', $primaryMenu);
        echo '<h1 class="mb-4">Blog</h1>';
        foreach ($posts as $post) {
            if (($post['status'] ?? 'published') !== 'published') {
                continue;
            }
            echo '<article class="mb-4">';
            echo '<h2><a href="/post/' . htmlspecialchars($post['slug'], ENT_QUOTES) . '">' . htmlspecialchars($post['title'], ENT_QUOTES) . '</a></h2>';
            if (!empty($post['published_at'])) {
                echo '<div class="text-muted small mb-2">' . htmlspecialchars($post['published_at'], ENT_QUOTES) . '</div>';
            }
            echo '<div>' . ($post['html'] ?? '') . '</div>';
            echo '</article>';
        }
        theme_footer();
        break;

    case str_starts_with($path, '/post/'):
        $slug = substr($path, strlen('/post/'));
        $post = find_by_slug($posts, $slug);
        if (!$post) {
            http_response_code(404);
            theme_header('Article introuvable', $primaryMenu);
            echo '<h1>404</h1><p>Article non trouvé.</p>';
            theme_footer();
            break;
        }
        theme_header($post['title'] ?? $slug, $primaryMenu);
        if (!empty($post['published_at'])) {
            echo '<div class="text-muted mb-2">' . htmlspecialchars($post['published_at'], ENT_QUOTES) . '</div>';
        }
        echo '<article>' . ($post['html'] ?? '') . '</article>';
        if (!empty($post['tags'])) {
            echo '<p class="mt-3"><strong>Tags :</strong> ' . htmlspecialchars($post['tags'], ENT_QUOTES) . '</p>';
        }
        theme_footer();
        break;

    default:
        http_response_code(404);
        theme_header('404', $primaryMenu);
        echo '<h1>404</h1><p>Page introuvable.</p>';
        theme_footer();
}
