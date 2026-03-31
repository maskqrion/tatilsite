<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

// GÜVENLİK GÜNCELLEMESİ: CSRF Token Kontrolü
// Formdan gelen token ile session'daki token'ı karşılaştır.
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı. Lütfen sayfayı yenileyip tekrar deneyin.']);
    exit;
}

// Kullanıcı giriş yapmamışsa yetki hatası ver
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Öneri göndermek için giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['id'];
$onerilen_rota = $_POST['onerilen_rota'] ?? '';
$bolge = $_POST['bolge'] ?? '';
$aciklama = $_POST['aciklama'] ?? '';

// Veri doğrulama
if (empty($onerilen_rota) || empty($bolge) || empty($aciklama)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
    exit;
}

// Yeni bir veritabanı tablosu oluşturmak için hazırlık: `rota_onerileri`
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO rota_onerileri (user_id, rota_adi, bolge, aciklama, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $onerilen_rota, $bolge, $aciklama]);

    echo json_encode(['success' => true, 'message' => 'Rota öneriniz başarıyla gönderildi.']);

} catch (Exception $e) {
    error_log('rota-oner-kaydet hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Öneri kaydedilirken bir hata oluştu.']);
}
?>
