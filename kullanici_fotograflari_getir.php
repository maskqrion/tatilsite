<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$rota_id = $_GET['rota_id'] ?? '';

if (empty($rota_id)) {
    echo json_encode(['success' => false, 'message' => 'Rota kimliği belirtilmedi.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT kf.resim_url, u.name as yukleyen_kullanici
        FROM kullanici_fotograflari kf
        JOIN users u ON kf.user_id = u.id
        WHERE kf.rota_id = ? AND kf.durum = 'onaylandi'
        ORDER BY kf.yuklenme_tarihi DESC
    ");
    $stmt->execute([$rota_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log('kullanici_fotograflari_getir hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fotoğraflar getirilirken bir hata oluştu.']);
}
?>
