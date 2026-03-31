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
$plan_id = (int)($_POST['plan_id'] ?? 0);

if ($plan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz plan kimliği.']);
    exit;
}

try {
    // Yetki kontrolü
    $stmt_check = $pdo->prepare("SELECT id FROM gezi_planlari WHERE id = ? AND user_id = ?");
    $stmt_check->execute([$plan_id, $user_id]);
    if (!$stmt_check->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu planı silme yetkiniz yok.']);
        exit;
    }

    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM gezi_plani_adimlari WHERE plan_id = ?")->execute([$plan_id]);
    $pdo->prepare("DELETE FROM gezi_planlari WHERE id = ?")->execute([$plan_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Plan başarıyla silindi.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('gezi_plani_sil hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Plan silinirken bir hata oluştu.']);
}
?>
