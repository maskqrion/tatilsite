<?php
// Hata raporlama: ekrana basma, loga yaz
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

/**
 * JSON hata yanıtı döndürüp scripti sonlandırır.
 */
function db_die($message) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * .env dosyasını okuyup $_ENV'ye yükler.
 */
function loadEnv($path) {
    if (!is_readable($path)) {
        throw new \RuntimeException(sprintf('%s dosyası okunamıyor.', $path));
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value, " \n\r\t\v\0\"");
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
        }
    }
}

/**
 * Singleton PDO bağlantısı döndürür.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        loadEnv(__DIR__ . '/.env');
    } catch (\RuntimeException $e) {
        db_die('Sunucu yapılandırma hatası: .env dosyası bulunamadı veya okunamadı.');
    }

    $host = $_ENV['DB_HOST']     ?? 'localhost';
    $db   = $_ENV['DB_DATABASE'] ?? 'tatilsite';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASSWORD'] ?? '';

    $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    } catch (\PDOException $e) {
        error_log('DB Bağlantı Hatası: ' . $e->getMessage());
        db_die('Veritabanı bağlantı hatası oluştu.');
    }

    return $pdo;
}

// Geriye uyumluluk: mevcut dosyalar $pdo kullanacak
$pdo = getDB();
