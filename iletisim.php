<?php
session_start();
header('Content-Type: application/json');

// Sadece veritabanı bağlantısını ve merkezi e-posta fonksiyonunu dahil et
require 'db_config.php'; 
require 'send_email.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF token kontrolü
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token doğrulanamadı.']);
        exit;
    }

    // Formdan gelen verileri güvenli bir şekilde al
    $name = strip_tags(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = trim($_POST["message"]);

    // Temel doğrulama
    if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doğru şekilde doldurun.']);
        exit;
    }

    // --- YENİ E-POSTA GÖNDERME YÖNTEMİ ---

    // 1. E-postayı alacak olan adminin e-postasını .env dosyasından al
    //    (Eğer .env'de ADMIN_EMAIL yoksa, .env'deki SMTP_USERNAME'i (kendinizi) kullanır)
    $admin_email = $_ENV['ADMIN_EMAIL'] ?? $_ENV['SMTP_USERNAME'];

    // 2. E-posta konusunu ve içeriğini hazırla
    $subject = "Yeni İletişim Formu Mesajı: $name";
    $body = "
        Ad Soyad: $name<br>
        E-posta: $email<br><br>
        Mesaj:<br>
        <p style='border-left: 3px solid #ccc; padding-left: 10px; font-style: italic;'>
            " . nl2br(htmlspecialchars($message)) . "
        </p>
    ";

    // 3. Merkezi fonksiyonu kullanarak e-postayı gönder
    if (sendEmail($admin_email, 'Site Admini', $subject, $body, true)) {
        echo json_encode(['success' => true, 'message' => 'Mesajınız başarıyla gönderildi.']);
    } else {
        error_log("iletisim.php: sendEmail fonksiyonu başarısız oldu.");
        echo json_encode(['success' => false, 'message' => 'Mesajınız gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
}
?>