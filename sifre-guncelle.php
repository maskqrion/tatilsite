<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($token) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi. Lütfen tüm alanları doldurun.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Şifreniz en az 6 karakter olmalıdır.']);
    exit;
}

$token_hash = hash('sha256', $token);

// bind_result Düzeltmesi
$stmt = $conn->prepare("SELECT id, reset_token_expiry FROM users WHERE reset_token = ?");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$stmt->bind_result($id, $reset_token_expiry);
$user = null;
if ($stmt->fetch()) {
    $user = ['id' => $id, 'reset_token_expiry' => $reset_token_expiry];
}
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veya daha önce kullanılmış bir sıfırlama linki.']);
    $conn->close();
    exit;
}

if (strtotime($user['reset_token_expiry']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Şifre sıfırlama linkinin süresi dolmuş. Lütfen yeni bir talep oluşturun.']);
    $conn->close();
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt_update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
$stmt_update->bind_param("si", $hashed_password, $user['id']);

if ($stmt_update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Şifreniz başarıyla güncellendi! Şimdi giriş yapabilirsiniz.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Şifre güncellenirken bir hata oluştu.']);
}

$stmt_update->close();
$conn->close();
?>