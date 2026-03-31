<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['id'];
$liste_adi = trim($_POST['liste_adi'] ?? '');
$aciklama = trim($_POST['liste_aciklama'] ?? '');
$herkese_acik = isset($_POST['herkese_acik']) ? 1 : 0;

if (empty($liste_adi)) {
    echo json_encode(['success' => false, 'message' => 'Liste başlığı boş bırakılamaz.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO kullanici_listeleri (user_id, liste_adi, aciklama, herkese_acik) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $liste_adi, $aciklama, $herkese_acik]);
    echo json_encode(['success' => true, 'message' => 'Listeniz başarıyla oluşturuldu!']);
} catch (PDOException $e) {
    error_log('liste_olustur hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}
?>
