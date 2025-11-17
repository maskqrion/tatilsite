<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['id'];
$plan_id = $_POST['plan_id'] ?? 0;

if ($plan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz plan kimliği.']);
    exit;
}

try {
    // bind_result Düzeltmesi (store_result ile)
    $stmt_check = $conn->prepare("SELECT id FROM gezi_planlari WHERE id = ? AND user_id = ?");
    $stmt_check->bind_param("ii", $plan_id, $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows === 0) {
        http_response_code(403); 
        echo json_encode(['success' => false, 'message' => 'Bu planı silme yetkiniz yok.']);
        $stmt_check->close();
        $conn->close();
        exit;
    }
    $stmt_check->close();

    $conn->begin_transaction();

    $stmt_delete_adimlari = $conn->prepare("DELETE FROM gezi_plani_adimlari WHERE plan_id = ?");
    $stmt_delete_adimlari->bind_param("i", $plan_id);
    $stmt_delete_adimlari->execute();
    $stmt_delete_adimlari->close();

    $stmt_delete_plan = $conn->prepare("DELETE FROM gezi_planlari WHERE id = ?");
    $stmt_delete_plan->bind_param("i", $plan_id);
    $stmt_delete_plan->execute();
    $stmt_delete_plan->close();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Plan başarıyla silindi.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Plan silinirken bir veritabanı hatası oluştu.']);
}

$conn->close();
?>