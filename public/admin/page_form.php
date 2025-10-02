<?php
require __DIR__ . '/../../bootstrap.php';

$type = $_GET['type'] ?? 'page';
$isBlog = $type === 'post';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isBlog = ($_POST['type'] ?? 'page') === 'post';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $menuLabel = trim($_POST['menu_label'] ?? '');
    $menuOrder = (int) ($_POST['menu_order'] ?? 0);
    $showInMenu = isset($_POST['show_in_menu']) ? 1 : 0;
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '' || $content === '') {
        $error = 'Le titre et le contenu sont obligatoires.';
    } else {
        if ($slug === '') {
            $slug = slugify($title);
        } else {
            $slug = slugify($slug);
        }

        $slugBase = $slug;
        $suffix = 1;
        while (true) {
            $params = ['slug' => $slug];
            $sql = 'SELECT id FROM pages WHERE slug = :slug';
            if ($id) {
                $sql .= ' AND id != :id';
                $params['id'] = $id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $existing = $stmt->fetchColumn();
            if (!$existing) {
                break;
            }
            $slug = $slugBase . '-' . $suffix++;
        }

        $now = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
        if ($id) {
            $stmt = $pdo->prepare('UPDATE pages SET title = :title, slug = :slug, content = :content, is_blog = :is_blog, is_published = :is_published, menu_label = :menu_label, menu_order = :menu_order, show_in_menu = :show_in_menu, updated_at = :updated_at WHERE id = :id');
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'is_blog' => $isBlog ? 1 : 0,
                'is_published' => $isPublished,
                'menu_label' => $menuLabel,
                'menu_order' => $menuOrder,
                'show_in_menu' => $showInMenu,
                'updated_at' => $now,
                'id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO pages (title, slug, content, is_blog, is_published, menu_label, menu_order, show_in_menu, created_at, updated_at) VALUES (:title, :slug, :content, :is_blog, :is_published, :menu_label, :menu_order, :show_in_menu, :created_at, :updated_at)');
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'is_blog' => $isBlog ? 1 : 0,
                'is_published' => $isPublished,
                'menu_label' => $menuLabel,
                'menu_order' => $menuOrder,
                'show_in_menu' => $showInMenu,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        header('Location: /admin/index.php');
        exit;
    }
}

$default = [
    'id' => null,
    'title' => '',
    'slug' => '',
    'content' => '<p></p>',
    'menu_label' => '',
    'menu_order' => 0,
    'show_in_menu' => 0,
    'is_published' => 1,
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($page) {
        $default = $page;
        $isBlog = (int) $page['is_blog'] === 1;
        $type = $isBlog ? 'post' : 'page';
    }
}

$titleLabel = $isBlog ? 'Article de blog' : 'Page';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Modifier' : 'Créer' ?> <?= htmlspecialchars($titleLabel) ?> - Administration</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
</head>
<body class="admin">
<header class="site-header">
    <div class="branding">
        <h1><?= $id ? 'Modifier' : 'Créer' ?> <?= htmlspecialchars($titleLabel) ?></h1>
    </div>
    <nav class="main-nav">
        <a href="/admin/index.php">&larr; Retour</a>
    </nav>
</header>
<main class="admin-content">
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" class="admin-form">
        <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($default['id'] ?? '')) ?>">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        <div class="form-group">
            <label for="title">Titre</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($default['title']) ?>" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug (optionnel)</label>
            <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($default['slug']) ?>" placeholder="ex: mentions-legales">
        </div>
        <div class="form-group">
            <label>Contenu</label>
            <div id="editor" class="wysiwyg"></div>
            <input type="hidden" name="content" id="content" value="<?= htmlspecialchars($default['content']) ?>">
        </div>
        <?php if (!$isBlog): ?>
            <div class="grid">
                <div class="form-group">
                    <label for="menu_label">Libellé du menu</label>
                    <input type="text" id="menu_label" name="menu_label" value="<?= htmlspecialchars($default['menu_label']) ?>" placeholder="Texte du menu">
                </div>
                <div class="form-group">
                    <label for="menu_order">Ordre du menu</label>
                    <input type="number" id="menu_order" name="menu_order" value="<?= (int) $default['menu_order'] ?>">
                </div>
            </div>
            <div class="form-group checkbox">
                <label>
                    <input type="checkbox" name="show_in_menu" <?= (int) $default['show_in_menu'] === 1 ? 'checked' : '' ?>> Afficher dans le menu
                </label>
            </div>
        <?php endif; ?>
        <div class="form-group checkbox">
            <label>
                <input type="checkbox" name="is_published" <?= (int) $default['is_published'] === 1 ? 'checked' : '' ?>> Publier
            </label>
        </div>
        <button type="submit" class="btn-primary">Enregistrer</button>
    </form>
</main>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const editor = new Quill('#editor', {
    theme: 'snow',
    modules: {
        toolbar: {
            container: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'image'],
                ['clean']
            ],
            handlers: {
                image: function () {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.addEventListener('change', async () => {
                        const file = input.files[0];
                        if (!file) return;
                        const formData = new FormData();
                        formData.append('image', file);
                        const response = await fetch('/admin/upload_image.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        if (data && data.url) {
                            const range = editor.getSelection();
                            editor.insertEmbed(range ? range.index : 0, 'image', data.url);
                        } else {
                            alert(data.error || 'Erreur lors de l\'upload de l\'image');
                        }
                    });
                    input.click();
                }
            }
        }
    }
});
const contentInput = document.getElementById('content');
const initialContent = contentInput.value;
if (initialContent) {
    editor.root.innerHTML = initialContent;
}
document.querySelector('.admin-form').addEventListener('submit', () => {
    contentInput.value = editor.root.innerHTML;
});
</script>
</body>
</html>
