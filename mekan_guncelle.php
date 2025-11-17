<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

$mekan_id = $_POST['mekan_id'] ?? 0;
$ad = $_POST['ad'] ?? '';
$kategori = $_POST['kategori'] ?? '';
$aciklama = $_POST['aciklama'] ?? '';
$user_id = $_SESSION['id'];

$ozel_teklif_baslik = $_POST['ozel_teklif_baslik'] ?? null;
$ozel_teklif_aciklama = $_POST['ozel_teklif_aciklama'] ?? null;

if (empty($mekan_id) || empty($ad) || empty($kategori)) {
    echo json_encode(['success' => false, 'message' => 'Mekan adı ve kategori alanları boş bırakılamaz.']);
    exit;
}

// Güvenlik: Kullanıcının bu mekanı düzenleme yetkisi var mı? (bind_result Düzeltmesi)
$stmt_check = $conn->prepare("SELECT owner_id FROM mekanlar WHERE id = ?");
$stmt_check->bind_param("i", $mekan_id);
$stmt_check->execute();
$stmt_check->bind_result($owner_id);
$stmt_check->fetch();
$stmt_check->close();

if ($owner_id != $user_id) { // Admin kontrolü de eklenebilir
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu mekanı düzenleme yetkiniz yok.']);
    exit;
}

// Veritabanını Güncelle
$stmt_update = $conn->prepare("UPDATE mekanlar SET ad = ?, kategori = ?, aciklama = ?, ozel_teklif_baslik = ?, ozel_teklif_aciklama = ? WHERE id = ?");
$stmt_update->bind_param("sssssi", $ad, $kategori, $aciklama, $ozel_teklif_baslik, $ozel_teklif_aciklama, $mekan_id);

if ($stmt_update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Mekan bilgileri başarıyla güncellendi.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Güncelleme sırasında bir veritabanı hatası oluştu.']);
}

$stmt_update->close();
$conn->close();
?>