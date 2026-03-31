<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->prepare("
        SELECT kl.id, kl.liste_adi, kl.aciklama, u.name as olusturan_kullanici
        FROM kullanici_listeleri kl
        JOIN users u ON kl.user_id = u.id
        WHERE kl.herkese_acik = 1
        ORDER BY kl.olusturulma_tarihi DESC
    ");
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log('public_lists_getir hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Listeler yüklenirken bir hata oluştu.']);
}
?>
