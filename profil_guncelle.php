<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

// === YENİ EKLENDİ: CSRF KONTROLÜ ===
if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}
// === GÜNCELLEME SONU ===

$user_id = $_SESSION['id'];
$response = ['success' => false, 'message' => 'Herhangi bir değişiklik yapılmadı.'];
$updated = false;

// Formdan gelen adı ve hakkımda metnini al
$name = $_POST['name'] ?? '';
$hakkimda = $_POST['hakkimda'] ?? '';

// Adı ve Hakkımda metnini güncelle
if (isset($_POST['name']) || isset($_POST['hakkimda'])) { // GÜNCELLENDİ: Boş bile olsa güncelle
    $stmt_update = $conn->prepare("UPDATE users SET name = ?, hakkimda = ? WHERE id = ?");
    $stmt_update->bind_param("ssi", $name, $hakkimda, $user_id);
    if ($stmt_update->execute()) {
        $_SESSION['name'] = $name; // Session'daki adı da güncelle
        $response['message'] = 'Bilgileriniz başarıyla güncellendi.';
        $updated = true;
    }
    $stmt_update->close();
}

// === PROFİL FOTOĞRAFI İŞLEME BLOĞU (GÜNCELLENDİ: WebP) ===
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    
    $target_dir = "assets/avatars/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file_info = $_FILES['profile_image'];
    $file_tmp_path = $file_info['tmp_name'];
    $file_type = $file_info['type'];
    $file_size = $file_info['size'];
    
    $file_extension = strtolower(pathinfo($file_info["name"], PATHINFO_EXTENSION));
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types) || $file_size > 2000000) { // 2MB limit
        $response['message'] = 'Sadece 2MB\'dan küçük JPG, PNG, GIF dosyaları yüklenebilir.';
        echo json_encode($response);
        exit;
    }

    $stmt_old_img = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt_old_img->bind_param("i", $user_id);
    $stmt_old_img->execute();
    $old_image_result = $stmt_old_img->get_result()->fetch_assoc();
    $old_image_path = $old_image_result['profile_image'] ?? null;
    $stmt_old_img->close();

    $image_resource = null;
    if ($file_extension === 'jpg' || $file_extension === 'jpeg') {
        $image_resource = imagecreatefromjpeg($file_tmp_path);
    } elseif ($file_extension === 'png') {
        $image_resource = imagecreatefrompng($file_tmp_path);
    } elseif ($file_extension === 'gif') {
        $image_resource = imagecreatefromgif($file_tmp_path);
    }
    
    $new_file_name = $user_id . '_' . time() . '.webp';
    $target_file = $target_dir . $new_file_name;

    if ($image_resource !== null) {
        if (imagewebp($image_resource, $target_file, 80)) {
            imagedestroy($image_resource); 

            $stmt_img = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt_img->bind_param("si", $target_file, $user_id);
            
            if ($stmt_img->execute()) {
                if ($old_image_path && file_exists($old_image_path) && strpos($old_image_path, 'default.png') === false) {
                    unlink($old_image_path);
                }
                $response['message'] = 'Profil fotoğrafınız başarıyla güncellendi.';
                $response['new_image_url'] = $target_file;
                $updated = true;
            }
            $stmt_img->close();
            
        } else {
            $response['message'] = 'Fotoğraf WebP formatına dönüştürülürken bir hata oluştu.';
        }
    } else {
        $response['message'] = 'Desteklenmeyen resim formatı.';
    }
}

$response['success'] = $updated;
echo json_encode($response);
$conn->close();
?>