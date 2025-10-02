<?php
require __DIR__ . '/../../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(['error' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_FILES['image'])) {
    respondJson(['error' => 'Aucune image reçue']);
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    respondJson(['error' => 'Erreur lors du téléchargement']);
    exit;
}

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
$mime = mime_content_type($file['tmp_name']);
if (!isset($allowed[$mime])) {
    respondJson(['error' => 'Format d\'image non supporté']);
    exit;
}

$extension = $allowed[$mime];
$uploadsDir = __DIR__ . '/../uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

$filename = bin2hex(random_bytes(8)) . '.' . $extension;
$destination = $uploadsDir . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    respondJson(['error' => 'Impossible d\'enregistrer le fichier']);
    exit;
}

$url = '/uploads/' . $filename;
respondJson(['url' => $url]);
