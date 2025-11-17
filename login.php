<?php
// 1. Oturumları kullanacağımız için en başa session_start() komutunu ekle.
// BU SATIR ÇOK ÖNEMLİ!
session_start();

// 2. Doğru veritabanı bağlantı dosyasını çağır.
require 'db_config.php';

// 3. Formdan gelen verileri al.
$email = $_POST['email'];
$password = $_POST['password'];

// 4. Kullanıcıyı e-posta adresine göre veritabanında ara.
$stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

// 5. Kullanıcı bulunduysa...
if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $name, $hashed_password);
    $stmt->fetch();

    // 6. Formdan gelen şifre ile veritabanındaki hash'lenmiş şifreyi karşılaştır.
    if (password_verify($password, $hashed_password)) {
        // Şifre doğruysa, oturum (session) değişkenlerini ayarla.
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $id;
        $_SESSION['name'] = $name;

        // 7. Ana sayfaya yönlendir.
        header("Location: index.html");
        exit;
    }
}

// Eğer kullanıcı bulunamadıysa veya şifre yanlışsa,
// bu satıra gelinecek ve giriş sayfasına hata mesajıyla geri yönlendirilecektir.
header("Location: login.html?error=invalid_credentials");
exit;

// Not: Bu alttaki kodlara normalde hiç ulaşılmaz ama iyi bir pratiktir.
$stmt->close();
$conn->close();
?>