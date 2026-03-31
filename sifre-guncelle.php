<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

// CSRF koruması
if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz CSRF token.']);
    exit;
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($token) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi. Lütfen tüm alanları doldurun.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Şifreniz en az 8 karakter olmalıdır.']);
    exit;
}

$token_hash = hash('sha256', $token);

// Kullanıcıyı token ile ara
$stmt = $pdo->prepare("SELECT id, reset_token_expiry FROM users WHERE reset_token = :token");
$stmt->execute([':token' => $token_hash]);
$user = $stmt->fetch();

if ($user === false) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veya daha önce kullanılmış bir sıfırlama linki.']);
    exit;
}

if (strtotime($user['reset_token_expiry']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Şifre sıfırlama linkinin süresi dolmuş. Lütfen yeni bir talep oluşturun.']);
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt_update = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expiry = NULL WHERE id = :id");
    $stmt_update->execute([':password' => $hashed_password, ':id' => $user['id']]);
    echo json_encode(['success' => true, 'message' => 'Şifreniz başarıyla güncellendi! Şimdi giriş yapabilirsiniz.']);
} catch (PDOException $e) {
    error_log('sifre-guncelle.php: Şifre güncelleme hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Şifre güncellenirken bir hata oluştu.']);
}
?>
