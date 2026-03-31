<?php
require 'db_config.php';

echo "<h1>Kullanıcı Listesi</h1>";

try {
    $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();

    if (!empty($users)) {
        echo "<table border='1'>
                <tr><th>ID</th><th>İsim</th><th>E-posta</th><th>Kayıt Tarihi</th></tr>";
        foreach ($users as $row) {
            echo "<tr>
                    <td>" . htmlspecialchars($row["id"]) . "</td>
                    <td>" . htmlspecialchars($row["name"]) . "</td>
                    <td>" . htmlspecialchars($row["email"]) . "</td>
                    <td>" . htmlspecialchars($row["created_at"]) . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "Veritabanında hiç kullanıcı bulunamadı.";
    }
} catch (PDOException $e) {
    error_log('user_connection hatası: ' . $e->getMessage());
    echo "Veritabanı hatası oluştu.";
}
?>
