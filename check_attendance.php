<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT a.*, u.name, b.branch_name 
                    FROM attendance a 
                    JOIN users u ON a.user_id = u.id 
                    LEFT JOIN branches b ON u.branch_id = b.id
                    ORDER BY a.created_at DESC LIMIT 10");
echo "LATEST ATTENDANCE ENTRIES:\n";
echo "--------------------------\n";
while($row = $stmt->fetch()) {
    echo "ID: " . $row['id'] . " | User: " . $row['name'] . " | Branch: " . $row['branch_name'] . "\n";
    echo "Date: " . $row['attendance_date'] . " | In: " . $row['check_in'] . " | Out: " . $row['check_out'] . " | Status: " . $row['status'] . "\n";
    echo "Created: " . $row['created_at'] . "\n";
    echo "--------------------------\n";
}
?>
