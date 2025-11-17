<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

// 1. Kullanıcı giriş yapmış mı diye kontrol et
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['id'];
$plan_id = $_GET['plan_id'] ?? 0;

if ($plan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz plan kimliği.']);
    exit;
}

// 2. Planın bu kullanıcıya ait olup olmadığını doğrula
$stmt = $conn->prepare("SELECT id, share_token FROM gezi_planlari WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $plan_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$plan = $result->fetch_assoc();
$stmt->close();

if (!$plan) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Bu plana erişim yetkiniz yok.']);
    exit;
}

// 3. Eğer planın daha önce oluşturulmuş bir token'ı yoksa, yeni bir tane oluştur
$token = $plan['share_token'];
if (empty($token)) {
    $token = bin2hex(random_bytes(16)); // Benzersiz ve tahmin edilemez bir token oluştur
    
    // Yeni token'ı veritabanına kaydet
    $stmt_update = $conn->prepare("UPDATE gezi_planlari SET share_token = ? WHERE id = ?");
    $stmt_update->bind_param("si", $token, $plan_id);
    $stmt_update->execute();
    $stmt_update->close();
}

// 4. Paylaşım URL'sini oluştur ve JSON olarak geri döndür
// Not: "https://www.seckinrotalar.com" kısmını kendi site adresinizle değiştirin
$share_url = "https://www.seckinrotalar.com/plan.html?token=" . $token;

echo json_encode(['success' => true, 'share_url' => $share_url]);

$conn->close();
?>