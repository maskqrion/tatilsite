<?php
session_start();
header('Content-Type: application/json');

require 'db_config.php';
require 'send_email.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CSRF token kontrolü (isset güvenliği eklendi)
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token doğrulanamadı.']);
        exit;
    }

    $name = strip_tags(trim($_POST["name"] ?? ''));
    $email = filter_var(trim($_POST["email"] ?? ''), FILTER_SANITIZE_EMAIL);
    $message = trim($_POST["message"] ?? '');

    if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doğru şekilde doldurun.']);
        exit;
    }

    $admin_email = $_ENV['ADMIN_EMAIL'] ?? $_ENV['SMTP_USERNAME'];

    $subject = "Yeni İletişim Formu Mesajı: $name";
    $body = "
        Ad Soyad: " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "<br>
        E-posta: " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "<br><br>
        Mesaj:<br>
        <p style='border-left: 3px solid #ccc; padding-left: 10px; font-style: italic;'>
            " . nl2br(htmlspecialchars($message)) . "
        </p>
    ";

    if (sendEmail($admin_email, 'Site Admini', $subject, $body, true)) {
        echo json_encode(['success' => true, 'message' => 'Mesajınız başarıyla gönderildi.']);
    } else {
        error_log("iletisim.php: sendEmail fonksiyonu başarısız oldu.");
        echo json_encode(['success' => false, 'message' => 'Mesajınız gönderilirken bir hata oluştu.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
}
?>
