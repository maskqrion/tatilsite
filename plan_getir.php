<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz paylaşım linki.']);
    exit;
}

// 1. Token'a göre ana plan bilgilerini ve oluşturan kullanıcıyı çek
$stmt_plan = $conn->prepare("
    SELECT 
        gp.plan_adi, 
        gp.olusturulma_tarihi,
        r.ad as rota_adi,
        r.id as rota_id,
        u.name as kullanici_adi
    FROM gezi_planlari gp
    JOIN users u ON gp.user_id = u.id
    JOIN rotalar r ON gp.rota_id = r.id
    WHERE gp.share_token = ?
");
$stmt_plan->bind_param("s", $token);
$stmt_plan->execute();
$stmt_plan->bind_result($plan_adi, $olusturulma_tarihi, $rota_adi, $rota_id, $kullanici_adi);
$plan_data = null;
if ($stmt_plan->fetch()) {
    $plan_data = [
        'plan_adi' => $plan_adi,
        'olusturulma_tarihi' => $olusturulma_tarihi,
        'rota_adi' => $rota_adi,
        'rota_id' => $rota_id,
        'kullanici_adi' => $kullanici_adi
    ];
}
$stmt_plan->close();

if (!$plan_data) {
    echo json_encode(['success' => false, 'message' => 'Paylaşmak istediğiniz plan bulunamadı.']);
    $conn->close();
    exit;
}

// 2. Planın adımlarını çek
$stmt_adim = $conn->prepare("
    SELECT gpa.tip, gpa.referans_id, gpa.ozel_not 
    FROM gezi_plani_adimlari gpa
    JOIN gezi_planlari gp ON gpa.plan_id = gp.id
    WHERE gp.share_token = ? 
    ORDER BY gpa.sira ASC
");
$stmt_adim->bind_param("s", $token);
$stmt_adim->execute();
$stmt_adim->bind_result($tip, $referans_id, $ozel_not);

$adimlari = [];
while ($stmt_adim->fetch()) {
    $adimlari[] = [
        'tip' => $tip,
        'referans_id' => $referans_id,
        'ozel_not' => $ozel_not
    ];
}
$stmt_adim->close();

$plan_data['adimlari'] = $adimlari;
$plan_data['olusturulma_tarihi'] = date("d F Y", strtotime($plan_data['olusturulma_tarihi']));


echo json_encode(['success' => true, 'plan' => $plan_data]);

$conn->close();
?>