<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

// CSRF kontrolü
if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$mekan_id = (int)($_POST['mekan_id'] ?? 0);
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

// Güvenlik: Kullanıcının bu mekanı düzenleme yetkisi var mı?
$stmt_check = $pdo->prepare("SELECT owner_id FROM mekanlar WHERE id = ?");
$stmt_check->execute([$mekan_id]);
$row = $stmt_check->fetch();

if (!$row || (int)$row['owner_id'] !== (int)$user_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu mekanı düzenleme yetkiniz yok.']);
    exit;
}

try {
    $stmt_update = $pdo->prepare("UPDATE mekanlar SET ad = ?, kategori = ?, aciklama = ?, ozel_teklif_baslik = ?, ozel_teklif_aciklama = ? WHERE id = ?");
    $stmt_update->execute([$ad, $kategori, $aciklama, $ozel_teklif_baslik, $ozel_teklif_aciklama, $mekan_id]);
    echo json_encode(['success' => true, 'message' => 'Mekan bilgileri başarıyla güncellendi.']);
} catch (PDOException $e) {
    error_log('mekan_guncelle hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Güncelleme sırasında bir hata oluştu.']);
}
?>
