<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    // bind_result Düzeltmesi
    $stmt = $conn->prepare("
        SELECT 
            kl.id, 
            kl.liste_adi, 
            kl.aciklama, 
            u.name as olusturan_kullanici
        FROM kullanici_listeleri kl
        JOIN users u ON kl.user_id = u.id
        WHERE kl.herkese_acik = 1
        ORDER BY kl.olusturulma_tarihi DESC
    ");
    
    $stmt->execute();
    $stmt->bind_result($id, $liste_adi, $aciklama, $olusturan_kullanici);
    $listeler = [];
    while ($stmt->fetch()) {
        $listeler[] = [
            'id' => $id,
            'liste_adi' => $liste_adi,
            'aciklama' => $aciklama,
            'olusturan_kullanici' => $olusturan_kullanici
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $listeler]);
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Listeler getirilirken bir sunucu hatası oluştu.']);
}

$conn->close();
?>