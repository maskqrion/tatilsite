<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

// CSRF kontrolü
if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Öneri yapmak için giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['id'];
$rota_id = $_POST['rota_id'] ?? '';
$oneri_tipi = $_POST['oneri_tipi'] ?? '';
$oneri_baslik = trim($_POST['oneri_baslik'] ?? '');
$oneri_aciklama = trim($_POST['oneri_aciklama'] ?? '');

if (empty($rota_id) || empty($oneri_tipi) || empty($oneri_baslik) || strlen($oneri_aciklama) < 20) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doğru şekilde doldurun. Açıklama en az 20 karakter olmalıdır.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO icerik_onerileri (user_id, rota_id, oneri_tipi, baslik, aciklama, durum) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $rota_id, $oneri_tipi, $oneri_baslik, $oneri_aciklama]);
    echo json_encode(['success' => true, 'message' => 'Öneriniz başarıyla alındı.']);
} catch (PDOException $e) {
    error_log('mekan_oneri_kaydet hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}
?>
