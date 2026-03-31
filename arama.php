<?php
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$term = $_GET['term'] ?? '';

if (strlen($term) < 3) {
    echo json_encode(['success' => false, 'message' => 'Lütfen en az 3 karakter girin.']);
    exit;
}

$results = [
    'rotalar' => [],
    'blog' => []
];

// Boolean arama terimini oluştur (özel FTS karakterlerini temizle)
$search_term_bool = "";
$terms = explode(' ', $term);
foreach ($terms as $t) {
    $t = preg_replace('/[+\-><~*"@(){}\\[\\]]/', '', $t);
    if (!empty($t)) {
        $search_term_bool .= "+" . $t . "* ";
    }
}
$search_term_bool = trim($search_term_bool);

if (empty($search_term_bool)) {
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

try {
    $pdo = getDB();

    // 1. Rotalar tablosunda ara
    $stmt = $pdo->prepare("
        SELECT id, ad, aciklama, resim,
               MATCH(ad, aciklama, tanitim) AGAINST(? IN BOOLEAN MODE) AS relevance
        FROM rotalar
        WHERE MATCH(ad, aciklama, tanitim) AGAINST(? IN BOOLEAN MODE)
        ORDER BY relevance DESC
        LIMIT 10
    ");
    $stmt->execute([$search_term_bool, $search_term_bool]);
    $results['rotalar'] = $stmt->fetchAll();

    // 2. Blog Yazıları tablosunda ara
    $stmt = $pdo->prepare("
        SELECT id, baslik, ozet, resim,
               MATCH(baslik, ozet, icerik) AGAINST(? IN BOOLEAN MODE) AS relevance
        FROM blog_yazilari
        WHERE MATCH(baslik, ozet, icerik) AGAINST(? IN BOOLEAN MODE)
        ORDER BY relevance DESC
        LIMIT 10
    ");
    $stmt->execute([$search_term_bool, $search_term_bool]);
    $results['blog'] = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $results]);

} catch (Exception $e) {
    error_log('arama hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Arama sırasında bir hata oluştu.']);
}
?>
