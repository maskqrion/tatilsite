<?php
// 1. Doğru veritabanı bağlantı dosyasını çağır.
require 'db_config.php';

// 2. Formdan gelen verileri güvenle al.
$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];

// 3. Şifreyi asla düz metin olarak kaydetme, her zaman hash'le.
// Bu, veritabanın çalınsa bile şifrelerin güvende kalmasını sağlar.
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 4. E-postanın daha önce kullanılıp kullanılmadığını kontrol et.
// Hazırlıklı ifadeler (prepared statements) SQL injection saldırılarını önler.
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // E-posta zaten kayıtlıysa, hata mesajıyla kayıt sayfasına geri yönlendir.
    header("Location: register.html?error=email_exists");
    exit;
} else {
    // 5. Yeni kullanıcıyı veritabanına ekle.
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed_password);

    if ($stmt->execute()) {
        // Kayıt başarılıysa, başarı mesajıyla giriş sayfasına yönlendir.
        header("Location: login.html?success=registration_complete");
        exit;
    } else {
        // Veritabanı hatası olursa, hata mesajıyla kayıt sayfasına geri yönlendir.
        header("Location: register.html?error=db_error");
        exit;
    }
}

// 6. İşlem bittikten sonra bağlantıyı ve sorguyu kapat.
$stmt->close();
$conn->close();
?>