<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz paylaşım linki.']);
    exit;
}

try {
    // Token'a göre ana plan bilgilerini çek
    $stmt_plan = $pdo->prepare("
        SELECT gp.plan_adi, gp.olusturulma_tarihi, r.ad as rota_adi, r.id as rota_id, u.name as kullanici_adi
        FROM gezi_planlari gp
        JOIN users u ON gp.user_id = u.id
        JOIN rotalar r ON gp.rota_id = r.id
        WHERE gp.share_token = ?
    ");
    $stmt_plan->execute([$token]);
    $plan_data = $stmt_plan->fetch();

    if (!$plan_data) {
        echo json_encode(['success' => false, 'message' => 'Paylaşmak istediğiniz plan bulunamadı.']);
        exit;
    }

    // Planın adımlarını çek
    $stmt_adim = $pdo->prepare("
        SELECT gpa.tip, gpa.referans_id, gpa.ozel_not
        FROM gezi_plani_adimlari gpa
        JOIN gezi_planlari gp ON gpa.plan_id = gp.id
        WHERE gp.share_token = ?
        ORDER BY gpa.sira ASC
    ");
    $stmt_adim->execute([$token]);
    $plan_data['adimlari'] = $stmt_adim->fetchAll();
    $plan_data['olusturulma_tarihi'] = date("d F Y", strtotime($plan_data['olusturulma_tarihi']));

    echo json_encode(['success' => true, 'plan' => $plan_data]);

} catch (PDOException $e) {
    error_log('plan_getir hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Plan yüklenirken bir hata oluştu.']);
}
?>
