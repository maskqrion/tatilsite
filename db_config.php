<?php
// === YENİ GÜVENLİK ÖNLEMİ (1. ADIM) ===
// PHP'nin HTML/metin formatında hata basmasını engelle.
// Bu, "Unexpected token '<'" hatasını önlemek için KRİTİKTİR.
error_reporting(0);
ini_set('display_errors', 0);

// === YENİ HATA YÖNETİCİSİ (2. ADIM) ===
// Çökme durumunda her zaman geçerli JSON döndürecek bir fonksiyon
function db_die($message) {
    http_response_code(500); // Sunucu Hatası
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit; // Kodu burada sonlandır.
}

/**
 * .env dosyasını okuyup $_ENV değişkenine yükleyen basit bir fonksiyon.
 */
function loadEnv($path)
{
    if (!is_readable($path)) {
        // Hata fırlat, try-catch bloğu yakalayacak
        throw new \RuntimeException(sprintf('%s dosyası okunamıyor.', $path));
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \n\r\t\v\0\"");

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
        }
    }
}

// === ANA TRY-CATCH BLOĞU (3. ADIM) ===
// .env yükleme VEYA veritabanı bağlantısı başarısız olursa, 
// JSON hatası döndürülmesini garanti eder.
try {
    // .env dosyasını yükle
    loadEnv(__DIR__ . '/.env');

    // Veritabanı bilgilerini .env dosyasından al
    $sunucu = $_ENV['DB_HOST'] ?? 'localhost';
    $kullanici_adi = $_ENV['DB_USERNAME'] ?? 'root';
    $sifre = $_ENV['DB_PASSWORD'] ?? '';
    $veritabani_adi = $_ENV['DB_DATABASE'] ?? 'tatilsite';
    
    // Veritabanı bağlantısı
    $conn = new mysqli($sunucu, $kullanici_adi, $sifre, $veritabani_adi);

    if ($conn->connect_error) {
        // Veritabanı bağlantı hatası durumunda JSON hatası gönder
        db_die('Veritabanı bağlantı hatası: ' . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

} catch (\RuntimeException $e) {
    // .env okuma hatası durumunda JSON hatası gönder
    db_die('Sunucu yapılandırma hatası: .env dosyası bulunamadı veya okunamadı.');
} catch (\Exception $e) {
    // Diğer beklenmedik hatalar için
    db_die('Genel sunucu hatası: ' . $e->getMessage());
}

// Bu satıra ulaşıldıysa, $conn bağlantısı BAŞARILI demektir.
// Hata raporlamayı (sadece loglamak için) tekrar açabiliriz.
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ekrana basma, ama logla
ini_set('log_errors', 1);

?>