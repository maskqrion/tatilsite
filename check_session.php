<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Veritabanı bağlantısı
require 'db_config.php'; 

header('Content-Type: application/json');

$response = ['loggedin' => false];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$response['csrf_token'] = $_SESSION['csrf_token'];

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $response['loggedin'] = true;
    $response['id'] = $_SESSION['id'];
    $response['name'] = $_SESSION['name'];
    
    // YENİ: get_result() KODU bind_result() İLE GÜNCELLENDİ
    $stmt_role = $conn->prepare("SELECT rol FROM users WHERE id = ?");
    $stmt_role->bind_param("i", $_SESSION['id']);
    $stmt_role->execute();
    $stmt_role->bind_result($rol);
    $stmt_role->fetch();
    $response['rol'] = $rol ?? 'user';
    $stmt_role->close();
}

$conn->close();
echo json_encode($response);
?>