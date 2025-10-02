<?php
require __DIR__ . '/../../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = db()->prepare('DELETE FROM pages WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}

header('Location: /admin/index.php');
exit;
