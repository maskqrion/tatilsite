<?php
require 'db_config.php';
header('Content-Type: application/json');

$yazi_id = $_GET['id'] ?? '';
if (empty($yazi_id)) { 
    echo json_encode(['success' => false, 'message' => 'Yazı kimliği belirtilmedi.']);
    exit; 
}

$stmt = $conn->prepare("SELECT id, ad, aciklama, resim, bolge, fiyat, aktiviteler, baslik, alt_baslik, tanitim, koordinatlar, etiketler, video_url, one_cikan, tarih, kategori, ozet, icerik FROM blog_yazilari WHERE id = ?");
$stmt->bind_param("s", $yazi_id);
$stmt->execute();

// Tüm sütunları bind_result'a ekleyin
$stmt->bind_result($id, $ad, $aciklama, $resim, $bolge, $fiyat, $aktiviteler, $baslik, $alt_baslik, $tanitim, $koordinatlar, $etiketler, $video_url, $one_cikan, $tarih, $kategori, $ozet, $icerik);

$yazi = null;
if ($stmt->fetch()) {
    $yazi = [
        'id' => $id, 'ad' => $ad, 'aciklama' => $aciklama, 'resim' => $resim,
        'bolge' => $bolge, 'fiyat' => $fiyat, 'aktiviteler' => $aktiviteler,
        'baslik' => $baslik, 'alt_baslik' => $alt_baslik, 'tanitim' => $tanitim,
        'koordinatlar' => $koordinatlar, 'etiketler' => $etiketler, 'video_url' => $video_url,
        'one_cikan' => $one_cikan, 'tarih' => $tarih, 'kategori' => $kategori,
        'ozet' => $ozet, 'icerik' => $icerik
    ];
}
$stmt->close();

if ($yazi) {
    echo json_encode(['success' => true, 'data' => $yazi]);
} else {
    echo json_encode(['success' => false, 'message' => 'Yazı bulunamadı.']);
}

$conn->close();
?>