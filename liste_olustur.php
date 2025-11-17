<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

// Kullanıcı giriş yapmamışsa işlemi sonlandır
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

// === YENİ EKLENDİ: CSRF KONTROLÜ ===
if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}
// === GÜNCELLEME SONU ===

$user_id = $_SESSION['id'];
$liste_adi = trim($_POST['liste_adi'] ?? '');
$aciklama = trim($_POST['liste_aciklama'] ?? '');
$herkese_acik = isset($_POST['herkese_acik']) ? 1 : 0;

if (empty($liste_adi)) {
    echo json_encode(['success' => false, 'message' => 'Liste başlığı boş bırakılamaz.']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO kullanici_listeleri (user_id, liste_adi, aciklama, herkese_acik) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_id, $liste_adi, $aciklama, $herkese_acik);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Listeniz başarıyla oluşturuldu!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Liste oluşturulurken bir veritabanı hatası oluştu.']);
    }
    $stmt->close();
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}

$conn->close();
?>