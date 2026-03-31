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
$liste_id = (int)($_POST['liste_id'] ?? 0);
$item_type = $_POST['item_type'] ?? '';
$item_id = $_POST['item_id'] ?? '';

if (empty($liste_id) || empty($item_type) || empty($item_id) || !in_array($item_type, ['rota', 'mekan', 'blog'], true)) {
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz veri gönderildi.']);
    exit;
}

try {
    // Listenin sahibi mi?
    $stmt_check_owner = $pdo->prepare("SELECT id FROM kullanici_listeleri WHERE id = ? AND user_id = ?");
    $stmt_check_owner->execute([$liste_id, $user_id]);
    if (!$stmt_check_owner->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu listeye eleman ekleme yetkiniz yok.']);
        exit;
    }

    // Eleman zaten var mı?
    $stmt_check_exist = $pdo->prepare("SELECT id FROM kullanici_liste_elemanlari WHERE liste_id = ? AND item_type = ? AND item_id = ?");
    $stmt_check_exist->execute([$liste_id, $item_type, $item_id]);
    if ($stmt_check_exist->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu eleman zaten listenizde mevcut.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO kullanici_liste_elemanlari (liste_id, item_type, item_id) VALUES (?, ?, ?)");
    $stmt->execute([$liste_id, $item_type, $item_id]);
    echo json_encode(['success' => true, 'message' => 'Eleman listenize başarıyla eklendi!']);

} catch (PDOException $e) {
    error_log('liste_eleman_ekle hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}
?>
