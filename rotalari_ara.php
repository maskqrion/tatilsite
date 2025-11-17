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
$types = '';

// Filtreleme koşullarını ekle
if (!empty($arama_terimi)) {
    $sql .= " AND (r.ad LIKE ? OR r.aciklama LIKE ?)";
    $arama_terimi_like = '%' . $arama_terimi . '%';
    $params[] = $arama_terimi_like;
    $params[] = $arama_terimi_like;
    $types .= 'ss';
}

if ($bolge !== 'tumu') {
    $sql .= " AND r.bolge = ?";
    $params[] = $bolge;
    $types .= 's';
}

if ($fiyat !== 'tumu') {
    $sql .= " AND r.fiyat = ?";
    $params[] = $fiyat;
    $types .= 's';
}

if ($aktivite !== 'tumu') {
    $sql .= " AND FIND_IN_SET(?, r.aktiviteler)";
    $params[] = $aktivite;
    $types .= 's';
}

// GÜNCELLEME: Etiket filtresini ekle
if (!empty($etiket)) {
    // Virgülle ayrılmış etiketler içinde arama yapar
    $sql .= " AND r.etiketler LIKE ?";
    $etiket_like = '%' . $etiket . '%';
    $params[] = $etiket_like;
    $types .= 's';
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

$stmt = $conn->prepare($sql);

if ($types) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$rotalar = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['aktiviteler'] = explode(',', $row['aktiviteler']);
        $rotalar[] = $row;
    }
}

echo json_encode($rotalar);

$stmt->close();
$conn->close();
?>