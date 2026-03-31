<?php
// 1. Oturumu başlat (CSRF koruması için gerekli).
session_start();

// 2. Doğru veritabanı bağlantı dosyasını çağır.
require 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.html");
    exit;
}

// CSRF koruması
if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: register.html?error=invalid_csrf");
    exit;
}

// 3. Formdan gelen verileri güvenle al.
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 4. Şifreyi asla düz metin olarak kaydetme, her zaman hash'le.
// Bu, veritabanın çalınsa bile şifrelerin güvende kalmasını sağlar.
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 5. E-postanın daha önce kullanılıp kullanılmadığını kontrol et.
// Hazırlıklı ifadeler (prepared statements) SQL injection saldırılarını önler.
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);

if ($stmt->fetch() !== false) {
    // E-posta zaten kayıtlıysa, hata mesajıyla kayıt sayfasına geri yönlendir.
    header("Location: register.html?error=email_exists");
    exit;
}

// 6. Yeni kullanıcıyı veritabanına ekle.
try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
    $stmt->execute([':name' => $name, ':email' => $email, ':password' => $hashed_password]);
    // Kayıt başarılıysa, başarı mesajıyla giriş sayfasına yönlendir.
    header("Location: login.html?success=registration_complete");
    exit;
} catch (PDOException $e) {
    // Veritabanı hatası olursa, logla ve hata mesajıyla kayıt sayfasına geri yönlendir.
    error_log('register.php: Kayıt hatası: ' . $e->getMessage());
    header("Location: register.html?error=db_error");
    exit;
}
?>
