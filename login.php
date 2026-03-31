<?php
// 1. Oturumları kullanacağımız için en başa session_start() komutunu ekle.
// BU SATIR ÇOK ÖNEMLİ!
session_start();

// 2. Doğru veritabanı bağlantı dosyasını çağır.
require 'db_config.php';

// CSRF koruması
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.html");
    exit;
}

if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: login.html?error=invalid_csrf");
    exit;
}

// 3. Formdan gelen verileri al.
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 4. Kullanıcıyı e-posta adresine göre veritabanında ara.
$stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

// 5. Kullanıcı bulunduysa...
if ($user !== false) {
    // 6. Formdan gelen şifre ile veritabanındaki hash'lenmiş şifreyi karşılaştır.
    if (password_verify($password, $user['password'])) {
        // Şifre doğruysa, oturum (session) değişkenlerini ayarla.
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $user['id'];
        $_SESSION['name'] = $user['name'];

        // 7. Ana sayfaya yönlendir.
        header("Location: index.html");
        exit;
    }
}

// Eğer kullanıcı bulunamadıysa veya şifre yanlışsa,
// bu satıra gelinecek ve giriş sayfasına hata mesajıyla geri yönlendirilecektir.
header("Location: login.html?error=invalid_credentials");
exit;
?>
