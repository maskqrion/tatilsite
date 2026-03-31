<?php
require 'db_config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT id, baslik, kategori, tarih, resim, ozet FROM blog_yazilari ORDER BY tarih DESC");
    $stmt->execute();
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    error_log('blog_yazilari_getir hatası: ' . $e->getMessage());
    echo json_encode([]);
}
?>
