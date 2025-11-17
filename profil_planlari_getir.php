<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu sayfayı görmek için giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['id'];
$response = ['success' => true, 'planlar' => []];

// 1. Kullanıcının tüm gezi planlarını çek (bind_result Düzeltmesi)
$stmt_plan = $conn->prepare("SELECT id, rota_id, plan_adi, olusturulma_tarihi FROM gezi_planlari WHERE user_id = ? ORDER BY olusturulma_tarihi DESC");
$stmt_plan->bind_param("i", $user_id);
$stmt_plan->execute();
$stmt_plan->bind_result($plan_id, $rota_id, $plan_adi, $olusturulma_tarihi);

$planlar = [];
$plan_idler = [];
$plan_map = [];

while ($stmt_plan->fetch()) {
    $plan_id_val = $plan_id; 
    $plan_row = [
        'id' => $plan_id_val,
        'rota_id' => $rota_id,
        'plan_adi' => $plan_adi,
        'olusturulma_tarihi' => date("d F Y", strtotime($olusturulma_tarihi)),
        'adimlari' => []
    ];
    $planlar[] = $plan_row;
    $plan_idler[] = $plan_id_val;
    $plan_map[$plan_id_val] = count($planlar) - 1; 
}
$stmt_plan->close();

// 2. Her planın adımlarını çek (bind_result Düzeltmesi)
if (!empty($plan_idler)) {
    $in_clause = implode(',', array_fill(0, count($plan_idler), '?'));
    $types = str_repeat('i', count($plan_idler));
    
    $stmt_adim = $conn->prepare("SELECT plan_id, tip, referans_id, ozel_not FROM gezi_plani_adimlari WHERE plan_id IN ($in_clause) ORDER BY sira ASC");
    $stmt_adim->bind_param($types, ...$plan_idler);
    $stmt_adim->execute();
    $stmt_adim->bind_result($adim_plan_id, $adim_tip, $adim_referans_id, $adim_ozel_not);
    
    while ($stmt_adim->fetch()) {
        if (isset($plan_map[$adim_plan_id])) {
            $plan_index = $plan_map[$adim_plan_id];
            $planlar[$plan_index]['adimlari'][] = [
                'tip' => $adim_tip,
                'referans_id' => $adim_referans_id,
                'ozel_not' => $adim_ozel_not
            ];
        }
    }
    $stmt_adim->close();
}

$response['planlar'] = $planlar;
echo json_encode($response);

$conn->close();
?>