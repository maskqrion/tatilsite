<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu sayfayı görmek için giriş yapmalısınız.']);
    exit;
}

$user_id = $_SESSION['id'];
$pdo = getDB();
$response = ['success' => true, 'data' => []];

try {
    // 1. Kullanıcının Temel Bilgilerini Al
    $stmt = $pdo->prepare("SELECT name, email, created_at, profile_image, hakkimda FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_row = $stmt->fetch();

    if ($user_row === false) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bilgileri alınamadı.']);
        exit;
    }

    $response['data']['user'] = [
        'name' => htmlspecialchars($user_row['name'], ENT_QUOTES, 'UTF-8'),
        'email' => htmlspecialchars($user_row['email'], ENT_QUOTES, 'UTF-8'),
        'created_at' => date("d F Y", strtotime($user_row['created_at'])),
        'profile_image' => $user_row['profile_image'],
        'hakkimda' => htmlspecialchars($user_row['hakkimda'] ?? '', ENT_QUOTES, 'UTF-8')
    ];

    // 2. İstatistikleri ve Puanları Hesapla
    $stmt_stats = $pdo->prepare("
        SELECT
            (SELECT COUNT(id) FROM yorumlar WHERE user_id = ?) as toplam_yorum,
            (SELECT COUNT(id) FROM gezi_planlari WHERE user_id = ?) as toplam_plan,
            (SELECT COUNT(id) FROM kullanici_listeleri WHERE user_id = ? AND herkese_acik = 1) as toplam_liste,
            (SELECT COUNT(b.id) FROM yorum_begeni b JOIN yorumlar y ON b.yorum_id = y.id WHERE y.user_id = ? AND y.user_id != b.user_id) as toplam_begeni
    ");
    $stmt_stats->execute([$user_id, $user_id, $user_id, $user_id]);
    $stats = $stmt_stats->fetch();

    $yorum_sayisi = (int)$stats['toplam_yorum'];
    $plan_sayisi = (int)$stats['toplam_plan'];
    $liste_sayisi = (int)$stats['toplam_liste'];
    $begeni_sayisi = (int)$stats['toplam_begeni'];

    $response['data']['stats']['toplam_yorum'] = $yorum_sayisi;
    $response['data']['stats']['toplam_plan'] = $plan_sayisi;

    $toplam_puan = ($yorum_sayisi * 10) + ($liste_sayisi * 25) + ($begeni_sayisi * 2);
    $response['data']['stats']['toplam_puan'] = $toplam_puan;

    // 3. Yorum Geçmişini Al
    $stmt_yorumlar = $pdo->prepare("
        SELECT y.yorum_metni, y.tarih, y.rota_id, r.ad AS rota_adi
        FROM yorumlar y JOIN rotalar r ON y.rota_id = r.id
        WHERE y.user_id = ? ORDER BY y.tarih DESC LIMIT 5
    ");
    $stmt_yorumlar->execute([$user_id]);
    $yorumlar = [];
    while ($row = $stmt_yorumlar->fetch()) {
        $yorumlar[] = [
            'yorum_metni' => htmlspecialchars($row['yorum_metni'], ENT_QUOTES, 'UTF-8'),
            'tarih' => date("d F Y", strtotime($row['tarih'])),
            'rota_id' => $row['rota_id'],
            'rota_adi' => htmlspecialchars($row['rota_adi'], ENT_QUOTES, 'UTF-8')
        ];
    }
    $response['data']['yorumlar'] = $yorumlar;

    // 4. Rozetleri Hesapla
    $rozetler = [];
    if ($yorum_sayisi >= 1) $rozetler[] = ['ad' => 'Kaşif', 'aciklama' => 'İlk yorumunu yaptı!'];
    if ($yorum_sayisi >= 5) $rozetler[] = ['ad' => 'Gezgin', 'aciklama' => '5 veya daha fazla yorum yaptı.'];
    if ($plan_sayisi >= 1) $rozetler[] = ['ad' => 'Planlamacı', 'aciklama' => 'İlk gezi planını oluşturdu.'];
    $response['data']['rozetler'] = $rozetler;

    // 5. Kullanıcı Listelerini Al
    $stmt_listeler = $pdo->prepare("SELECT id, liste_adi, aciklama FROM kullanici_listeleri WHERE user_id = ? ORDER BY olusturulma_tarihi DESC");
    $stmt_listeler->execute([$user_id]);
    $response['data']['listeler'] = $stmt_listeler->fetchAll();

    // 6. Kullanıcının Gezi Planlarını Al
    $stmt_planlar = $pdo->prepare("SELECT id, plan_adi FROM gezi_planlari WHERE user_id = ? ORDER BY olusturulma_tarihi DESC");
    $stmt_planlar->execute([$user_id]);
    $response['data']['planlar'] = $stmt_planlar->fetchAll();

    echo json_encode($response);

} catch (PDOException $e) {
    error_log('profil_getir.php hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Profil bilgileri yüklenirken bir hata oluştu.']);
}
?>
