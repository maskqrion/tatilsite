<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

// 1. Kullanıcı giriş yapmış olmalı
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Rezervasyon yapmak için giriş yapmalısınız.']);
    exit;
}

// === YENİ EKLENDİ: CSRF KONTROLÜ ===
if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}
// === GÜNCELLEME SONU ===

// 2. Form verilerini al ve doğrula
$user_id = $_SESSION['id'];
$etkinlik_id = $_POST['etkinlik_id'] ?? 0;
$ad_soyad = trim($_POST['ad_soyad'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$kisi_sayisi = filter_var($_POST['kisi_sayisi'] ?? 0, FILTER_VALIDATE_INT);

if (empty($etkinlik_id) || empty($ad_soyad) || !$email || $kisi_sayisi <= 0) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doğru bir şekilde doldurun.']);
    exit;
}

// 3. Veritabanına kaydet
try {
    $stmt = $conn->prepare("INSERT INTO rezervasyonlar (etkinlik_id, user_id, ad_soyad, email, kisi_sayisi, durum) VALUES (?, ?, ?, ?, ?, 'beklemede')");
    $stmt->bind_param("isssi", $etkinlik_id, $user_id, $ad_soyad, $email, $kisi_sayisi);

    if ($stmt->execute()) {
        // BAŞARILI: E-posta bildirimleri burada eklenebilir (örneğin mekan sahibine)
        echo json_encode(['success' => true, 'message' => 'Rezervasyon talebiniz başarıyla alınmıştır. Mekan sahibi onayladığında size e-posta ile bilgi verilecektir.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rezervasyon kaydedilirken bir veritabanı hatası oluştu.']);
    }
    $stmt->close();
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}

$conn->close();
?>