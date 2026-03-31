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
$plan_id = (int)($_GET['plan_id'] ?? 0);

if ($plan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz plan kimliği.']);
    exit;
}

// Planın bu kullanıcıya ait olup olmadığını doğrula
$stmt = $pdo->prepare("SELECT id, share_token FROM gezi_planlari WHERE id = ? AND user_id = ?");
$stmt->execute([$plan_id, $user_id]);
$plan = $stmt->fetch();

if (!$plan) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu plana erişim yetkiniz yok.']);
    exit;
}

// Token yoksa oluştur
$token = $plan['share_token'];
if (empty($token)) {
    $token = bin2hex(random_bytes(16));
    $stmt_update = $pdo->prepare("UPDATE gezi_planlari SET share_token = ? WHERE id = ?");
    $stmt_update->execute([$token, $plan_id]);
}

$share_url = "https://www.seckinrotalar.com/plan.html?token=" . $token;
echo json_encode(['success' => true, 'share_url' => $share_url]);
?>
