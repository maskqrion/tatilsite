<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

// CSRF kontrolü
if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$mekan_id = (int)($_POST['mekan_id'] ?? $_GET['mekan_id'] ?? 0);

// Güvenlik: Kullanıcının bu mekanı düzenleme yetkisi var mı?
$stmt_check = $pdo->prepare("SELECT owner_id FROM mekanlar WHERE id = ?");
$stmt_check->execute([$mekan_id]);
$row = $stmt_check->fetch();

if (!$row || (int)($row['owner_id'] ?? 0) !== (int)$user_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu mekanı düzenleme yetkiniz yok.']);
    exit;
}

if ($action === 'upload' && isset($_FILES['galeri_resim'])) {
    $target_dir = "assets/mekanlar/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }

    $file_info = $_FILES['galeri_resim'];
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

    $new_file_name = $mekan_id . '_' . time() . '.webp';
    $target_file = $target_dir . $new_file_name;

    if (imagewebp($image_resource, $target_file, 80)) {
        imagedestroy($image_resource);
        try {
            $stmt = $pdo->prepare("INSERT INTO mekan_galerisi (mekan_id, resim_url) VALUES (?, ?)");
            $stmt->execute([$mekan_id, $target_file]);
            echo json_encode(['success' => true, 'message' => 'Resim yüklendi.', 'file_path' => $target_file, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            unlink($target_file);
            error_log('mekan_galeri upload hatası: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
        }
    } else {
        imagedestroy($image_resource);
        echo json_encode(['success' => false, 'message' => 'Dosya optimize edilemedi.']);
    }
}

if ($action === 'delete') {
    $resim_id = (int)($_POST['resim_id'] ?? 0);
    $stmt_get = $pdo->prepare("SELECT resim_url FROM mekan_galerisi WHERE id = ? AND mekan_id = ?");
    $stmt_get->execute([$resim_id, $mekan_id]);
    $resim = $stmt_get->fetch();

    if ($resim) {
        if (file_exists($resim['resim_url'])) {
            unlink($resim['resim_url']);
        }
        $stmt_del = $pdo->prepare("DELETE FROM mekan_galerisi WHERE id = ?");
        $stmt_del->execute([$resim_id]);
        echo json_encode(['success' => true, 'message' => 'Resim silindi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Silinecek resim bulunamadı veya yetkiniz yok.']);
    }
}
?>
