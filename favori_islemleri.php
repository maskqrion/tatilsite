<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.', 'status' => 'unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT rota_id FROM favoriler WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $favoriler = [];
        while ($row = $stmt->fetch()) {
            $favoriler[] = $row['rota_id'];
        }

        echo json_encode(['success' => true, 'favoriler' => $favoriler]);
    } catch (PDOException $e) {
        error_log('favori_islemleri.php GET hatası: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Favoriler yüklenirken bir hata oluştu.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
        exit;
    }

    $rota_id = $_POST['rota_id'] ?? '';
    if ($rota_id === '') {
        echo json_encode(['success' => false, 'message' => 'Rota kimliği boş olamaz.']);
        exit;
    }

    try {
        // Mevcut favori kontrolü (fetch ile)
        $stmt = $pdo->prepare("SELECT id FROM favoriler WHERE user_id = ? AND rota_id = ?");
        $stmt->execute([$user_id, $rota_id]);
        $existing = $stmt->fetch();

        if ($existing !== false) {
            $stmt_delete = $pdo->prepare("DELETE FROM favoriler WHERE user_id = ? AND rota_id = ?");
            $stmt_delete->execute([$user_id, $rota_id]);
            echo json_encode(['success' => true, 'action' => 'removed']);
        } else {
            $stmt_insert = $pdo->prepare("INSERT INTO favoriler (user_id, rota_id) VALUES (?, ?)");
            $stmt_insert->execute([$user_id, $rota_id]);
            echo json_encode(['success' => true, 'action' => 'added']);
        }
    } catch (PDOException $e) {
        error_log('favori_islemleri.php POST hatası: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Favori işlemi sırasında bir hata oluştu.']);
    }
    exit;
}
?>
