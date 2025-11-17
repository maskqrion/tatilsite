<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

// 1. Kullanıcı giriş yapmış mı?
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

// 2. CSRF Koruması
if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['id'];

try {
    // 3. Kullanıcıya ait okunmamış (okundu_mu = 0) tüm bildirimleri okundu (okundu_mu = 1) olarak güncelle
    $stmt = $conn->prepare("UPDATE bildirimler SET okundu_mu = 1 WHERE user_id = ? AND okundu_mu = 0");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Etkilenen satır sayısını kontrol etmeye gerek yok, 0 olması da bir başarıdır.
        echo json_encode(['success' => true, 'message' => 'Bildirimler okundu olarak işaretlendi.']);
    } else {
        throw new Exception('Bildirimler güncellenemedi.');
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}

$conn->close();
?>