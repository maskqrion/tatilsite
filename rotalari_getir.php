<?php
require 'db_config.php'; // Veritabanı bağlantısı
header('Content-Type: application/json');

// Rotalar sayfasında gerekli olan temel bilgileri çekiyoruz
$sql = "SELECT id, ad, aciklama, resim, bolge, fiyat, aktiviteler FROM rotalar";
$result = $conn->query($sql);

$rotalar = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Aktiviteler verisini virgülle ayrılmış string'den bir diziye çeviriyoruz
        $row['aktiviteler'] = explode(',', $row['aktiviteler']);
        $rotalar[] = $row;
    }
}

echo json_encode($rotalar);

$conn->close();
?>