<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

// Formdan gelen verileri al
$arama_terimi = $_GET['arama'] ?? '';
$bolge = $_GET['bolge'] ?? 'tumu';
$fiyat = $_GET['fiyat'] ?? 'tumu';
$aktivite = $_GET['aktivite'] ?? 'tumu';
$siralama = $_GET['siralama'] ?? 'varsayilan';
// GÜNCELLEME: Yeni etiket parametresini al
$etiket = $_GET['etiket'] ?? '';

// Temel SQL sorgusunu oluştur. Yorumların ortalama puanını (avg_puan) hesapla.
$sql = "
    SELECT
        r.id, r.ad, r.aciklama, r.resim, r.bolge, r.fiyat, r.aktiviteler, r.etiketler,
        AVG(y.puan) as avg_puan
    FROM rotalar r
    LEFT JOIN yorumlar y ON r.id = y.rota_id
    WHERE 1=1
";

$params = [];

// Filtreleme koşullarını ekle
if (!empty($arama_terimi)) {
    $sql .= " AND (r.ad LIKE ? OR r.aciklama LIKE ?)";
    $arama_terimi_like = '%' . $arama_terimi . '%';
    $params[] = $arama_terimi_like;
    $params[] = $arama_terimi_like;
}

if ($bolge !== 'tumu') {
    $sql .= " AND r.bolge = ?";
    $params[] = $bolge;
}

if ($fiyat !== 'tumu') {
    $sql .= " AND r.fiyat = ?";
    $params[] = $fiyat;
}

if ($aktivite !== 'tumu') {
    $sql .= " AND FIND_IN_SET(?, r.aktiviteler)";
    $params[] = $aktivite;
}

// GÜNCELLEME: Etiket filtresini ekle
if (!empty($etiket)) {
    // Virgülle ayrılmış etiketler içinde arama yapar
    $sql .= " AND r.etiketler LIKE ?";
    $params[] = '%' . $etiket . '%';
}

// Tüm rotaları grupla (ortalama puanı doğru hesaplamak için)
$sql .= " GROUP BY r.id";

// Sıralama koşullarını ekle
switch ($siralama) {
    case 'puan-desc':
        $sql .= " ORDER BY avg_puan DESC, r.ad ASC";
        break;
    case 'ad-az':
        $sql .= " ORDER BY r.ad ASC";
        break;
    case 'ad-za':
        $sql .= " ORDER BY r.ad DESC";
        break;
    default:
        $sql .= " ORDER BY r.ad ASC";
        break;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rotalar = $stmt->fetchAll();

    foreach ($rotalar as &$row) {
        $row['aktiviteler'] = explode(',', $row['aktiviteler']);
    }
    unset($row);

    echo json_encode($rotalar);

} catch (Exception $e) {
    error_log('rotalari_ara hatası: ' . $e->getMessage());
    echo json_encode([]);
}
?>
