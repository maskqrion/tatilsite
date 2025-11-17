<?php
session_start();
require 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.', 'status' => 'unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // bind_result Düzeltmesi
    $stmt = $conn->prepare("SELECT rota_id FROM favoriler WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($rota_id);
    
    $favoriler = [];
    while($stmt->fetch()) {
        $favoriler[] = $rota_id;
    }
    
    echo json_encode(['success' => true, 'favoriler' => $favoriler]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (empty($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik anahtarı.']);
        exit;
    }

    $rota_id = $_POST['rota_id'] ?? '';
    if (empty($rota_id)) {
        echo json_encode(['success' => false, 'message' => 'Rota kimliği boş olamaz.']);
        exit;
    }

    // bind_result Düzeltmesi (store_result ile)
    $stmt = $conn->prepare("SELECT id FROM favoriler WHERE user_id = ? AND rota_id = ?");
    $stmt->bind_param("is", $user_id, $rota_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close(); 
        $stmt_delete = $conn->prepare("DELETE FROM favoriler WHERE user_id = ? AND rota_id = ?");
        $stmt_delete->bind_param("is", $user_id, $rota_id);
        $stmt_delete->execute();
        echo json_encode(['success' => true, 'action' => 'removed']);
        $stmt_delete->close();
    } else {
        $stmt->close();
        $stmt_insert = $conn->prepare("INSERT INTO favoriler (user_id, rota_id) VALUES (?, ?)");
        $stmt_insert->bind_param("is", $user_id, $rota_id);
        $stmt_insert->execute();
        echo json_encode(['success' => true, 'action' => 'added']);
        $stmt_insert->close();
    }
    
    $conn->close();
    exit;
}
?>