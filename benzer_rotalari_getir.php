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

// 1. Mevcut rotanın özelliklerini al
$stmt_current = $conn->prepare("SELECT bolge, fiyat, etiketler FROM rotalar WHERE id = ?");
$stmt_current->bind_param("s", $current_rota_id);
$stmt_current->execute();
$result_current = $stmt_current->get_result();
$current_rota = $result_current->fetch_assoc();
$stmt_current->close();

if (!$current_rota) {
    echo json_encode(['success' => false, 'message' => 'Rota bulunamadı.']);
    exit;
}

$response_items = [];
$current_tags = array_map('trim', explode(',', $current_rota['etiketler']));

// 2. Benzer Rotaları Getir (Mevcut mantık korundu, 2 ile sınırlandı)
// ... (Kişiselleştirilmiş öneri kodu burada varsayılabilir) ...
if (count($response_items) < 2) {
    $limit = 2 - count($response_items);
    $tag_clauses = [];
    $params = [$current_rota_id];
    $types = 's';
    
    foreach ($current_tags as $tag) {
        if (!empty($tag)) {
            $tag_clauses[] = "etiketler LIKE ?";
            $params[] = '%' . $tag . '%';
            $types .= 's';
        }
    }
    
    $sql_generic_rotas = "
        SELECT id, ad, aciklama, resim, 'rota' as item_type
        FROM rotalar
        WHERE id != ? 
    ";
    
    if (!empty($tag_clauses)) {
        $sql_generic_rotas .= " AND (" . implode(' OR ', $tag_clauses) . " OR bolge = ?)";
        $params[] = $current_rota['bolge'];
        $types .= 's';
    } else {
        $sql_generic_rotas .= " AND bolge = ?";
        $params[] = $current_rota['bolge'];
        $types .= 's';
    }
    
    $sql_generic_rotas .= " ORDER BY RAND() LIMIT ?"; // Küçük limit için RAND() kabul edilebilir
    $params[] = $limit;
    $types .= 'i';

    $stmt_generic = $conn->prepare($sql_generic_rotas);
    $stmt_generic->bind_param($types, ...$params);
    $stmt_generic->execute();
    $result_generic = $stmt_generic->get_result();
    while ($row = $result_generic->fetch_assoc()) {
        $response_items[] = $row;
    }
    $stmt_generic->close();
}


// 3. YENİ: Benzer Blog Yazılarını Getir (Etiketlere göre)
if (!empty($current_tags)) {
    $tag_clauses_blog = [];
    $params_blog = [];
    $types_blog = '';
    
    foreach ($current_tags as $tag) {
        if (!empty($tag)) {
            $tag_clauses_blog[] = "etiketler LIKE ?"; // Blog tablosunda da 'etiketler' sütunu olduğunu varsayıyoruz
            $params_blog[] = '%' . $tag . '%';
            $types_blog .= 's';
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
        
        $stmt_blog = $conn->prepare($sql_blog);
        $stmt_blog->bind_param($types_blog, ...$params_blog);
        $stmt_blog->execute();
        $result_blog = $stmt_blog->get_result();
        while ($row = $result_blog->fetch_assoc()) {
            $response_items[] = $row;
        }
        $stmt_blog->close();
    }
}

// Sonuçları karıştırıp gönder
shuffle($response_items);

$conn->close();
echo json_encode(['success' => true, 'items' => $response_items]);
?>