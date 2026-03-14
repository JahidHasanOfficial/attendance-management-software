<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT * FROM branches");
while($row = $stmt->fetch()) {
    print_r($row);
}
?>
