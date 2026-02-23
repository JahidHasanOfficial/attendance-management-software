<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Employee']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $time = date('H:i:s');
    $action = $_POST['action'];

    if ($action == 'check_in') {
        $lat = $_POST['latitude'] ?? 0;
        $lng = $_POST['longitude'] ?? 0;

        // Get user's assigned branch location
        $stmt = $pdo->prepare("SELECT b.* FROM branches b JOIN users u ON u.branch_id = b.id WHERE u.id = ?");
        $stmt->execute([$user_id]);
        $branch = $stmt->fetch();

        if (!$branch) {
            die("Error: No branch assigned. Contact HR.");
        }

        // Calculate distance using Haversine Formula
        function getDistance($lat1, $lon1, $lat2, $lon2) {
            $earth_radius = 6371000; // in meters
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            return $earth_radius * $c;
        }

        $distance = getDistance($lat, $lng, $branch['latitude'], $branch['longitude']);

        if ($distance > $branch['radius_meters']) {
            die("Error: You are too far from your branch (" . round($distance) . "m away). Required distance: " . $branch['radius_meters'] . "m.");
        }

        // Logic: If after 09:30 AM, mark as Late
        $status = (strtotime($time) > strtotime('09:30:00')) ? 'Late' : 'Present';
        
        $stmt = $pdo->prepare("INSERT INTO attendance (user_id, attendance_date, check_in, status) VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE check_in = VALUES(check_in)");
        $stmt->execute([$user_id, $today, $time, $status]);
    } elseif ($action == 'check_out') {
        $stmt = $pdo->prepare("UPDATE attendance SET check_out = ? WHERE user_id = ? AND attendance_date = ?");
        $stmt->execute([$time, $user_id, $today]);
    }

    header("Location: dashboard.php");
    exit();
}
?>
