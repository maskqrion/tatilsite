<?php
error_reporting(E_ALL);

require 'db_config.php';
header('Content-Type: application/json');

function hata_mesaji($mesaj) {
    echo json_encode(['success' => false, 'message' => $mesaj]);
    exit;
}

$rota_id = $_GET['id'] ?? '';
if (empty($rota_id)) {
    hata_mesaji('Rota kimliği belirtilmedi.');
}

try {
    $pdo = getDB();
    $response = ['success' => true];

    // 1. Rota ana verilerini çek
    $stmt = $pdo->prepare("SELECT
        id, ad, aciklama, resim, bolge, fiyat, aktiviteler,
        baslik, alt_baslik, tanitim, koordinatlar, etiketler, video_url
        FROM rotalar WHERE id = ?");
    $stmt->execute([$rota_id]);
    $rota_data = $stmt->fetch();

    if ($rota_data === false) {
        hata_mesaji('Rota bulunamadı.');
    }

    // 2. Puan verilerini çek
    $stmt = $pdo->prepare("SELECT AVG(puan) as avg_puan, COUNT(id) as yorum_sayisi FROM yorumlar WHERE rota_id = ? AND parent_id IS NULL AND puan > 0");
    $stmt->execute([$rota_id]);
    $puan = $stmt->fetch();
    $rota_data['avg_puan'] = $puan['avg_puan'] !== null ? round((float)$puan['avg_puan'], 1) : 0;
    $rota_data['yorum_sayisi'] = (int)$puan['yorum_sayisi'];

    $response['details'] = $rota_data;

    // 3. İpuçlarını Çek
    $stmt = $pdo->prepare("SELECT id, baslik, metin FROM ipuclari WHERE rota_id = ?");
    $stmt->execute([$rota_id]);
    $response['details']['ipuclari'] = $stmt->fetchAll();

    // 4. Galeriyi Çek
    $stmt = $pdo->prepare("SELECT resim_url FROM galeri WHERE rota_id = ?");
    $stmt->execute([$rota_id]);
    $response['details']['galeri'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    // 5. Mekanları Çek (Oteller ve Restoranlar)
    $stmt = $pdo->prepare("SELECT id, tip, ad, aciklama, kategori, koordinatlar, booking_url, onaylandi, owner_id, ozel_teklif_baslik, ozel_teklif_aciklama FROM mekanlar WHERE rota_id = ?");
    $stmt->execute([$rota_id]);
    $mekanlar = $stmt->fetchAll();

    $response['details']['neredeKalinir'] = array_values(array_filter($mekanlar, function($m) {
        return isset($m['tip']) && $m['tip'] === 'otel';
    }));
    $response['details']['neYenir'] = array_values(array_filter($mekanlar, function($m) {
        return isset($m['tip']) && $m['tip'] === 'restoran';
    }));

    echo json_encode($response);

} catch (Exception $e) {
    error_log('rota_detay_getir hatası: ' . $e->getMessage());
    hata_mesaji('Veri getirilirken sunucu hatası oluştu.');
}
?>
