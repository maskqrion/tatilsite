<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$current_rota_id = $_GET['id'] ?? '';
$user_id = $_SESSION['id'] ?? null;

if (empty($current_rota_id)) {
    echo json_encode(['success' => false, 'message' => 'Rota kimliği belirtilmedi.']);
    exit;
}

try {
    $pdo = getDB();

    // 1. Mevcut rotanın özelliklerini al
    $stmt = $pdo->prepare("SELECT bolge, fiyat, etiketler FROM rotalar WHERE id = ?");
    $stmt->execute([$current_rota_id]);
    $current_rota = $stmt->fetch();

    if ($current_rota === false) {
        echo json_encode(['success' => false, 'message' => 'Rota bulunamadı.']);
        exit;
    }

    $response_items = [];
    $current_tags = array_map('trim', explode(',', $current_rota['etiketler']));

    // 2. Benzer Rotaları Getir (Mevcut mantık korundu, 2 ile sınırlandı)
    if (count($response_items) < 2) {
        $limit = 2 - count($response_items);
        $params = [$current_rota_id];

        $sql_generic_rotas = "
            SELECT id, ad, aciklama, resim, 'rota' as item_type
            FROM rotalar
            WHERE id != ?
        ";

        $tag_clauses = [];
        foreach ($current_tags as $tag) {
            if (!empty($tag)) {
                $tag_clauses[] = "etiketler LIKE ?";
                $params[] = '%' . $tag . '%';
            }
        }

        if (!empty($tag_clauses)) {
            $sql_generic_rotas .= " AND (" . implode(' OR ', $tag_clauses) . " OR bolge = ?)";
            $params[] = $current_rota['bolge'];
        } else {
            $sql_generic_rotas .= " AND bolge = ?";
            $params[] = $current_rota['bolge'];
        }

        $sql_generic_rotas .= " ORDER BY RAND() LIMIT ?"; // Küçük limit için RAND() kabul edilebilir
        $params[] = $limit;

        $stmt = $pdo->prepare($sql_generic_rotas);
        $stmt->execute($params);
        while ($row = $stmt->fetch()) {
            $response_items[] = $row;
        }
    }

    // 3. YENİ: Benzer Blog Yazılarını Getir (Etiketlere göre)
    if (!empty($current_tags)) {
        $tag_clauses_blog = [];
        $params_blog = [];

        foreach ($current_tags as $tag) {
            if (!empty($tag)) {
                $tag_clauses_blog[] = "etiketler LIKE ?"; // Blog tablosunda da 'etiketler' sütunu olduğunu varsayıyoruz
                $params_blog[] = '%' . $tag . '%';
            }
        }

        if (!empty($tag_clauses_blog)) {
            $sql_blog = "
                SELECT id, baslik AS ad, ozet AS aciklama, resim, 'blog' as item_type
                FROM blog_yazilari
                WHERE " . implode(' OR ', $tag_clauses_blog) . "
                ORDER BY tarih DESC
                LIMIT 2
            ";

            $stmt = $pdo->prepare($sql_blog);
            $stmt->execute($params_blog);
            while ($row = $stmt->fetch()) {
                $response_items[] = $row;
            }
        }
    }

    // Sonuçları karıştırıp gönder
    shuffle($response_items);

    echo json_encode(['success' => true, 'items' => $response_items]);

} catch (Exception $e) {
    error_log('benzer_rotalari_getir hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası oluştu.']);
}
?>
