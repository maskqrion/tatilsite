<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

// Kullanıcı giriş yapmamışsa işlemi sonlandır
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

// === YENİ EKLENDİ: CSRF KONTROLÜ ===
// Bu dosya şu anda kullanılmıyor gibi görünse de, gelecekteki kullanıma karşı güvenli hale getirildi.
if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}
// === GÜNCELLEME SONU ===

$user_id = $_SESSION['id'];
$liste_id = $_POST['liste_id'] ?? 0;
$item_type = $_POST['item_type'] ?? ''; // 'rota' veya 'mekan'
$item_id = $_POST['item_id'] ?? '';

// Gelen verileri doğrula
if (empty($liste_id) || empty($item_type) || empty($item_id) || !in_array($item_type, ['rota', 'mekan', 'blog'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik veya geçersiz veri gönderildi.']);
    exit;
}

try {
    // GÜVENLİK ADIMI: Kullanıcının, eleman eklemek istediği listenin sahibi olup olmadığını kontrol et
    $stmt_check_owner = $conn->prepare("SELECT id FROM kullanici_listeleri WHERE id = ? AND user_id = ?");
    $stmt_check_owner->bind_param("ii", $liste_id, $user_id);
    $stmt_check_owner->execute();
    $result_check_owner = $stmt_check_owner->get_result();
    if ($result_check_owner->num_rows === 0) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Bu listeye eleman ekleme yetkiniz yok.']);
        exit;
    }
    $stmt_check_owner->close();

    // Mevcut elemanın listede olup olmadığını kontrol et
    $stmt_check_exist = $conn->prepare("SELECT id FROM kullanici_liste_elemanlari WHERE liste_id = ? AND item_type = ? AND item_id = ?");
    $stmt_check_exist->bind_param("iss", $liste_id, $item_type, $item_id);
    $stmt_check_exist->execute();
    $result_check_exist = $stmt_check_exist->get_result();
    if ($result_check_exist->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu eleman zaten listenizde mevcut.']);
        exit;
    }
     $stmt_check_exist->close();


    // Veritabanına yeni elemanı ekle
    $stmt = $conn->prepare("INSERT INTO kullanici_liste_elemanlari (liste_id, item_type, item_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $liste_id, $item_type, $item_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Eleman listenize başarıyla eklendi!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Eleman eklenirken bir veritabanı hatası oluştu.']);
    }
    $stmt->close();

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}

$conn->close();
?>