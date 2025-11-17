<?php
require 'db_config.php';
require 'sende-email.php'; 
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

$email = $_POST['email'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen geçerli bir e-posta adresi girin.']);
    exit;
}

// bind_result Düzeltmesi
$stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id, $name);
$user = null;
if($stmt->fetch()) {
    $user = ['id' => $id, 'name' => $name];
}
$stmt->close();

if (!$user) {
    echo json_encode(['success' => true, 'message' => 'Eğer bu e-posta adresi sistemimizde kayıtlıysa, bir sıfırlama bağlantısı gönderilmiştir.']);
    $conn->close();
    exit;
}

$token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $token);
$expiry_date = date('Y-m-d H:i:s', time() + 3600); 

$stmt_update = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
$stmt_update->bind_param("sss", $token_hash, $expiry_date, $email);

if (!$stmt_update->execute()) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen tekrar deneyin.']);
    $stmt_update->close();
    $conn->close();
    exit;
}
$stmt_update->close();

$reset_link = "https://www.seckinrotalar.com/sifre-sifirla.html?token=" . $token; 

$to = $email;
$toName = $user['name'];
$subject = "Seçkin Rotalar - Şifre Sıfırlama Talebi";
$message_body = "
    <p>Merhaba {$toName},</p>
    <p>Hesabınız için bir şifre sıfırlama talebi aldık.</p>
    <p>Yeni bir şifre belirlemek için lütfen aşağıdaki bağlantıya tıklayın. Bu bağlantı 1 saat geçerlidir:</p>
    <p><a href='{$reset_link}' style='display: inline-block; padding: 10px 15px; background-color: #007782; color: #ffffff; text-decoration: none; border-radius: 5px;'>Şifremi Sıfırla</a></p>
    <p>Eğer bu talebi siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz.</p>
    <br>
    <p>Saygılarımızla,<br>
    Seçkin Rotalar Ekibi</p>
";

if (sendEmail($to, $toName, $subject, $message_body, true)) {
    echo json_encode(['success' => true, 'message' => 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.']);
} else {
    error_log("sifre-talep.php: PHPMailer e-posta gönderemedi. SMTP ayarlarını kontrol edin.");
    echo json_encode(['success' => false, 'message' => "E-posta gönderilirken bir sunucu hatası oluştu."]);
}

$conn->close();
?>