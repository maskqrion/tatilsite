<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$bolge = $_GET['bolge'] ?? 'tumu';
$tip = $_GET['tip'] ?? 'tumu';
$fiyat = $_GET['fiyat'] ?? 'tumu';
$etiket = $_GET['etiket'] ?? '';

$response = [];
$params = [];
$types = '';

if ($tip === 'tumu' || $tip === 'rota') {
    $sql_rotalar = "SELECT id, ad, aciklama, koordinatlar, bolge FROM rotalar WHERE koordinatlar IS NOT NULL AND koordinatlar != ''";
    $rota_params = [];
    $rota_types = '';
    
    if ($bolge !== 'tumu') {
        $sql_rotalar .= " AND bolge = ?";
        $rota_params[] = $bolge;
        $rota_types .= 's';
    }
    if ($fiyat !== 'tumu') {
        $sql_rotalar .= " AND fiyat = ?";
        $rota_params[] = $fiyat;
        $rota_types .= 's';
    }
    if (!empty($etiket)) {
        $sql_rotalar .= " AND etiketler LIKE ?";
        $rota_params[] = '%' . $etiket . '%';
        $rota_types .= 's';
    }

    $stmt_rotalar = $conn->prepare($sql_rotalar);
    if (!empty($rota_types)) {
        $stmt_rotalar->bind_param($rota_types, ...$rota_params);
    }
    $stmt_rotalar->execute();
    $stmt_rotalar->bind_result($id, $ad, $aciklama, $koordinatlar, $bolge_res);

    while($stmt_rotalar->fetch()) {
        $response[] = [
            'tip' => 'rota',
            'id' => $id,
            'ad' => $ad,
            'aciklama' => $aciklama,
            'koordinatlar' => $koordinatlar
        ];
    }
    $stmt_rotalar->close();
}

if ($tip === 'tumu' || $tip === 'otel' || $tip === 'restoran') {
    $sql_mekanlar = "
        SELECT m.id, m.ad, m.aciklama, m.koordinatlar, m.tip as mekan_tipi, r.id as rota_id, r.bolge 
        FROM mekanlar m
        JOIN rotalar r ON m.rota_id = r.id
        WHERE m.koordinatlar IS NOT NULL AND m.koordinatlar != '' AND m.onaylandi = 1
    ";

    $mekan_params = [];
    $mekan_types = '';

    if ($bolge !== 'tumu') {
        $sql_mekanlar .= " AND r.bolge = ?";
        $mekan_params[] = $bolge;
        $mekan_types .= 's';
    }
    if ($fiyat !== 'tumu') {
        $sql_mekanlar .= " AND r.fiyat = ?";
        $mekan_params[] = $fiyat;
        $mekan_types .= 's';
    }
     if (!empty($etiket)) {
        $sql_mekanlar .= " AND r.etiketler LIKE ?";
        $mekan_params[] = '%' . $etiket . '%';
        $mekan_types .= 's';
    }
    if ($tip !== 'tumu') {
         $sql_mekanlar .= " AND m.tip = ?";
         $mekan_params[] = $tip;
         $mekan_types .= 's';
    }

    $stmt_mekanlar = $conn->prepare($sql_mekanlar);
    if (!empty($mekan_types)) {
        $stmt_mekanlar->bind_param($mekan_types, ...$mekan_params);
    }
    $stmt_mekanlar->execute();
    $stmt_mekanlar->bind_result($id, $ad, $aciklama, $koordinatlar, $mekan_tipi, $rota_id, $bolge_res);
    
    while($stmt_mekanlar->fetch()) {
        $response[] = [
            'tip' => $mekan_tipi,
            'id' => $id,
            'ad' => $ad,
            'aciklama' => $aciklama,
            'koordinatlar' => $koordinatlar,
            'rota_id' => $rota_id
        ];
    }
    $stmt_mekanlar->close();
}

echo json_encode($response);
$conn->close();
?>