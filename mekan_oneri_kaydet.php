<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

// 1. Güvenlik Kontrolleri
// CSRF Token Kontrolü
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

// Kullanıcı giriş yapmamışsa işlemi sonlandır
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Öneri yapmak için giriş yapmalısınız.']);
    exit;
}

// 2. Formdan gelen verileri al ve doğrula
$user_id = $_SESSION['id'];
$rota_id = $_POST['rota_id'] ?? '';
$oneri_tipi = $_POST['oneri_tipi'] ?? '';
$oneri_baslik = trim($_POST['oneri_baslik'] ?? '');
$oneri_aciklama = trim($_POST['oneri_aciklama'] ?? '');

if (empty($rota_id) || empty($oneri_tipi) || empty($oneri_baslik) || strlen($oneri_aciklama) < 20) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doğru şekilde doldurun. Açıklama en az 20 karakter olmalıdır.']);
    exit;
}

// 3. Veritabanına kaydet
// Bu işlem için yeni bir 'icerik_onerileri' tablosu oluşturulmalıdır.
// Sütunlar: id, user_id, rota_id, oneri_tipi ('mekan' veya 'ipucu'), baslik, aciklama, durum ('pending', 'approved', 'rejected')
try {
    $stmt = $conn->prepare("INSERT INTO icerik_onerileri (user_id, rota_id, oneri_tipi, baslik, aciklama, durum) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("issss", $user_id, $rota_id, $oneri_tipi, $oneri_baslik, $oneri_aciklama);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Öneriniz başarıyla alındı.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Öneri kaydedilirken bir veritabanı hatası oluştu.']);
    }
    $stmt->close();
} catch (Exception $e) {
    // Genellikle 'icerik_onerileri' tablosu yoksa bu hatayı alırız.
    error_log($e->getMessage()); // Hataları loglamak iyi bir pratiktir.
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}

$conn->close();
?>