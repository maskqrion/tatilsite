<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$user_id = $_GET['id'] ?? 0;

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı kimliği belirtilmedi.']);
    exit;
}

$response = ['success' => true, 'data' => []];

// 1. Kullanıcının Temel Herkese Açık Bilgilerini Al (bind_result Düzeltmesi)
$stmt_user = $conn->prepare("SELECT name, created_at, profile_image FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$stmt_user->bind_result($name, $created_at, $profile_image);
$user_info = null;
if ($stmt_user->fetch()) {
    $user_info = [
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'created_at' => date("d F Y", strtotime($created_at)),
        'profile_image' => $profile_image
    ];
    $response['data']['user'] = $user_info;
}
$stmt_user->close();

if (!$user_info) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
    $conn->close();
    exit;
}

// 2. Kullanıcının Yaptığı Yorumları Al (bind_result Düzeltmesi)
$stmt_comments = $conn->prepare("
    SELECT 
        y.yorum_metni,
        y.tarih,
        y.rota_id,
        r.ad AS rota_adi
    FROM 
        yorumlar y
    JOIN 
        rotalar r ON y.rota_id = r.id
    WHERE 
        y.user_id = ?
    ORDER BY 
        y.tarih DESC
    LIMIT 10
");
$stmt_comments->bind_param("i", $user_id);
$stmt_comments->execute();
$stmt_comments->bind_result($yorum_metni, $tarih, $rota_id, $rota_adi);
$yorumlar = [];
while ($stmt_comments->fetch()) {
    $yorumlar[] = [
        'yorum_metni' => htmlspecialchars($yorum_metni, ENT_QUOTES, 'UTF-8'),
        'tarih' => date("d F Y", strtotime($tarih)),
        'rota_id' => $rota_id,
        'rota_adi' => htmlspecialchars($rota_adi, ENT_QUOTES, 'UTF-8')
    ];
}
$response['data']['yorumlar'] = $yorumlar;
$stmt_comments->close();


echo json_encode($response);
$conn->close();
?>