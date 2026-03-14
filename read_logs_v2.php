<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT * FROM security_logs ORDER BY id DESC LIMIT 10");
echo "LAST 10 SECURITY LOGS:\n";
while($row = $stmt->fetch()) {
    echo "Time: " . $row['created_at'] . " | User: " . $row['user_id'] . " | Type: " . $row['attempt_type'] . "\n";
    echo "Details: " . $row['details'] . "\n";
    echo "--------------------------\n";
}
?>
