<?php
// PHPMailer kütüphanesini dahil et
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// .env dosyasındaki değişkenleri yükleyen db_config.php'yi dahil et
// (Bu dosya veritabanı bağlantısı yapmaz, sadece $_ENV değişkenlerini yükler)
require_once 'db_config.php';

/**
 * Sitenin herhangi bir yerinden e-posta göndermek için merkezi fonksiyon.
 *
 * @param string $toEmail Alıcının e-posta adresi
 * @param string $toName Alıcının adı
 * @param string $subject E-postanın konusu
 * @param string $body E-postanın içeriği (HTML veya düz metin)
 * @param bool $isHTML İçerik HTML formatında mı?
 * @return bool Gönderim başarılıysa true, değilse false döner.
 */
function sendEmail($toEmail, $toName, $subject, $body, $isHTML = false) {

    // .env dosyasından ayarları çek (db_config.php sayesinde $_ENV içinde)
    $smtpHost = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
    $smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
    $smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
    $smtpPort = $_ENV['SMTP_PORT'] ?? 587;
    $smtpFromAddress = $_ENV['SMTP_FROM_ADDRESS'] ?? 'no-reply@example.com';
    $smtpFromName = $_ENV['SMTP_FROM_NAME'] ?? 'Web Siteniz';

    // Eğer SMTP bilgileri .env'de yoksa, göndermeyi deneme
    if (empty($smtpUsername) || empty($smtpPassword)) {
        error_log("SMTP ayarları eksik. E-posta gönderilemedi.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Sunucu Ayarları
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUsername;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;
        $mail->CharSet    = 'UTF-8';

        // Gönderen
        $mail->setFrom($smtpFromAddress, $smtpFromName);

        // Alıcı
        $mail->addAddress($toEmail, $toName);

        // İçerik
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Hata olursa log dosyasına yaz
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>