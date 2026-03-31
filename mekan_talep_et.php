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
$mekan_id = (int)($_POST['mekan_id'] ?? 0);

if (empty($mekan_id)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz mekan kimliği.']);
    exit;
}

try {
    // Kullanıcının rolünü kontrol et
    $stmt_role = $pdo->prepare("SELECT rol FROM users WHERE id = ?");
    $stmt_role->execute([$user_id]);
    $row = $stmt_role->fetch();
    $rol = $row['rol'] ?? null;

    if (!$rol || !in_array($rol, ['mekan_sahibi', 'premium_mekan'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sadece işletme hesapları mekan sahipliği talep edebilir.']);
        exit;
    }

    // Mekanın zaten bir sahibi var mı?
    $stmt_check = $pdo->prepare("SELECT owner_id FROM mekanlar WHERE id = ?");
    $stmt_check->execute([$mekan_id]);
    $mekan = $stmt_check->fetch();

    if (!empty($mekan['owner_id'])) {
        echo json_encode(['success' => false, 'message' => 'Bu mekanın zaten bir sahibi var.']);
        exit;
    }

    // Beklemede olan talep var mı?
    $stmt_exist = $pdo->prepare("SELECT id FROM mekan_sahiplik_talepleri WHERE user_id = ? AND mekan_id = ? AND durum = 'beklemede'");
    $stmt_exist->execute([$user_id, $mekan_id]);
    if ($stmt_exist->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu mekan için zaten beklemede olan bir talebiniz var.']);
        exit;
    }

    // Yeni talep oluştur
    $stmt_insert = $pdo->prepare("INSERT INTO mekan_sahiplik_talepleri (user_id, mekan_id) VALUES (?, ?)");
    $stmt_insert->execute([$user_id, $mekan_id]);
    echo json_encode(['success' => true, 'message' => 'Sahiplik talebiniz başarıyla alınmıştır.']);

} catch (PDOException $e) {
    error_log('mekan_talep_et hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}
?>
