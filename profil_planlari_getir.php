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
$pdo = getDB();
$response = ['success' => true, 'planlar' => []];

try {
    // 1. Kullanıcının tüm gezi planlarını çek
    $stmt_plan = $pdo->prepare("SELECT id, rota_id, plan_adi, olusturulma_tarihi FROM gezi_planlari WHERE user_id = ? ORDER BY olusturulma_tarihi DESC");
    $stmt_plan->execute([$user_id]);

    $planlar = [];
    $plan_idler = [];
    $plan_map = [];

    while ($row = $stmt_plan->fetch()) {
        $plan_row = [
            'id' => $row['id'],
            'rota_id' => $row['rota_id'],
            'plan_adi' => $row['plan_adi'],
            'olusturulma_tarihi' => date("d F Y", strtotime($row['olusturulma_tarihi'])),
            'adimlari' => []
        ];
        $planlar[] = $plan_row;
        $plan_idler[] = $row['id'];
        $plan_map[$row['id']] = count($planlar) - 1;
    }

    // 2. Her planın adımlarını çek (IN clause dinamik placeholder)
    if (!empty($plan_idler)) {
        $placeholders = implode(',', array_fill(0, count($plan_idler), '?'));

        $stmt_adim = $pdo->prepare("SELECT plan_id, tip, referans_id, ozel_not FROM gezi_plani_adimlari WHERE plan_id IN ($placeholders) ORDER BY sira ASC");
        $stmt_adim->execute($plan_idler);

        while ($adim = $stmt_adim->fetch()) {
            if (isset($plan_map[$adim['plan_id']])) {
                $plan_index = $plan_map[$adim['plan_id']];
                $planlar[$plan_index]['adimlari'][] = [
                    'tip' => $adim['tip'],
                    'referans_id' => $adim['referans_id'],
                    'ozel_not' => $adim['ozel_not']
                ];
            }
        }
    }

    $response['planlar'] = $planlar;
    echo json_encode($response);

} catch (PDOException $e) {
    error_log('profil_planlari_getir.php hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Planlar yüklenirken bir hata oluştu.']);
}
?>
