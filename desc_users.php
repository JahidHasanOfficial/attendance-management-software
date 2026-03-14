<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE users");
while($row = $stmt->fetch()) {
    print_r($row);
}
?>
