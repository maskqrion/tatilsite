<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

$rota_id = $_GET['rota_id'] ?? '';
$current_user_id = $_SESSION['id'] ?? null;

if (empty($rota_id)) {
    echo json_encode(['success' => false, 'message' => 'Rota kimliği belirtilmedi.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            y.id, y.user_id, y.yorum_metni, y.tarih, y.puan, y.parent_id, y.yorum_tipi,
            u.name AS kullanici_adi,
            (SELECT COUNT(*) FROM yorum_begeni WHERE yorum_id = y.id) as begeni_sayisi,
            (SELECT COUNT(*) FROM yorum_begeni WHERE yorum_id = y.id AND user_id = ?) as kullanici_begendi
        FROM yorumlar y
        JOIN users u ON y.user_id = u.id
        WHERE y.rota_id = ?
        ORDER BY y.tarih ASC
    ");

    $user_id_to_bind = $current_user_id ?? 0;
    $stmt->execute([$user_id_to_bind, $rota_id]);
    $tum_yorumlar_raw = $stmt->fetchAll();

    // Çıktı için güvenli hale getir
    $tum_yorumlar = [];
    foreach ($tum_yorumlar_raw as $row) {
        $tum_yorumlar[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'yorum_metni' => htmlspecialchars($row['yorum_metni'], ENT_QUOTES, 'UTF-8'),
            'tarih' => date("d F Y", strtotime($row['tarih'])),
            'puan' => $row['puan'],
            'parent_id' => $row['parent_id'],
            'yorum_tipi' => $row['yorum_tipi'],
            'kullanici_adi' => htmlspecialchars($row['kullanici_adi'], ENT_QUOTES, 'UTF-8'),
            'begeni_sayisi' => $row['begeni_sayisi'],
            'kullanici_begendi' => !empty($row['kullanici_begendi']) && $row['kullanici_begendi'] > 0,
            'yanitlar' => []
        ];
    }

    // Ağaç yapısını oluştur
    $yorum_map = [];
    foreach ($tum_yorumlar as $yorum) {
        $yorum_map[$yorum['id']] = $yorum;
    }

    $yorum_agaci = [];
    $soru_agaci = [];

    foreach ($yorum_map as $id => &$yorum) {
        if ($yorum['parent_id'] !== null && isset($yorum_map[$yorum['parent_id']])) {
            $yorum_map[$yorum['parent_id']]['yanitlar'][] = &$yorum;
        } else {
            if (isset($yorum['yorum_tipi']) && $yorum['yorum_tipi'] === 'soru') {
                $soru_agaci[] = &$yorum;
            } else {
                $yorum_agaci[] = &$yorum;
            }
        }
    }
    unset($yorum);

    usort($yorum_agaci, function($a, $b) {
        return strtotime($b['tarih']) - strtotime($a['tarih']);
    });
    usort($soru_agaci, function($a, $b) {
        return strtotime($b['tarih']) - strtotime($a['tarih']);
    });

    echo json_encode([
        'success' => true,
        'yorumlar' => $yorum_agaci,
        'sorular' => $soru_agaci,
        'current_user_id' => $current_user_id
    ]);

} catch (Exception $e) {
    error_log('yorum_getir hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Yorumlar getirilirken bir sunucu hatası oluştu.']);
}
?>
