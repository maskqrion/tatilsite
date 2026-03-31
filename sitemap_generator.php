<?php
header('Content-Type: application/xml; charset=utf-8');
require 'db_config.php';

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$static_pages = [
    'index.html' => 1.0,
    'rotalar.html' => 0.9,
    'hakkinda.html' => 0.8,
    'iletisim.html' => 0.7,
    'blog.html' => 0.85,
    'genel-bilgiler.html' => 0.8
];

foreach ($static_pages as $page => $priority) {
    echo '<url>';
    echo '<loc>https://www.seckinrotalar.com/' . htmlspecialchars($page) . '</loc>';
    echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
    echo '<priority>' . $priority . '</priority>';
    echo '</url>';
}

try {
    // Rotalar
    $stmt = $pdo->prepare("SELECT id FROM rotalar");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        echo '<url>';
        echo '<loc>https://www.seckinrotalar.com/rota-detay.html?id=' . htmlspecialchars($row['id']) . '</loc>';
        echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
        echo '<priority>0.95</priority>';
        echo '</url>';
    }

    // Blog yazıları
    $stmt = $pdo->prepare("SELECT id FROM blog_yazilari");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        echo '<url>';
        echo '<loc>https://www.seckinrotalar.com/blog-detay.html?yazi=' . htmlspecialchars($row['id']) . '</loc>';
        echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
        echo '<priority>0.95</priority>';
        echo '</url>';
    }
} catch (PDOException $e) {
    error_log('sitemap_generator hatası: ' . $e->getMessage());
}

echo '</urlset>';
?>
