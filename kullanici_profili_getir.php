<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$user_id = (int)($_GET['id'] ?? 0);

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı kimliği belirtilmedi.']);
    exit;
}

$response = ['success' => true, 'data' => []];

try {
    // Kullanıcının Temel Bilgilerini Al
    $stmt_user = $pdo->prepare("SELECT name, created_at, profile_image FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user_info = $stmt_user->fetch();

    if (!$user_info) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
        exit;
    }

    $response['data']['user'] = [
        'name' => htmlspecialchars($user_info['name'], ENT_QUOTES, 'UTF-8'),
        'created_at' => date("d F Y", strtotime($user_info['created_at'])),
        'profile_image' => $user_info['profile_image']
    ];

    // Kullanıcının Yorumlarını Al
    $stmt_comments = $pdo->prepare("
        SELECT y.yorum_metni, y.tarih, y.rota_id, r.ad AS rota_adi
        FROM yorumlar y JOIN rotalar r ON y.rota_id = r.id
        WHERE y.user_id = ? ORDER BY y.tarih DESC LIMIT 10
    ");
    $stmt_comments->execute([$user_id]);
    $yorumlar = [];
    foreach ($stmt_comments->fetchAll() as $row) {
        $yorumlar[] = [
            'yorum_metni' => htmlspecialchars($row['yorum_metni'], ENT_QUOTES, 'UTF-8'),
            'tarih' => date("d F Y", strtotime($row['tarih'])),
            'rota_id' => $row['rota_id'],
            'rota_adi' => htmlspecialchars($row['rota_adi'], ENT_QUOTES, 'UTF-8')
        ];
    }
    $response['data']['yorumlar'] = $yorumlar;

    echo json_encode($response);
} catch (PDOException $e) {
    error_log('kullanici_profili_getir hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Profil yüklenirken bir hata oluştu.']);
}
?>
