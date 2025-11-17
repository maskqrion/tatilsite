<?php

// Adım 1: Veritabanı bağlantısını kuran DOĞRU dosyayı dahil et
// Bu dosya, $conn değişkenini bize sağlar.
require 'db_config.php'; // Hata düzeltildi: 'db.php' yerine 'db_config.php' kullanıldı

echo "<h1>Kullanıcı Listesi</h1>";

// Adım 2: Çalıştırmak istediğimiz SQL sorgusu
$sql = "SELECT id, name, email, created_at FROM users";

// Adım 3: Sorguyu çalıştır ve sonucu al
// $conn nesnesi db_config.php dosyasından geliyor
$result = $conn->query($sql);

// Adım 4: Sonuçları kontrol et ve satır satır işle (Cursor mantığı)
if ($result->num_rows > 0) {
    // Veri varsa, tablo oluşturup başlıkları yaz
    echo "<table border='1'>
            <tr>
                <th>ID</th>
                <th>İsim</th>
                <th>E-posta</th>
                <th>Kayıt Tarihi</th>
            </tr>";

    // fetch_assoc() ile her satırı bir dizi olarak al
    // Veri olduğu sürece döngü çalışır
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row["id"]. "</td>
                <td>" . $row["name"]. "</td>
                <td>" . $row["email"]. "</td>
                <td>" . $row["created_at"]. "</td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "Veritabanında hiç kullanıcı bulunamadı.";
}

// Adım 5: İşlem bittiğinde bağlantıyı kapat
$conn->close();

?>