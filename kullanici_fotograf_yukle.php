<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

// 1. Güvenlik Kontrolleri
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Fotoğraf yüklemek için giriş yapmalısınız.']);
    exit;
}

if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['id'];
$rota_id = $_POST['rota_id'] ?? '';

// 2. Dosya Yükleme İşlemleri
if (isset($_FILES['user_photo']) && $_FILES['user_photo']['error'] == 0 && !empty($rota_id)) {
    
    $target_dir = "assets/community_uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // GÜNCELLENDİ: WebP dönüştürme mantığı eklendi
    $file_info = $_FILES['user_photo'];
    $file_tmp_path = $file_info['tmp_name'];
    $file_extension = strtolower(pathinfo($file_info["name"], PATHINFO_EXTENSION));
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types) || $file_info["size"] > 5000000) { // 5MB limit
        echo json_encode(['success' => false, 'message' => 'Hata: Sadece 5MB\'dan küçük JPG, PNG, GIF dosyaları yüklenebilir.']);
        exit;
    }

    // Dosyayı belleğe al
    $image_resource = null;
    if ($file_extension === 'jpg' || $file_extension === 'jpeg') {
        $image_resource = imagecreatefromjpeg($file_tmp_path);
    } elseif ($file_extension === 'png') {
        $image_resource = imagecreatefrompng($file_tmp_path);
    } elseif ($file_extension === 'gif') {
        $image_resource = imagecreatefromgif($file_tmp_path);
    }

    if ($image_resource === null) {
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen resim formatı.']);
        exit;
    }
    
    // Yeni, benzersiz dosya adı ve yolu (uzantısı .webp olacak)
    $new_file_name = $rota_id . '_' . $user_id . '_' . time() . '.webp';
    $target_file = $target_dir . $new_file_name;

    // Dosyayı WebP olarak kaydet
    if (imagewebp($image_resource, $target_file, 80)) { // Kalite 80
        imagedestroy($image_resource); // Hafızayı boşalt

        // Veritabanına kaydet (onay bekliyor olarak)
        try {
            $stmt = $conn->prepare("INSERT INTO kullanici_fotograflari (user_id, rota_id, resim_url, durum) VALUES (?, ?, ?, 'beklemede')");
            $stmt->bind_param("iss", $user_id, $rota_id, $target_file);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Fotoğrafınız başarıyla yüklendi! Onaylandıktan sonra galeride görünecektir.']);
            } else {
                unlink($target_file); // Veritabanı hatası olursa yüklenen resmi sil
                echo json_encode(['success' => false, 'message' => 'Veritabanı kaydı sırasında bir hata oluştu.']);
            }
        } catch (Exception $e) {
            unlink($target_file); // Veritabanı hatası olursa yüklenen resmi sil
            echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
        }
    } else {
        imagedestroy($image_resource);
        echo json_encode(['success' => false, 'message' => 'Fotoğraf optimize edilirken bir hata oluştu.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Lütfen bir fotoğraf seçin ve tekrar deneyin.']);
}

$conn->close();
?>