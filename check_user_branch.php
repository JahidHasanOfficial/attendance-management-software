<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT u.name, b.branch_name, b.latitude as b_lat, b.longitude as b_lng, b.radius_meters 
                    FROM users u 
                    LEFT JOIN branches b ON u.branch_id = b.id 
                    WHERE u.id = 1 OR u.role_id = 4 LIMIT 5");
while($row = $stmt->fetch()) {
    print_r($row);
}
?>
