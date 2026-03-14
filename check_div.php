<?php
require_once 'config/db.php';
echo "DIVISIONS:\n";
$stmt = $pdo->query("SELECT * FROM divisions");
while($row = $stmt->fetch()) {
    print_r($row);
}
echo "\nBRANCHES:\n";
$stmt = $pdo->query("SELECT b.*, d.division_name FROM branches b JOIN divisions d ON b.division_id = d.id");
while($row = $stmt->fetch()) {
    print_r($row);
}
?>
