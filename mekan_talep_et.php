<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['id'];
$mekan_id = $_POST['mekan_id'] ?? 0;

if (empty($mekan_id)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz mekan kimliği.']);
    exit;
}

try {
    // Kullanıcının rolünü kontrol et (bind_result Düzeltmesi)
    $stmt_role = $conn->prepare("SELECT rol FROM users WHERE id = ?");
    $stmt_role->bind_param("i", $user_id);
    $stmt_role->execute();
    $stmt_role->bind_result($rol);
    $stmt_role->fetch();
    $stmt_role->close();
    
    if (!$rol || ($rol !== 'mekan_sahibi' && $rol !== 'premium_mekan')) {
         http_response_code(403);
         echo json_encode(['success' => false, 'message' => 'Sadece işletme hesapları mekan sahipliği talep edebilir.']);
         exit;
    }

    // Mekanın zaten bir sahibi var mı diye kontrol et (bind_result Düzeltmesi)
    $stmt_check = $conn->prepare("SELECT owner_id FROM mekanlar WHERE id = ?");
    $stmt_check->bind_param("i", $mekan_id);
    $stmt_check->execute();
    $stmt_check->bind_result($owner_id);
    $stmt_check->fetch();
    $stmt_check->close();
    
    if (!empty($owner_id)) {
        echo json_encode(['success' => false, 'message' => 'Bu mekanın zaten bir sahibi var.']);
        exit;
    }

    // Kullanıcının bu mekan için zaten beklemede olan bir talebi var mı? (bind_result Düzeltmesi)
    $stmt_exist = $conn->prepare("SELECT id FROM mekan_sahiplik_talepleri WHERE user_id = ? AND mekan_id = ? AND durum = 'beklemede'");
    $stmt_exist->bind_param("ii", $user_id, $mekan_id);
    $stmt_exist->execute();
    $stmt_exist->store_result();
    if ($stmt_exist->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu mekan için zaten beklemede olan bir talebiniz var.']);
        $stmt_exist->close();
        exit;
    }
    $stmt_exist->close();

    // Yeni talep oluştur
    $stmt_insert = $conn->prepare("INSERT INTO mekan_sahiplik_talepleri (user_id, mekan_id) VALUES (?, ?)");
    $stmt_insert->bind_param("ii", $user_id, $mekan_id);
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sahiplik talebiniz başarıyla alınmıştır. Admin onayından sonra mekan panelinize eklenecektir.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Talep oluşturulurken bir hata oluştu.']);
    }
    $stmt_insert->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}

$conn->close();
?>