<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

// Kullanıcı giriş yapmamışsa yetki hatası ver
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.']);
    exit;
}

// CSRF kontrolü
if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
    exit;
}

$user_id = $_SESSION['id'];
$action = $_POST['action'] ?? '';

if ($action === 'delete') {
    $yorum_id = (int)($_POST['id'] ?? 0);
    if ($yorum_id > 0) {
        try {
            // Yorumun gerçekten bu kullanıcıya ait olup olmadığını kontrol et
            $stmt_check = $pdo->prepare("SELECT user_id FROM yorumlar WHERE id = ?");
            $stmt_check->execute([$yorum_id]);
            $yorum_sahibi = $stmt_check->fetch();

            if ($yorum_sahibi && (int)$yorum_sahibi['user_id'] === (int)$user_id) {
                $stmt_delete = $pdo->prepare("DELETE FROM yorumlar WHERE id = ?");
                $stmt_delete->execute([$yorum_id]);
                echo json_encode(['success' => true, 'message' => 'Yorum başarıyla silindi.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bu yorumu silme yetkiniz yok.']);
            }
        } catch (Exception $e) {
            error_log('yorum_sil_guncelle hatası: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Silme sırasında bir hata oluştu.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz yorum kimliği.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
}
?>
