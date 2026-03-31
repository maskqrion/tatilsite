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
$pdo = getDB();
$response = ['success' => true, 'listeler' => []];

try {
    $stmt = $pdo->prepare("SELECT id, liste_adi, aciklama, olusturulma_tarihi FROM kullanici_listeleri WHERE user_id = ? ORDER BY olusturulma_tarihi DESC");
    $stmt->execute([$user_id]);
    $response['listeler'] = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('profil_listeleri_getir.php hatası: ' . $e->getMessage());
    $response = ['success' => false, 'message' => 'Listeler getirilirken bir hata oluştu.'];
}

echo json_encode($response);
?>
