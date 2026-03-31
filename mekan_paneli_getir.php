<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu sayfayı görmek için giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['id'];

// Kullanıcının rolünü al
$stmt_role = $pdo->prepare("SELECT rol FROM users WHERE id = ?");
$stmt_role->execute([$user_id]);
$row = $stmt_role->fetch();
$user_role = $row['rol'] ?? 'user';

if (!in_array($user_role, ['mekan_sahibi', 'premium_mekan', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu sayfaya erişim yetkiniz yok.']);
    exit;
}

// Kullanıcıya ait mekanı veritabanından çek
$stmt = $pdo->prepare("SELECT id, ad, tip, kategori, aciklama, ozel_teklif_baslik, ozel_teklif_aciklama FROM mekanlar WHERE owner_id = ?");
$stmt->execute([$user_id]);
$mekan = $stmt->fetch();

if (!$mekan) {
    echo json_encode(['success' => false, 'message' => 'Yönetilecek bir mekanınız bulunmuyor.']);
    exit;
}

// Mekana ait galeri resimlerini çek
$stmt_gallery = $pdo->prepare("SELECT id, resim_url FROM mekan_galerisi WHERE mekan_id = ?");
$stmt_gallery->execute([$mekan['id']]);
$mekan['galeri'] = $stmt_gallery->fetchAll();

echo json_encode(['success' => true, 'data' => $mekan, 'user_role' => $user_role]);
?>
