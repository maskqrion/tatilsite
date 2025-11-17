<?php
// Geliştirme aşamasında hataları görmek için
ini_set('display_errors', 1);
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
    $response = ['success' => true];
    $rota_data = [];

    // 1. Rota ana verilerini çek (bind_result Düzeltmesi)
    $sql_rota = "SELECT 
        id, ad, aciklama, resim, bolge, fiyat, aktiviteler, 
        baslik, alt_baslik, tanitim, koordinatlar, etiketler, video_url 
        FROM rotalar WHERE id = ?";
        
    $stmt_rota = $conn->prepare($sql_rota);
    $stmt_rota->bind_param("s", $rota_id);
    $stmt_rota->execute();
    
    $stmt_rota->bind_result(
        $id, $ad, $aciklama, $resim, $bolge, $fiyat, $aktiviteler, 
        $baslik, $alt_baslik, $tanitim, $koordinatlar, $etiketler, $video_url
    );

    if ($stmt_rota->fetch()) {
        $rota_data = [
            'id' => $id, 'ad' => $ad, 'aciklama' => $aciklama, 'resim' => $resim,
            'bolge' => $bolge, 'fiyat' => $fiyat, 'aktiviteler' => $aktiviteler,
            'baslik' => $baslik, 'alt_baslik' => $alt_baslik, 'tanitim' => $tanitim,
            'koordinatlar' => $koordinatlar, 'etiketler' => $etiketler, 'video_url' => $video_url
        ];
    } else {
        $stmt_rota->close(); // Hata vermeden önce kapat
        hata_mesaji('Rota bulunamadı.');
    }
    $stmt_rota->close();
    
    // 2. Puan verilerini çek (bind_result Düzeltmesi)
    $stmt_puan = $conn->prepare("SELECT AVG(puan) as avg_puan, COUNT(id) as yorum_sayisi FROM yorumlar WHERE rota_id = ? AND parent_id IS NULL AND puan > 0");
    $stmt_puan->bind_param("s", $rota_id);
    $stmt_puan->execute();
    $stmt_puan->bind_result($avg_puan, $yorum_sayisi);
    $stmt_puan->fetch();
    $rota_data['avg_puan'] = $avg_puan ? round($avg_puan, 1) : 0;
    $rota_data['yorum_sayisi'] = (int) $yorum_sayisi;
    $stmt_puan->close();

    $response['details'] = $rota_data;

    // 3. İpuçlarını Çek (bind_result Düzeltmesi)
    $ipuclari = [];
    $stmt_ipuclari = $conn->prepare("SELECT id, baslik, metin FROM ipuclari WHERE rota_id = ?");
    $stmt_ipuclari->bind_param("s", $rota_id);
    $stmt_ipuclari->execute();
    $stmt_ipuclari->bind_result($ipucu_id, $ipucu_baslik, $ipucu_metin);
    while ($stmt_ipuclari->fetch()) {
        $ipuclari[] = ['id' => $ipucu_id, 'baslik' => $ipucu_baslik, 'metin' => $ipucu_metin];
    }
    $stmt_ipuclari->close();
    $response['details']['ipuclari'] = $ipuclari;

    // 4. Galeriyi Çek (bind_result Düzeltmesi)
    $galeri = [];
    $stmt_galeri = $conn->prepare("SELECT resim_url FROM galeri WHERE rota_id = ?");
    $stmt_galeri->bind_param("s", $rota_id);
    $stmt_galeri->execute();
    $stmt_galeri->bind_result($galeri_resim_url);
    while ($stmt_galeri->fetch()) {
        $galeri[] = $galeri_resim_url;
    }
    $stmt_galeri->close();
    $response['details']['galeri'] = $galeri;

    // 5. Mekanları Çek (Oteller ve Restoranlar) (bind_result Düzeltmesi)
    $mekanlar = [];
    $stmt_mekanlar = $conn->prepare("SELECT id, tip, ad, aciklama, kategori, koordinatlar, booking_url, onaylandi, owner_id, ozel_teklif_baslik, ozel_teklif_aciklama FROM mekanlar WHERE rota_id = ?");
    $stmt_mekanlar->bind_param("s", $rota_id);
    $stmt_mekanlar->execute();
    $stmt_mekanlar->bind_result(
        $m_id, $m_tip, $m_ad, $m_aciklama, $m_kategori, $m_koordinatlar, 
        $m_booking_url, $m_onaylandi, $m_owner_id, $m_ozel_teklif_baslik, $m_ozel_teklif_aciklama
    );
    while ($stmt_mekanlar->fetch()) {
        $mekanlar[] = [
            'id' => $m_id, 'tip' => $m_tip, 'ad' => $m_ad, 'aciklama' => $m_aciklama,
            'kategori' => $m_kategori, 'koordinatlar' => $m_koordinatlar,
            'booking_url' => $m_booking_url, 'onaylandi' => $m_onaylandi, 'owner_id' => $m_owner_id,
            'ozel_teklif_baslik' => $m_ozel_teklif_baslik, 'ozel_teklif_aciklama' => $m_ozel_teklif_aciklama
        ];
    }
    $stmt_mekanlar->close();

    $response['details']['neredeKalinir'] = array_values(array_filter($mekanlar, function($m) {
        return isset($m['tip']) && $m['tip'] === 'otel';
    }));
    $response['details']['neYenir'] = array_values(array_filter($mekanlar, function($m) {
        return isset($m['tip']) && $m['tip'] === 'restoran';
    }));

    echo json_encode($response);

} catch (Exception $e) {
    hata_mesaji('Veri getirilirken sunucu hatası oluştu: ' . $e->getMessage());
}

$conn->close();
?>