<?php
require 'db_config.php';
header('Content-Type: application/json');

$yazi_id = $_GET['id'] ?? '';
if (empty($yazi_id)) {
    echo json_encode(['success' => false, 'message' => 'Yazı kimliği belirtilmedi.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, baslik, tarih, kategori, ozet, icerik, resim, etiketler FROM blog_yazilari WHERE id = ?");
    $stmt->execute([$yazi_id]);
    $yazi = $stmt->fetch();

    if ($yazi) {
        echo json_encode(['success' => true, 'data' => $yazi]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Yazı bulunamadı.']);
    }
} catch (PDOException $e) {
    error_log('blog-detay_getir hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Yazı yüklenirken bir hata oluştu.']);
}
?>
