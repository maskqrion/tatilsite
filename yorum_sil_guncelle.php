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

$user_id = $_SESSION['id'];
$action = $_POST['action'] ?? '';

if ($action === 'delete') {
    $yorum_id = $_POST['id'] ?? 0;
    if ($yorum_id > 0) {
        // Yorumun gerçekten bu kullanıcıya ait olup olmadığını kontrol et
        $stmt_check = $conn->prepare("SELECT user_id FROM yorumlar WHERE id = ?");
        $stmt_check->bind_param("i", $yorum_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $yorum_sahibi = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($yorum_sahibi && $yorum_sahibi['user_id'] == $user_id) {
            $stmt_delete = $conn->prepare("DELETE FROM yorumlar WHERE id = ?");
            $stmt_delete->bind_param("i", $yorum_id);
            if ($stmt_delete->execute()) {
                echo json_encode(['success' => true, 'message' => 'Yorum başarıyla silindi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Silme sırasında bir hata oluştu.']);
            }
            $stmt_delete->close();
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bu yorumu silme yetkiniz yok.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz yorum kimliği.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
}

$conn->close();
?>