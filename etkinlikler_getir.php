<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->prepare("
        SELECT
            e.id, e.rota_id, e.etkinlik_adi, e.aciklama, e.baslangic_tarihi, e.bitis_tarihi,
            e.konum, e.resim_url, e.rezervasyon_aktif, r.ad as rota_adi
        FROM etkinlikler e
        JOIN rotalar r ON e.rota_id = r.id
        WHERE e.bitis_tarihi >= CURDATE()
        ORDER BY e.baslangic_tarihi ASC
    ");
    $stmt->execute();
    $etkinlikler = $stmt->fetchAll();

    foreach ($etkinlikler as &$row) {
        $row['baslangic_tarihi_formatli'] = date("d F Y", strtotime($row['baslangic_tarihi']));
        $row['bitis_tarihi_formatli'] = date("d F Y", strtotime($row['bitis_tarihi']));
    }
    unset($row);

    echo json_encode($etkinlikler);
} catch (PDOException $e) {
    error_log('etkinlikler_getir hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Etkinlikler yüklenirken bir hata oluştu.']);
}
?>
