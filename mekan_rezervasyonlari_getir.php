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
    // bind_result Düzeltmesi
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.ad_soyad,
            r.email,
            r.kisi_sayisi,
            r.durum,
            r.talep_tarihi,
            e.etkinlik_adi
        FROM rezervasyonlar r
        JOIN etkinlikler e ON r.etkinlik_id = e.id
        JOIN mekanlar m ON e.mekan_id = m.id
        WHERE m.owner_id = ?
        ORDER BY r.talep_tarihi DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($id, $ad_soyad, $email, $kisi_sayisi, $durum, $talep_tarihi, $etkinlik_adi);
    
    $rezervasyonlar = [];
    while($stmt->fetch()) {
        $rezervasyonlar[] = [
            'id' => $id,
            'ad_soyad' => $ad_soyad,
            'email' => $email,
            'kisi_sayisi' => $kisi_sayisi,
            'durum' => $durum,
            'talep_tarihi' => $talep_tarihi,
            'etkinlik_adi' => $etkinlik_adi
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $rezervasyonlar]);
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'message' => 'Rezervasyonlar getirilirken bir sunucu hatası oluştu.']);
}

$conn->close();
?>