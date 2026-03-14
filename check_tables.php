<?php
require_once 'config/db.php';
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in attendance_db:\n";
    foreach($tables as $t) echo "- $t\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
