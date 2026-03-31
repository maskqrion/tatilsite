<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$bolge = $_GET['bolge'] ?? 'tumu';
$tip = $_GET['tip'] ?? 'tumu';
$fiyat = $_GET['fiyat'] ?? 'tumu';
$etiket = $_GET['etiket'] ?? '';

$response = [];

try {
    if ($tip === 'tumu' || $tip === 'rota') {
        $sql = "SELECT id, ad, aciklama, koordinatlar, bolge FROM rotalar WHERE koordinatlar IS NOT NULL AND koordinatlar != ''";
        $params = [];

        if ($bolge !== 'tumu') { $sql .= " AND bolge = ?"; $params[] = $bolge; }
        if ($fiyat !== 'tumu') { $sql .= " AND fiyat = ?"; $params[] = $fiyat; }
        if (!empty($etiket))   { $sql .= " AND etiketler LIKE ?"; $params[] = '%' . $etiket . '%'; }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $row) {
            $response[] = ['tip' => 'rota', 'id' => $row['id'], 'ad' => $row['ad'], 'aciklama' => $row['aciklama'], 'koordinatlar' => $row['koordinatlar']];
        }
    }

    if ($tip === 'tumu' || $tip === 'otel' || $tip === 'restoran') {
        $sql = "
            SELECT m.id, m.ad, m.aciklama, m.koordinatlar, m.tip as mekan_tipi, r.id as rota_id
            FROM mekanlar m JOIN rotalar r ON m.rota_id = r.id
            WHERE m.koordinatlar IS NOT NULL AND m.koordinatlar != '' AND m.onaylandi = 1
        ";
        $params = [];

        if ($bolge !== 'tumu') { $sql .= " AND r.bolge = ?"; $params[] = $bolge; }
        if ($fiyat !== 'tumu') { $sql .= " AND r.fiyat = ?"; $params[] = $fiyat; }
        if (!empty($etiket))   { $sql .= " AND r.etiketler LIKE ?"; $params[] = '%' . $etiket . '%'; }
        if ($tip !== 'tumu')   { $sql .= " AND m.tip = ?"; $params[] = $tip; }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $row) {
            $response[] = ['tip' => $row['mekan_tipi'], 'id' => $row['id'], 'ad' => $row['ad'], 'aciklama' => $row['aciklama'], 'koordinatlar' => $row['koordinatlar'], 'rota_id' => $row['rota_id']];
        }
    }

    echo json_encode($response);
} catch (PDOException $e) {
    error_log('harita_verilerini_getir hatası: ' . $e->getMessage());
    echo json_encode([]);
}
?>
