<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

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

try {
    $stmt = $pdo->prepare("UPDATE bildirimler SET okundu_mu = 1 WHERE user_id = ? AND okundu_mu = 0");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'message' => 'Bildirimler okundu olarak işaretlendi.']);
} catch (PDOException $e) {
    error_log('bildirim_okundu_isaretle hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası oluştu.']);
}
?>
