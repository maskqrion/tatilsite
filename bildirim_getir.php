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
    // YENİ: Sorguya yeni bildirim tipi için LEFT JOIN ve ilgili alanlar eklendi.
    $stmt = $conn->prepare("
        SELECT 
            b.id,
            b.bildirim_tipi,
            b.hedef_id,
            b.okundu_mu,
            b.olusturulma_tarihi,
            u.name AS tetikleyici_kullanici_adi,
            r.ad AS rota_adi,
            COALESCE(y.yorum_metni, '') AS yorum_metni -- Bahsedilme ve beğeni için yorum metnini al
        FROM bildirimler b
        JOIN users u ON b.tetikleyici_user_id = u.id
        LEFT JOIN yorumlar y ON b.hedef_id = y.id AND (b.bildirim_tipi = 'yorum_begeni' OR b.bildirim_tipi = 'bahsedilme')
        LEFT JOIN rotalar r ON y.rota_id = r.id
        WHERE b.user_id = ?
        ORDER BY b.okundu_mu ASC, b.olusturulma_tarihi DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $bildirimler = [];
    while ($row = $result->fetch_assoc()) {
        $bildirimler[] = $row;
    }
    
    echo json_encode(['success' => true, 'bildirimler' => $bildirimler]);
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bildirimler getirilirken bir hata oluştu.']);
}

$conn->close();
?>