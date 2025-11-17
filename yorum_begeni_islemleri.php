<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

// 1. KULLANICI GİRİŞ KONTROLÜ
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

$tetikleyici_user_id = $_SESSION['id'];
$yorum_id = isset($_POST['yorum_id']) ? (int)$_POST['yorum_id'] : 0;

if ($yorum_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz yorum kimliği.']);
    exit;
}

$conn->begin_transaction();

try {
    // 2. KULLANICININ MEVCUT BEĞENİSİNİ KONTROL ET
    $stmt_check = $conn->prepare("SELECT id FROM yorum_begeni WHERE user_id = ? AND yorum_id = ?");
    $stmt_check->bind_param("ii", $tetikleyici_user_id, $yorum_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // 3a. BEĞENİ VARSA, GERİ AL (SİL)
        $stmt_delete = $conn->prepare("DELETE FROM yorum_begeni WHERE user_id = ? AND yorum_id = ?");
        $stmt_delete->bind_param("ii", $tetikleyici_user_id, $yorum_id);
        $stmt_delete->execute();
        $action = 'unliked';
    } else {
        // 3b. BEĞENİ YOKSA, YENİ BEĞENİ EKLE
        $stmt_insert = $conn->prepare("INSERT INTO yorum_begeni (user_id, yorum_id) VALUES (?, ?)");
        $stmt_insert->bind_param("ii", $tetikleyici_user_id, $yorum_id);
        $stmt_insert->execute();
        $action = 'liked';

        // 4. BİLDİRİM OLUŞTUR
        // Yorumun sahibini bul
        $stmt_yorum_sahibi = $conn->prepare("SELECT user_id FROM yorumlar WHERE id = ?");
        $stmt_yorum_sahibi->bind_param("i", $yorum_id);
        $stmt_yorum_sahibi->execute();
        $yorum_sahibi_result = $stmt_yorum_sahibi->get_result()->fetch_assoc();

        if ($yorum_sahibi_result) {
            $yorum_sahibi_id = $yorum_sahibi_result['user_id'];
            // Kişi kendi yorumunu beğenmiyorsa bildirim gönder
            if ($yorum_sahibi_id != $tetikleyici_user_id) {
                $bildirim_tipi = 'yorum_begeni';
                // GÜNCELLENDİ: Bildirim tekrarı önleme
                // Önce aynı bildirimin "okunmamış" olarak var olup olmadığını kontrol et
                $stmt_check_notify = $conn->prepare("SELECT id FROM bildirimler WHERE user_id = ? AND tetikleyici_user_id = ? AND bildirim_tipi = ? AND hedef_id = ? AND okundu_mu = 0");
                $stmt_check_notify->bind_param("iisi", $yorum_sahibi_id, $tetikleyici_user_id, $bildirim_tipi, $yorum_id);
                $stmt_check_notify->execute();
                if ($stmt_check_notify->get_result()->num_rows == 0) {
                    // Sadece okunmamış bildirim yoksa yenisini ekle
                    $stmt_bildirim = $conn->prepare("INSERT INTO bildirimler (user_id, tetikleyici_user_id, bildirim_tipi, hedef_id) VALUES (?, ?, ?, ?)");
                    $stmt_bildirim->bind_param("iisi", $yorum_sahibi_id, $tetikleyici_user_id, $bildirim_tipi, $yorum_id);
                    $stmt_bildirim->execute();
                }
            }
        }
    }

    // 5. YENİ BEĞENİ SAYISINI GÜVENLİ BİR ŞEKİLDE AL
    $stmt_count = $conn->prepare("SELECT COUNT(id) as total_likes FROM yorum_begeni WHERE yorum_id = ?");
    $stmt_count->bind_param("i", $yorum_id);
    $stmt_count->execute();
    $total_likes = $stmt_count->get_result()->fetch_assoc()['total_likes'];

    // Her şey yolundaysa işlemi onayla
    $conn->commit();
    echo json_encode(['success' => true, 'action' => $action, 'total_likes' => $total_likes]);

} catch (Exception $e) {
    // Herhangi bir hata olursa tüm işlemleri geri al
    $conn->rollback();
    http_response_code(500);
    // Hata mesajını loglamak daha iyidir, ancak geliştirme aşamasında görmek için:
    echo json_encode(['success' => false, 'message' => 'Bir veritabanı hatası oluştu: ' . $e->getMessage()]);
}

$conn->close();
?>