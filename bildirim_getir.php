<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapılmadı.']);
    exit;
}

$user_id = $_SESSION['id'];

try {
    $stmt = $pdo->prepare("
        SELECT
            b.id, b.bildirim_tipi, b.hedef_id, b.okundu_mu, b.olusturulma_tarihi,
            u.name AS tetikleyici_kullanici_adi,
            r.ad AS rota_adi,
            COALESCE(y.yorum_metni, '') AS yorum_metni
        FROM bildirimler b
        JOIN users u ON b.tetikleyici_user_id = u.id
        LEFT JOIN yorumlar y ON b.hedef_id = y.id AND (b.bildirim_tipi = 'yorum_begeni' OR b.bildirim_tipi = 'bahsedilme')
        LEFT JOIN rotalar r ON y.rota_id = r.id
        WHERE b.user_id = ?
        ORDER BY b.okundu_mu ASC, b.olusturulma_tarihi DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'bildirimler' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log('bildirim_getir hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bildirimler getirilirken bir hata oluştu.']);
}
?>
