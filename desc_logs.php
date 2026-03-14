<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE security_logs");
while($row = $stmt->fetch()) {
    print_r($row);
}
?>
