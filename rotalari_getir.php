<?php
require 'db_config.php'; // Veritabanı bağlantısı
header('Content-Type: application/json');

try {
    $pdo = getDB();

    // Rotalar sayfasında gerekli olan temel bilgileri çekiyoruz
    $stmt = $pdo->prepare("SELECT id, ad, aciklama, resim, bolge, fiyat, aktiviteler FROM rotalar");
    $stmt->execute();
    $rotalar = $stmt->fetchAll();

    // Aktiviteler verisini virgülle ayrılmış string'den bir diziye çeviriyoruz
    foreach ($rotalar as &$row) {
        $row['aktiviteler'] = explode(',', $row['aktiviteler']);
    }
    unset($row);

    echo json_encode($rotalar);

} catch (Exception $e) {
    error_log('rotalari_getir hatası: ' . $e->getMessage());
    echo json_encode([]);
}
?>
