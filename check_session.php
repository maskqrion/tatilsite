<?php
if (session_status() === PHP_SESSION_NONE) {
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

    // Kullanıcı rolünü veritabanından al
    $stmt_role = $pdo->prepare("SELECT rol FROM users WHERE id = :id");
    $stmt_role->execute([':id' => $_SESSION['id']]);
    $row = $stmt_role->fetch();
    $response['rol'] = $row['rol'] ?? 'user';
}

echo json_encode($response);
?>
