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

// === CSRF KONTROLÜ ===
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

$pdo->beginTransaction();

try {
    // 2. KULLANICININ MEVCUT BEĞENİSİNİ KONTROL ET
    $stmt_check = $pdo->prepare("SELECT id FROM yorum_begeni WHERE user_id = ? AND yorum_id = ?");
    $stmt_check->execute([$tetikleyici_user_id, $yorum_id]);
    $mevcut_begeni = $stmt_check->fetch();

    if ($mevcut_begeni) {
        // 3a. BEĞENİ VARSA, GERİ AL (SİL)
        $stmt_delete = $pdo->prepare("DELETE FROM yorum_begeni WHERE user_id = ? AND yorum_id = ?");
        $stmt_delete->execute([$tetikleyici_user_id, $yorum_id]);
        $action = 'unliked';
    } else {
        // 3b. BEĞENİ YOKSA, YENİ BEĞENİ EKLE
        $stmt_insert = $pdo->prepare("INSERT INTO yorum_begeni (user_id, yorum_id) VALUES (?, ?)");
        $stmt_insert->execute([$tetikleyici_user_id, $yorum_id]);
        $action = 'liked';

        // 4. BİLDİRİM OLUŞTUR
        // Yorumun sahibini bul
        $stmt_yorum_sahibi = $pdo->prepare("SELECT user_id FROM yorumlar WHERE id = ?");
        $stmt_yorum_sahibi->execute([$yorum_id]);
        $yorum_sahibi_result = $stmt_yorum_sahibi->fetch();

        if ($yorum_sahibi_result) {
            $yorum_sahibi_id = $yorum_sahibi_result['user_id'];
            // Kişi kendi yorumunu beğenmiyorsa bildirim gönder
            if ((int)$yorum_sahibi_id !== (int)$tetikleyici_user_id) {
                $bildirim_tipi = 'yorum_begeni';
                // Bildirim tekrarı önleme: aynı bildirimin "okunmamış" olarak var olup olmadığını kontrol et
                $stmt_check_notify = $pdo->prepare("SELECT id FROM bildirimler WHERE user_id = ? AND tetikleyici_user_id = ? AND bildirim_tipi = ? AND hedef_id = ? AND okundu_mu = 0");
                $stmt_check_notify->execute([$yorum_sahibi_id, $tetikleyici_user_id, $bildirim_tipi, $yorum_id]);
                if ($stmt_check_notify->rowCount() === 0) {
                    // Sadece okunmamış bildirim yoksa yenisini ekle
                    $stmt_bildirim = $pdo->prepare("INSERT INTO bildirimler (user_id, tetikleyici_user_id, bildirim_tipi, hedef_id) VALUES (?, ?, ?, ?)");
                    $stmt_bildirim->execute([$yorum_sahibi_id, $tetikleyici_user_id, $bildirim_tipi, $yorum_id]);
                }
            }
        }
    }

    // 5. YENİ BEĞENİ SAYISINI GÜVENLİ BİR ŞEKİLDE AL
    $stmt_count = $pdo->prepare("SELECT COUNT(id) FROM yorum_begeni WHERE yorum_id = ?");
    $stmt_count->execute([$yorum_id]);
    $total_likes = $stmt_count->fetchColumn();

    // Her şey yolundaysa işlemi onayla
    $pdo->commit();
    echo json_encode(['success' => true, 'action' => $action, 'total_likes' => $total_likes]);

} catch (Exception $e) {
    // Herhangi bir hata olursa tüm işlemleri geri al
    $pdo->rollBack();
    error_log('yorum_begeni_islemleri hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir veritabanı hatası oluştu.']);
}
?>
