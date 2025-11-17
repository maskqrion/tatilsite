<?php
header('Content-Type: application/xml; charset=utf-8');

// Veritabanı bağlantısını sağlayan dosyayı dahil et
require 'db_config.php';

// XML çıktısının başlangıcını yaz
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Mevcut statik sayfaları ekle
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

// Rotalar tablosundaki tüm rotaları veritabanından çek
$sql_rotalar = "SELECT id, ad FROM rotalar";
$result_rotalar = $conn->query($sql_rotalar);
if ($result_rotalar->num_rows > 0) {
    while ($row = $result_rotalar->fetch_assoc()) {
        echo '<url>';
        echo '<loc>https://www.seckinrotalar.com/rota-detay.html?id=' . htmlspecialchars($row['id']) . '</loc>';
        echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
        echo '<priority>0.95</priority>';
        echo '</url>';
    }
}

// Blog yazıları tablosundaki tüm yazıları veritabanından çek
$sql_blog = "SELECT id FROM blog_yazilari";
$result_blog = $conn->query($sql_blog);
if ($result_blog->num_rows > 0) {
    while ($row = $result_blog->fetch_assoc()) {
        echo '<url>';
        echo '<loc>https://www.seckinrotalar.com/blog-detay.html?yazi=' . htmlspecialchars($row['id']) . '</loc>';
        echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
        echo '<priority>0.95</priority>';
        echo '</url>';
    }
}

// XML çıktısının sonunu yaz
echo '</urlset>';

$conn->close();
?>