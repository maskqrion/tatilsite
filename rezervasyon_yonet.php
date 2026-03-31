<?php
session_start();
require 'db_config.php';
require 'sende-email.php';
header('Content-Type: application/json; charset=utf-8');

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

// Rol kontrolü
$stmt_role = $pdo->prepare("SELECT rol FROM users WHERE id = ?");
$stmt_role->execute([$user_id]);
$row = $stmt_role->fetch();
$rol = $row['rol'] ?? null;

if (!$rol || !in_array($rol, ['mekan_sahibi', 'premium_mekan', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.']);
    exit;
}

$rezervasyon_id = (int)($_POST['rezervasyon_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (empty($rezervasyon_id) || !in_array($action, ['onayla', 'reddet'], true)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

try {
    $stmt_check = $pdo->prepare("
        SELECT r.id, r.ad_soyad, r.email, e.etkinlik_adi, m.ad AS mekan_adi
        FROM rezervasyonlar r
        JOIN etkinlikler e ON r.etkinlik_id = e.id
        JOIN mekanlar m ON e.mekan_id = m.id
        WHERE r.id = ? AND m.owner_id = ?
    ");
    $stmt_check->execute([$rezervasyon_id, $user_id]);
    $info = $stmt_check->fetch();

    if (!$info) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.']);
        exit;
    }

    $yeni_durum = ($action === 'onayla') ? 'onaylandı' : 'reddedildi';
    $stmt_update = $pdo->prepare("UPDATE rezervasyonlar SET durum = ? WHERE id = ?");
    $stmt_update->execute([$yeni_durum, $rezervasyon_id]);

    $safe_ad_soyad = htmlspecialchars($info['ad_soyad'], ENT_QUOTES, 'UTF-8');
    $safe_mekan_adi = htmlspecialchars($info['mekan_adi'], ENT_QUOTES, 'UTF-8');
    $safe_etkinlik_adi = htmlspecialchars($info['etkinlik_adi'], ENT_QUOTES, 'UTF-8');

    $email_subject = "Rezervasyon Talebiniz Güncellendi: {$info['etkinlik_adi']}";
    if ($yeni_durum === 'onaylandı') {
        $email_body = "Merhaba {$safe_ad_soyad},<br><br><b>{$safe_mekan_adi}</b> mekanındaki '<b>{$safe_etkinlik_adi}</b>' etkinliği için yaptığınız rezervasyon talebi <b>ONAYLANMIŞTIR</b>.<br><br>İyi eğlenceler dileriz!<br>Seçkin Rotalar Ekibi";
    } else {
        $email_body = "Merhaba {$safe_ad_soyad},<br><br><b>{$safe_mekan_adi}</b> mekanındaki '<b>{$safe_etkinlik_adi}</b>' etkinliği için yaptığınız rezervasyon talebi maalesef <b>REDDEDİLMİŞTİR</b>.<br><br>Anlayışınız için teşekkür ederiz.<br>Seçkin Rotalar Ekibi";
    }

    sendEmail($info['email'], $info['ad_soyad'], $email_subject, $email_body, true);
    echo json_encode(['success' => true, 'message' => 'Rezervasyon durumu başarıyla güncellendi.']);

} catch (PDOException $e) {
    error_log('rezervasyon_yonet hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu tarafında bir hata oluştu.']);
}
?>
