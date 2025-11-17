<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    // KOD GÜNCELLENDİ: bind_result() kullanıldı
    $stmt = $conn->prepare("
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
    $stmt->bind_param("is", $user_id_to_bind, $rota_id);
    $stmt->execute();

    $stmt->bind_result(
        $id, $user_id, $yorum_metni, $tarih, $puan, $parent_id, $yorum_tipi,
        $kullanici_adi, $begeni_sayisi, $kullanici_begendi
    );

    $tum_yorumlar = [];
    while ($stmt->fetch()) {
        $tum_yorumlar[] = [
            'id' => $id,
            'user_id' => $user_id,
            'yorum_metni' => htmlspecialchars($yorum_metni, ENT_QUOTES, 'UTF-8'),
            'tarih' => date("d F Y", strtotime($tarih)),
            'puan' => $puan,
            'parent_id' => $parent_id,
            'yorum_tipi' => $yorum_tipi,
            'kullanici_adi' => htmlspecialchars($kullanici_adi, ENT_QUOTES, 'UTF-8'),
            'begeni_sayisi' => $begeni_sayisi,
            'kullanici_begendi' => !empty($kullanici_begendi) && $kullanici_begendi > 0,
            'yanitlar' => []
        ];
    }
    $stmt->close();

    // Ağaç yapısını oluşturan bu kısım aynı kalabilir
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
     echo json_encode(['success' => false, 'message' => 'Yorumlar getirilirken bir sunucu hatası oluştu: ' . $e->getMessage()]);
}

$conn->close();
?>