<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

// === CSRF KONTROLÜ ===
if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['id'];
$pdo = getDB();
$response = ['success' => false, 'message' => 'Herhangi bir değişiklik yapılmadı.'];
$updated = false;

// Formdan gelen adı ve hakkımda metnini al
$name = $_POST['name'] ?? '';
$hakkimda = $_POST['hakkimda'] ?? '';

try {
    // Adı ve Hakkımda metnini güncelle
    if (isset($_POST['name']) || isset($_POST['hakkimda'])) {
        $stmt_update = $pdo->prepare("UPDATE users SET name = ?, hakkimda = ? WHERE id = ?");
        $stmt_update->execute([$name, $hakkimda, $user_id]);
        $_SESSION['name'] = $name; // Session'daki adı da güncelle
        $response['message'] = 'Bilgileriniz başarıyla güncellendi.';
        $updated = true;
    }

    // === PROFİL FOTOĞRAFI İŞLEME BLOĞU (WebP) ===
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {

        $target_dir = "assets/avatars/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $file_info = $_FILES['profile_image'];
        $file_tmp_path = $file_info['tmp_name'];
        $file_size = $file_info['size'];

        $file_extension = strtolower(pathinfo($file_info["name"], PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types, true) || $file_size > 2000000) { // 2MB limit
            $response['message'] = 'Sadece 2MB\'dan küçük JPG, PNG, GIF dosyaları yüklenebilir.';
            echo json_encode($response);
            exit;
        }

        // MIME doğrulaması (getimagesize ile)
        $finfo = getimagesize($file_tmp_path);
        if (!$finfo || !in_array($finfo['mime'], ['image/jpeg', 'image/png', 'image/gif'], true)) {
            $response['message'] = 'Geçersiz resim dosyası. Sadece JPG, PNG, GIF desteklenir.';
            echo json_encode($response);
            exit;
        }

        // Eski profil fotoğrafını bul
        $stmt_old_img = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt_old_img->execute([$user_id]);
        $old_image_row = $stmt_old_img->fetch();
        $old_image_path = $old_image_row['profile_image'] ?? null;

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

                $stmt_img = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt_img->execute([$target_file, $user_id]);

                if ($old_image_path && file_exists($old_image_path) && strpos($old_image_path, 'default.png') === false) {
                    unlink($old_image_path);
                }
                $response['message'] = 'Profil fotoğrafınız başarıyla güncellendi.';
                $response['new_image_url'] = $target_file;
                $updated = true;
            } else {
                $response['message'] = 'Fotoğraf WebP formatına dönüştürülürken bir hata oluştu.';
            }
        } else {
            $response['message'] = 'Desteklenmeyen resim formatı.';
        }
    }

    $response['success'] = $updated;
    echo json_encode($response);

} catch (PDOException $e) {
    error_log('profil_guncelle.php hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Profil güncellenirken bir hata oluştu.']);
}
?>
