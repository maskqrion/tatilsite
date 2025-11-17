<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

// 1. Kullanıcı giriş yapmış mı ve rolü uygun mu diye kontrol et
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu sayfayı görmek için giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['id'];

// Kullanıcının rolünü al (Bu dosya zaten bind_result kullanıyordu, GÜVENLİ)
$stmt_role = $conn->prepare("SELECT rol FROM users WHERE id = ?");
$stmt_role->bind_param("i", $user_id);
$stmt_role->execute();
$stmt_role->bind_result($rol);
$stmt_role->fetch();
$stmt_role->close();
$user_role = $rol ?? 'user';

if ($user_role !== 'mekan_sahibi' && $user_role !== 'premium_mekan' && $user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu sayfaya erişim yetkiniz yok.']);
    exit;
}

// 2. Kullanıcıya ait mekanı veritabanından çek (bind_result Düzeltmesi)
$stmt = $conn->prepare("SELECT id, ad, tip, kategori, aciklama, ozel_teklif_baslik, ozel_teklif_aciklama FROM mekanlar WHERE owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($id, $ad, $tip, $kategori, $aciklama, $ozel_teklif_baslik, $ozel_teklif_aciklama);
$mekan = null;
if ($stmt->fetch()) {
    $mekan = [
        'id' => $id,
        'ad' => $ad,
        'tip' => $tip,
        'kategori' => $kategori,
        'aciklama' => $aciklama,
        'ozel_teklif_baslik' => $ozel_teklif_baslik,
        'ozel_teklif_aciklama' => $ozel_teklif_aciklama
    ];
}
$stmt->close();

if (!$mekan) {
     echo json_encode(['success' => false, 'message' => 'Yönetilecek bir mekanınız bulunmuyor.']);
     $conn->close();
     exit;
}

// 3. YENİ: Mekana ait galeri resimlerini çek (bind_result Düzeltmesi)
$stmt_gallery = $conn->prepare("SELECT id, resim_url FROM mekan_galerisi WHERE mekan_id = ?");
$stmt_gallery->bind_param("i", $mekan['id']);
$stmt_gallery->execute();
$stmt_gallery->bind_result($galeri_id, $resim_url);
$galeri = [];
while($stmt_gallery->fetch()) {
    $galeri[] = [
        'id' => $galeri_id,
        'resim_url' => $resim_url
    ];
}
$mekan['galeri'] = $galeri;
$stmt_gallery->close();

// 4. Tüm verileri ve kullanıcının rolünü birlikte gönder
echo json_encode(['success' => true, 'data' => $mekan, 'user_role' => $user_role]);

$conn->close();
?>