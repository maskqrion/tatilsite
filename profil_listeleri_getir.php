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
$response = ['success' => true, 'listeler' => []];

try {
    // bind_result Düzeltmesi
    $stmt = $conn->prepare("SELECT id, liste_adi, aciklama, olusturulma_tarihi FROM kullanici_listeleri WHERE user_id = ? ORDER BY olusturulma_tarihi DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($id, $liste_adi, $aciklama, $olusturulma_tarihi);
    
    $listeler = [];
    while ($stmt->fetch()) {
        $listeler[] = [
            'id' => $id,
            'liste_adi' => $liste_adi,
            'aciklama' => $aciklama,
            'olusturulma_tarihi' => $olusturulma_tarihi
        ];
    }
    $stmt->close();
    $response['listeler'] = $listeler;

} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Listeler getirilirken bir hata oluştu.'];
}

echo json_encode($response);
$conn->close();
?>