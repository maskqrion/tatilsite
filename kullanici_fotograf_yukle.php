<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

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

if (isset($_FILES['user_photo']) && $_FILES['user_photo']['error'] === 0 && !empty($rota_id)) {
    $target_dir = "assets/community_uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }

    $file_info = $_FILES['user_photo'];
    $file_tmp_path = $file_info['tmp_name'];
    $file_extension = strtolower(pathinfo($file_info["name"], PATHINFO_EXTENSION));

    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types, true) || $file_info["size"] > 5000000) {
        echo json_encode(['success' => false, 'message' => 'Sadece 5MB\'dan küçük JPG, PNG, GIF dosyaları yüklenebilir.']);
        exit;
    }

    // MIME doğrulaması
    $finfo = getimagesize($file_tmp_path);
    if (!$finfo || !in_array($finfo['mime'], ['image/jpeg', 'image/png', 'image/gif'], true)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz resim dosyası.']);
        exit;
    }

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

    $new_file_name = $rota_id . '_' . $user_id . '_' . time() . '.webp';
    $target_file = $target_dir . $new_file_name;

    if (imagewebp($image_resource, $target_file, 80)) {
        imagedestroy($image_resource);
        try {
            $stmt = $pdo->prepare("INSERT INTO kullanici_fotograflari (user_id, rota_id, resim_url, durum) VALUES (?, ?, ?, 'beklemede')");
            $stmt->execute([$user_id, $rota_id, $target_file]);
            echo json_encode(['success' => true, 'message' => 'Fotoğrafınız başarıyla yüklendi! Onaylandıktan sonra galeride görünecektir.']);
        } catch (PDOException $e) {
            unlink($target_file);
            error_log('kullanici_fotograf_yukle hatası: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Veritabanı kaydı sırasında bir hata oluştu.']);
        }
    } else {
        imagedestroy($image_resource);
        echo json_encode(['success' => false, 'message' => 'Fotoğraf optimize edilirken bir hata oluştu.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Lütfen bir fotoğraf seçin ve tekrar deneyin.']);
}
?>
