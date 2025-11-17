<?php
session_start();
require 'db_config.php';
require 'sende-email.php'; 
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); exit;
}

if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['id'];
// bind_result Düzeltmesi
$stmt_role = $conn->prepare("SELECT rol FROM users WHERE id = ?");
$stmt_role->bind_param("i", $user_id);
$stmt_role->execute();
$stmt_role->bind_result($rol);
$stmt_role->fetch();
$stmt_role->close();

if (!$rol || ($rol !== 'mekan_sahibi' && $rol !== 'premium_mekan' && $rol !== 'admin')) {
    http_response_code(403); exit;
}

$rezervasyon_id = $_POST['rezervasyon_id'] ?? 0;
$action = $_POST['action'] ?? '';

if (empty($rezervasyon_id) || !in_array($action, ['onayla', 'reddet'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

try {
    // bind_result Düzeltmesi
    $stmt_check = $conn->prepare("
        SELECT r.id, r.ad_soyad, r.email, e.etkinlik_adi, m.ad AS mekan_adi
        FROM rezervasyonlar r 
        JOIN etkinlikler e ON r.etkinlik_id = e.id 
        JOIN mekanlar m ON e.mekan_id = m.id 
        WHERE r.id = ? AND m.owner_id = ?
    ");
    $stmt_check->bind_param("ii", $rezervasyon_id, $user_id);
    $stmt_check->execute();
    $stmt_check->bind_result($id, $ad_soyad, $email, $etkinlik_adi, $mekan_adi);
    
    $info = null;
    if ($stmt_check->fetch()) {
        $info = [
            'id' => $id,
            'ad_soyad' => $ad_soyad,
            'email' => $email,
            'etkinlik_adi' => $etkinlik_adi,
            'mekan_adi' => $mekan_adi
        ];
    }
    $stmt_check->close();

    if (!$info) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.']);
        exit;
    }

    $yeni_durum = ($action === 'onayla') ? 'onaylandı' : 'reddedildi';
    $stmt_update = $conn->prepare("UPDATE rezervasyonlar SET durum = ? WHERE id = ?");
    $stmt_update->bind_param("si", $yeni_durum, $rezervasyon_id);
    
    if ($stmt_update->execute()) {
        
        $email_subject = "Rezervasyon Talebiniz Güncellendi: {$info['etkinlik_adi']}";
        $email_body = "";
        
        if ($yeni_durum === 'onaylandı') {
            $email_body = "Merhaba {$info['ad_soyad']},<br><br><b>{$info['mekan_adi']}</b> mekanındaki '<b>{$info['etkinlik_adi']}</b>' etkinliği için yaptığınız rezervasyon talebi <b>ONAYLANMIŞTIR</b>.<br><br>İyi eğlenceler dileriz!<br>Seçkin Rotalar Ekibi";
        } else {
             $email_body = "Merhaba {$info['ad_soyad']},<br><br><b>{$info['mekan_adi']}</b> mekanındaki '<b>{$info['etkinlik_adi']}</b>' etkinliği için yaptığınız rezervasyon talebi maalesef <b>REDDEDİLMİŞTİR</b>.<br><br>Anlayışınız için teşekkür ederiz.<br>Seçkin Rotalar Ekibi";
        }
        
        sendEmail($info['email'], $info['ad_soyad'], $email_subject, $email_body, true);

        echo json_encode(['success' => true, 'message' => 'Rezervasyon durumu başarıyla güncellendi ve kullanıcıya e-posta gönderildi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Durum güncellenirken bir hata oluştu.']);
    }
    $stmt_update->close();

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}

$conn->close();
?>