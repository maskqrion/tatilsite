<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->prepare("
        SELECT
            u.id, u.name, u.profile_image,
            COALESCE(yorum.sayi, 0) * 10 +
            COALESCE(liste.sayi, 0) * 25 +
            COALESCE(begeni.sayi, 0) * 2 AS toplam_puan
        FROM users u
        LEFT JOIN (SELECT user_id, COUNT(id) as sayi FROM yorumlar GROUP BY user_id) AS yorum ON u.id = yorum.user_id
        LEFT JOIN (SELECT user_id, COUNT(id) as sayi FROM kullanici_listeleri WHERE herkese_acik = 1 GROUP BY user_id) AS liste ON u.id = liste.user_id
        LEFT JOIN (SELECT y.user_id, COUNT(b.id) as sayi FROM yorum_begeni b JOIN yorumlar y ON b.yorum_id = y.id WHERE y.user_id != b.user_id GROUP BY y.user_id) AS begeni ON u.id = begeni.user_id
        ORDER BY toplam_puan DESC
        LIMIT 20
    ");
    $stmt->execute();
    $leaderboard = [];
    foreach ($stmt->fetchAll() as $row) {
        if ($row['toplam_puan'] > 0) {
            $leaderboard[] = $row;
        }
    }
    echo json_encode(['success' => true, 'data' => $leaderboard]);
} catch (PDOException $e) {
    error_log('liderlik_tablosu_getir hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Liderlik tablosu yüklenirken bir hata oluştu.']);
}
?>
