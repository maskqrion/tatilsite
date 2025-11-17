<?php
/*
==============================================
VERİTABANI BAĞLANTI TEST DOSYASI
==============================================
Bu dosya, db_config.php'nin doğru çalışıp çalışmadığını
ve veritabanından veri çekilip çekilemediğini test eder.
*/

// 1. ADIM: Bağlantı ayar dosyasını sayfaya dahil et.
require 'db_config.php';

echo "<h1>Veritabanı Test Sayfası</h1>";
echo "<p><strong>Bağlantı Durumu:</strong> Başarılı!</p>";
echo "<hr>";
echo "<h2>Kullanıcılar Tablosundaki Veriler:</h2>";

// 2. ADIM: Veritabanına göndermek istediğin SQL komutunu hazırla.
$sql = "SELECT id, name, email, created_at FROM users";

// 3. ADIM: Komutu $conn bağlantısı üzerinden çalıştır ve gelen sonucu $sonuc değişkenine ata.
$sonuc = $conn->query($sql);

// 4. ADIM: Gelen sonucun içinde veri var mı diye kontrol et.
if ($sonuc && $sonuc->num_rows > 0) {
    // Veri varsa, bir HTML tablosu oluştur.
    echo "<table border='1' cellpadding='10' cellspacing='0'>
            <tr>
                <th>ID</th>
                <th>İsim Soyisim</th>
                <th>E-posta</th>
                <th>Kayıt Tarihi</th>
            </tr>";

    // Gelen sonuçları satır satır oku.
    while($satir = $sonuc->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($satir["id"]) . "</td>
                <td>" . htmlspecialchars($satir["name"]) . "</td>
                <td>" . htmlspecialchars($satir["email"]) . "</td>
                <td>" . htmlspecialchars($satir["created_at"]) . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    // Eğer tablodan hiç veri gelmediyse veya sorgu başarısızsa bu mesajı göster.
    echo "Kullanıcılar tablosunda hiç veri bulunamadı veya sorgu başarısız oldu.";
    if ($conn->error) {
        echo "<br><strong>Hata:</strong> " . $conn->error;
    }
}

// 5. ADIM: İşin bittiğinde veritabanı bağlantısını kapat.
$conn->close();

?>