<?php
session_start();
require 'db_config.php';
require 'send_email.php';

header('Content-Type: application/json');

// CSRF kontrolü
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

$rota_id = $_POST['rota_id'] ?? '';
$yorum_metni = $_POST['yorumMetni'] ?? '';
$puan = $_POST['puan'] ?? 0;
$user_id = $_SESSION['id'];
$user_name = $_SESSION['name'];
$parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
$yorum_tipi = $_POST['yorum_tipi'] ?? 'yorum';

// Doğrulama
if (empty($rota_id) || empty($yorum_metni) || !in_array($yorum_tipi, ['yorum', 'soru', 'cevap'])) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
    exit;
}
if ($yorum_tipi === 'yorum' && $parent_id === null && ($puan < 1 || $puan > 5)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen geçerli bir puan seçin.']);
    exit;
}

$pdo->beginTransaction();

try {
    // Eğer parent_id varsa, üst yorumun tipini kontrol et
    if ($parent_id !== null) {
        $stmt_check_parent = $pdo->prepare("SELECT yorum_tipi FROM yorumlar WHERE id = ?");
        $stmt_check_parent->execute([$parent_id]);
        $parent_result = $stmt_check_parent->fetch();

        if ($parent_result && $parent_result['yorum_tipi'] === 'soru') {
            $yorum_tipi = 'cevap';
        }
    }

    $yorum_puani = ($yorum_tipi === 'yorum' && $parent_id === null) ? $puan : 0;

    // Yorumu ekle
    $stmt = $pdo->prepare("INSERT INTO yorumlar (rota_id, user_id, yorum_metni, puan, parent_id, yorum_tipi) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$rota_id, $user_id, $yorum_metni, $yorum_puani, $parent_id, $yorum_tipi]);
    $son_yorum_id = $pdo->lastInsertId();

    // @bahsetme ve bildirim mantığı
    preg_match_all('/@(\w+)/', $yorum_metni, $matches);
    if (!empty($matches[1])) {
        // ... (mevcut @bahsetme bildirim kodunuz burada kalacak) ...
    }

    // Yanıt bildirimi ve e-posta gönderimi
    if ($parent_id !== null) {
        $stmt_info = $pdo->prepare("
            SELECT
                u.email AS parent_email,
                u.name AS parent_name,
                r.ad AS rota_adi
            FROM yorumlar y
            JOIN users u ON y.user_id = u.id
            JOIN rotalar r ON y.rota_id = r.id
            WHERE y.id = ? AND y.user_id != ?
        ");
        $stmt_info->execute([$parent_id, $user_id]);
        $info = $stmt_info->fetch();

        if ($info) {
            $toEmail = $info['parent_email'];
            $toName = $info['parent_name'];
            $subject = "Yorumunuza bir yanıt geldi!";
            $guvenli_yorum = htmlspecialchars($yorum_metni, ENT_QUOTES, 'UTF-8');
            $body = "
                Merhaba {$toName},<br><br>
                <b>{$info['rota_adi']}</b> rotasındaki yorumunuza <b>{$user_name}</b> tarafından bir yanıt yazıldı:<br>
                <hr>
                <p><i>\"{$guvenli_yorum}\"</i></p>
                <hr>
                <p>Yanıtı görmek için <a href='https://www.seckinrotalar.com/rota-detay.html?id={$rota_id}#yorum-{$son_yorum_id}'>buraya tıklayın</a>.</p>
                <br>
                Seçkin Rotalar Ekibi
            ";

            sendEmail($toEmail, $toName, $subject, $body, true);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Mesajınız başarıyla eklendi.']);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('yorum_kaydet hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir veritabanı hatası oluştu.']);
}
?>
