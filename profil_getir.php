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
$response = ['success' => true, 'data' => []];

// 1. Kullanıcının Temel Bilgilerini Al (bind_result Düzeltmesi)
$stmt = $conn->prepare("SELECT name, email, created_at, profile_image, hakkimda FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $created_at, $profile_image, $hakkimda);
$user_info = null;
if ($stmt->fetch()) {
    $user_info = [
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
        'created_at' => date("d F Y", strtotime($created_at)),
        'profile_image' => $profile_image,
        'hakkimda' => htmlspecialchars($hakkimda ?? '', ENT_QUOTES, 'UTF-8')
    ];
    $response['data']['user'] = $user_info;
}
$stmt->close();

if ($user_info === null) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı bilgileri alınamadı.']);
    $conn->close();
    exit;
}

// 2. İstatistikleri ve Puanları Hesapla (bind_result Düzeltmesi)
$stmt_stats = $conn->prepare("
    SELECT 
        (SELECT COUNT(id) FROM yorumlar WHERE user_id = ?) as toplam_yorum,
        (SELECT COUNT(id) FROM gezi_planlari WHERE user_id = ?) as toplam_plan,
        (SELECT COUNT(id) FROM kullanici_listeleri WHERE user_id = ? AND herkese_acik = 1) as toplam_liste,
        (SELECT COUNT(b.id) FROM yorum_begeni b JOIN yorumlar y ON b.yorum_id = y.id WHERE y.user_id = ? AND y.user_id != b.user_id) as toplam_begeni
");
$stmt_stats->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt_stats->execute();
$stmt_stats->bind_result($toplam_yorum, $toplam_plan, $toplam_liste, $toplam_begeni);
$stmt_stats->fetch();
$stats = [
    'toplam_yorum' => $toplam_yorum,
    'toplam_plan' => $toplam_plan,
    'toplam_liste' => $toplam_liste,
    'toplam_begeni' => $toplam_begeni
];
$stmt_stats->close();

$yorum_sayisi = (int)$stats['toplam_yorum'];
$plan_sayisi = (int)$stats['toplam_plan'];
$liste_sayisi = (int)$stats['toplam_liste'];
$begeni_sayisi = (int)$stats['toplam_begeni'];

$response['data']['stats']['toplam_yorum'] = $yorum_sayisi;
$response['data']['stats']['toplam_plan'] = $plan_sayisi;

$toplam_puan = ($yorum_sayisi * 10) + ($liste_sayisi * 25) + ($begeni_sayisi * 2);
$response['data']['stats']['toplam_puan'] = $toplam_puan;


// 3. Yorum Geçmişini Al (bind_result Düzeltmesi)
$stmt_yorumlar = $conn->prepare("
    SELECT y.yorum_metni, y.tarih, y.rota_id, r.ad AS rota_adi
    FROM yorumlar y JOIN rotalar r ON y.rota_id = r.id
    WHERE y.user_id = ? ORDER BY y.tarih DESC LIMIT 5
");
$stmt_yorumlar->bind_param("i", $user_id);
$stmt_yorumlar->execute();
$stmt_yorumlar->bind_result($yorum_metni, $tarih, $rota_id, $rota_adi);
$yorumlar = [];
while ($stmt_yorumlar->fetch()) {
    $yorumlar[] = [
        'yorum_metni' => htmlspecialchars($yorum_metni, ENT_QUOTES, 'UTF-8'),
        'tarih' => date("d F Y", strtotime($tarih)),
        'rota_id' => $rota_id,
        'rota_adi' => htmlspecialchars($rota_adi, ENT_QUOTES, 'UTF-8')
    ];
}
$response['data']['yorumlar'] = $yorumlar;
$stmt_yorumlar->close();

// 4. Rozetleri Hesapla
$rozetler = [];
if ($yorum_sayisi >= 1) $rozetler[] = ['ad' => 'Kaşif', 'aciklama' => 'İlk yorumunu yaptı!'];
if ($yorum_sayisi >= 5) $rozetler[] = ['ad' => 'Gezgin', 'aciklama' => '5 veya daha fazla yorum yaptı.'];
if ($plan_sayisi >= 1) $rozetler[] = ['ad' => 'Planlamacı', 'aciklama' => 'İlk gezi planını oluşturdu.'];
$response['data']['rozetler'] = $rozetler;

// 5. Kullanıcı Listelerini Al (bind_result Düzeltmesi)
$stmt_listeler = $conn->prepare("SELECT id, liste_adi, aciklama FROM kullanici_listeleri WHERE user_id = ? ORDER BY olusturulma_tarihi DESC");
$stmt_listeler->bind_param("i", $user_id);
$stmt_listeler->execute();
$stmt_listeler->bind_result($liste_id, $liste_adi, $aciklama);
$listeler = [];
while ($stmt_listeler->fetch()) {
    $listeler[] = [
        'id' => $liste_id,
        'liste_adi' => $liste_adi,
        'aciklama' => $aciklama
    ];
}
$response['data']['listeler'] = $listeler;
$stmt_listeler->close();

// 6. Kullanıcının Gezi Planlarını Al (bind_result Düzeltmesi)
$stmt_planlar = $conn->prepare("SELECT id, plan_adi FROM gezi_planlari WHERE user_id = ? ORDER BY olusturulma_tarihi DESC");
$stmt_planlar->bind_param("i", $user_id);
$stmt_planlar->execute();
$stmt_planlar->bind_result($plan_id, $plan_adi);
$planlar = [];
while ($stmt_planlar->fetch()) {
    $planlar[] = [
        'id' => $plan_id,
        'plan_adi' => $plan_adi
    ];
}
$response['data']['planlar'] = $planlar;
$stmt_planlar->close();


echo json_encode($response);
$conn->close();
?>