<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$rota_id = $_GET['rota_id'] ?? '';

if (empty($rota_id)) {
    echo json_encode(['success' => false, 'message' => 'Rota kimliği belirtilmedi.']);
    exit;
}

try {
    // bind_result Düzeltmesi
    $stmt = $conn->prepare("
        SELECT 
            kf.resim_url,
            u.name as yukleyen_kullanici
        FROM kullanici_fotograflari kf
        JOIN users u ON kf.user_id = u.id
        WHERE kf.rota_id = ? AND kf.durum = 'onaylandi'
        ORDER BY kf.yuklenme_tarihi DESC
    ");
    $stmt->bind_param("s", $rota_id);
    $stmt->execute();
    $stmt->bind_result($resim_url, $yukleyen_kullanici);
    
    $fotograflar = [];
    while($stmt->fetch()) {
        $fotograflar[] = [
            'resim_url' => $resim_url,
            'yukleyen_kullanici' => $yukleyen_kullanici
        ];
    }

    echo json_encode(['success' => true, 'data' => $fotograflar]);
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fotoğraflar getirilirken bir hata oluştu.']);
}

$conn->close();
?>