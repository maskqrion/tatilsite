<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

// SORGUDAN 'rezervasyon_aktif' ALANI EKLENDİ
$sql = "
    SELECT 
        e.id,
        e.rota_id,
        e.etkinlik_adi,
        e.aciklama,
        e.baslangic_tarihi,
        e.bitis_tarihi,
        e.konum,
        e.resim_url,
        e.rezervasyon_aktif,
        r.ad as rota_adi
    FROM etkinlikler e
    JOIN rotalar r ON e.rota_id = r.id
    WHERE e.bitis_tarihi >= CURDATE()
    ORDER BY e.baslangic_tarihi ASC
";

$result = $conn->query($sql);

if ($result === false) {
    http_response_code(500); 
    echo json_encode(['message' => 'Veritabanı sorgusu çalıştırılırken bir hata oluştu: ' . $conn->error]);
    exit;
}

$etkinlikler = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['baslangic_tarihi_formatli'] = date("d F Y", strtotime($row['baslangic_tarihi']));
        $row['bitis_tarihi_formatli'] = date("d F Y", strtotime($row['bitis_tarihi']));
        $etkinlikler[] = $row;
    }
}

echo json_encode($etkinlikler);

$conn->close();
?>