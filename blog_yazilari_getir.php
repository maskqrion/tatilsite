<?php
require 'db_config.php';
header('Content-Type: application/json');

$sql = "SELECT id, baslik, kategori, tarih, resim, ozet FROM blog_yazilari ORDER BY tarih DESC";
$result = $conn->query($sql);

$yazilar = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $yazilar[] = $row;
    }
}

echo json_encode($yazilar);

$conn->close();
?>