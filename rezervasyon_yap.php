<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Rezervasyon yapmak için giriş yapmalısınız.']);
    exit;
}

if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['id'];
$etkinlik_id = (int)($_POST['etkinlik_id'] ?? 0);
$ad_soyad = trim($_POST['ad_soyad'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$kisi_sayisi = filter_var($_POST['kisi_sayisi'] ?? 0, FILTER_VALIDATE_INT);

if (empty($etkinlik_id) || empty($ad_soyad) || !$email || $kisi_sayisi <= 0) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doğru bir şekilde doldurun.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO rezervasyonlar (etkinlik_id, user_id, ad_soyad, email, kisi_sayisi, durum) VALUES (?, ?, ?, ?, ?, 'beklemede')");
    $stmt->execute([$etkinlik_id, $user_id, $ad_soyad, $email, $kisi_sayisi]);
    echo json_encode(['success' => true, 'message' => 'Rezervasyon talebiniz başarıyla alınmıştır.']);
} catch (PDOException $e) {
    error_log('rezervasyon_yap hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}
?>
