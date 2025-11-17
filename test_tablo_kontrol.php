<?php
require 'db_config.php';

echo "<h1>'kullanici_fotograflari' Tablosu Kontrol Aracı</h1>";

// Tablonun var olup olmadığını kontrol et
$result = $conn->query("SHOW TABLES LIKE 'kullanici_fotograflari'");

if ($result->num_rows == 1) {
    echo "<p style='color:green; font-weight:bold;'>✔ 'kullanici_fotograflari' tablosu veritabanında MEVCUT.</p>";
    echo "<h3>Tablo Yapısı (Sütunlar):</h3>";

    // Tablo yapısını al
    $describe_result = $conn->query("DESCRIBE kullanici_fotograflari");
    echo "<table border='1' cellpadding='5' cellspacing='0'>
            <tr style='background-color:#f2f2f2;'>
                <th>Field (Sütun Adı)</th>
                <th>Type (Veri Tipi)</th>
                <th>Null</th>
                <th>Key</th>
                <th>Default</th>
                <th>Extra</th>
            </tr>";
    while($row = $describe_result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['Field']) . "</td>
                <td>" . htmlspecialchars($row['Type']) . "</td>
                <td>" . htmlspecialchars($row['Null']) . "</td>
                <td>" . htmlspecialchars($row['Key']) . "</td>
                <td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>
                <td>" . htmlspecialchars($row['Extra']) . "</td>
              </tr>";
    }
    echo "</table>";

} else {
    echo "<p style='color:red; font-weight:bold; font-size:20px;'>❌ HATA: 'kullanici_fotograflari' tablosu veritabanında BULUNAMADI!</p>";
    echo "<p>Lütfen bir önceki yanıtta verilen SQL kodunu phpMyAdmin üzerinden çalıştırarak tabloyu oluşturun.</p>";
}

$conn->close();
?>