<?php
require 'db_config.php';

echo "<h1>Mekanlar Tablosu Test Sayfası</h1>";

// Test için içinde mekan olduğundan emin olduğumuz bir rota ID'si belirliyoruz.
// Eğer 'akyaka' rotanızda mekan yoksa, lütfen burada ID'yi mekan olan başka bir rota ile değiştirin.
$rota_id = 'akyaka'; 

echo "<p><strong>'".$rota_id."'</strong> ID'li Rota için Mekanlar Aranıyor...</p><hr>";

$stmt = $conn->prepare("SELECT * FROM mekanlar WHERE rota_id = ?");
$stmt->bind_param("s", $rota_id);
$stmt->execute();
$sonuc = $stmt->get_result();

if ($sonuc && $sonuc->num_rows > 0) {
    echo "<h3>Bulunan Mekanlar:</h3>";
    echo "<table border='1' cellpadding='10' cellspacing='0'>
            <tr>
                <th>ID</th>
                <th>Tip</th>
                <th>Ad</th>
                <th>Kategori</th>
                <th>Onaylandı Mı?</th>
            </tr>";

    while($satir = $sonuc->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($satir["id"]) . "</td>
                <td>" . htmlspecialchars($satir["tip"]) . "</td>
                <td>" . htmlspecialchars($satir["ad"]) . "</td>
                <td>" . htmlspecialchars($satir["kategori"]) . "</td>
                <td>" . (isset($satir["onaylandi"]) ? htmlspecialchars($satir["onaylandi"]) : 'Sütun Yok') . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<h3>Hiç mekan bulunamadı.</h3>";
    echo "<p>Bu durumun birkaç sebebi olabilir:</p>";
    echo "<ul>";
    echo "<li>'".$rota_id."' ID'li rota için veritabanına hiç mekan eklenmemiş olabilir.</li>";
    echo "<li>Sorguda bir hata olabilir. Veritabanı hatası: " . $conn->error . "</li>";
    echo "</ul>";
}

$stmt->close();
$conn->close();
?>