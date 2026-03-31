<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız.']);
    exit;
}

if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['id'];
$rota_id = $_POST['rota_id'] ?? '';
$plan_adi = $_POST['plan_adi'] ?? 'Adsız Plan';
$plan_data_json = $_POST['adimlari'] ?? '';
$plan_data = json_decode($plan_data_json, true);

if (empty($rota_id) || json_last_error() !== JSON_ERROR_NONE || !is_array($plan_data) || empty($plan_data)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veya bozuk plan verisi gönderildi.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt_plan = $pdo->prepare("INSERT INTO gezi_planlari (user_id, rota_id, plan_adi) VALUES (?, ?, ?)");
    $stmt_plan->execute([$user_id, $rota_id, $plan_adi]);
    $plan_id = $pdo->lastInsertId();

    $stmt_adim = $pdo->prepare("INSERT INTO gezi_plani_adimlari (plan_id, gun_numarasi, sira, tip, referans_id, ozel_not) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($plan_data as $gunIndex => $gun) {
        if (!isset($gun['adimlar']) || !is_array($gun['adimlar'])) continue;
        $gun_numarasi = $gunIndex + 1;
        foreach ($gun['adimlar'] as $adimIndex => $adim) {
            $sira = $adimIndex + 1;
            $tip = $adim['tip'] ?? 'not';
            $referans_id = $adim['referans_id'] ?? '';
            $ozel_not = $adim['ozel_not'] ?? '';
            $stmt_adim->execute([$plan_id, $gun_numarasi, $sira, $tip, $referans_id, $ozel_not]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Gezi planınız başarıyla kaydedildi!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('gezi_plani_kaydet hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Plan kaydedilirken bir hata oluştu.']);
}
?>
