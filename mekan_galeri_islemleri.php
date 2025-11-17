<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); exit;
}

$user_id = $_SESSION['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$mekan_id = $_POST['mekan_id'] ?? $_GET['mekan_id'] ?? 0;

// Güvenlik: Kullanıcının bu mekanı düzenleme yetkisi var mı? (bind_result Düzeltmesi)
$stmt_check = $conn->prepare("SELECT owner_id FROM mekanlar WHERE id = ?");
$stmt_check->bind_param("i", $mekan_id);
$stmt_check->execute();
$stmt_check->bind_result($owner_id);
$stmt_check->fetch();
$stmt_check->close();

if (($owner_id ?? null) != $user_id) {
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
    if (!in_array($file_extension, $allowed_types) || $file_info["size"] > 5000000) { // 5MB limit
        echo json_encode(['success' => false, 'message' => 'Hata: Sadece 5MB\'dan küçük JPG, PNG, GIF dosyaları yüklenebilir.']);
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

        $stmt = $conn->prepare("INSERT INTO mekan_galerisi (mekan_id, resim_url) VALUES (?, ?)");
        $stmt->bind_param("is", $mekan_id, $target_file);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Resim yüklendi.', 'file_path' => $target_file, 'id' => $conn->insert_id]);
        } else {
            unlink($target_file);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
        }
    } else {
        imagedestroy($image_resource);
        echo json_encode(['success' => false, 'message' => 'Dosya optimize edilemedi.']);
    }
}

if ($action === 'delete') {
    $resim_id = $_POST['resim_id'] ?? 0;
    // Önce dosya yolunu al ve sil (bind_result Düzeltmesi)
    $stmt_get = $conn->prepare("SELECT resim_url FROM mekan_galerisi WHERE id = ? AND mekan_id = ?");
    $stmt_get->bind_param("ii", $resim_id, $mekan_id);
    $stmt_get->execute();
    $stmt_get->bind_result($resim_url);
    
    if($stmt_get->fetch()) {
        $stmt_get->close(); // fetch() sonrası hemen kapat
        if (file_exists($resim_url)) {
            unlink($resim_url);
        }
        
        // Sonra veritabanından kaydı sil
        $stmt_del = $conn->prepare("DELETE FROM mekan_galerisi WHERE id = ?");
        $stmt_del->bind_param("i", $resim_id);
        if($stmt_del->execute()){
             echo json_encode(['success' => true, 'message' => 'Resim silindi.']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Resim silinemedi.']);
        }
        $stmt_del->close();
    } else {
        $stmt_get->close();
        echo json_encode(['success' => false, 'message' => 'Silinecek resim bulunamadı veya yetkiniz yok.']);
    }
}

$conn->close();
?>