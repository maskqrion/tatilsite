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

$search_term_bool = "";
$terms = explode(' ', $term);
foreach ($terms as $t) {
    if (!empty($t)) {
        $search_term_bool .= "+" . $t . "* ";
    }
}
$search_term_bool = trim($search_term_bool);


// 1. Rotalar tablosunda ara (bind_result Düzeltmesi)
$stmt_rotalar = $conn->prepare("
    SELECT id, ad, aciklama, resim, 
           MATCH(ad, aciklama, tanitim) AGAINST(? IN BOOLEAN MODE) AS relevance
    FROM rotalar 
    WHERE MATCH(ad, aciklama, tanitim) AGAINST(? IN BOOLEAN MODE)
    ORDER BY relevance DESC
    LIMIT 10
");
$stmt_rotalar->bind_param("ss", $search_term_bool, $search_term_bool);
$stmt_rotalar->execute();
$stmt_rotalar->bind_result($id, $ad, $aciklama, $resim, $relevance);
while($stmt_rotalar->fetch()) {
    $results['rotalar'][] = [
        'id' => $id,
        'ad' => $ad,
        'aciklama' => $aciklama,
        'resim' => $resim,
        'relevance' => $relevance
    ];
}
$stmt_rotalar->close();

// 2. Blog Yazıları tablosunda ara (bind_result Düzeltmesi)
$stmt_blog = $conn->prepare("
    SELECT id, baslik, ozet, resim,
           MATCH(baslik, ozet, icerik) AGAINST(? IN BOOLEAN MODE) AS relevance
    FROM blog_yazilari 
    WHERE MATCH(baslik, ozet, icerik) AGAINST(? IN BOOLEAN MODE)
    ORDER BY relevance DESC
    LIMIT 10
");
$stmt_blog->bind_param("ss", $search_term_bool, $search_term_bool);
$stmt_blog->execute();
$stmt_blog->bind_result($id, $baslik, $ozet, $resim, $relevance);
while($stmt_blog->fetch()) {
    $results['blog'][] = [
        'id' => $id,
        'baslik' => $baslik,
        'ozet' => $ozet,
        'resim' => $resim,
        'relevance' => $relevance
    ];
}
$stmt_blog->close();

echo json_encode(['success' => true, 'data' => $results]);

$conn->close();
?>