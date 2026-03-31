<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu verileri görmek için giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['id'];

try {
    $stmt = $pdo->prepare("
        SELECT
            r.id, r.ad_soyad, r.email, r.kisi_sayisi, r.durum, r.talep_tarihi,
            e.etkinlik_adi
        FROM rezervasyonlar r
        JOIN etkinlikler e ON r.etkinlik_id = e.id
        JOIN mekanlar m ON e.mekan_id = m.id
        WHERE m.owner_id = ?
        ORDER BY r.talep_tarihi DESC
    ");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log('mekan_rezervasyonlari_getir hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Rezervasyonlar getirilirken bir hata oluştu.']);
}
?>
