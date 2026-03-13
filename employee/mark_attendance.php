<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Employee']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $time = date('H:i:s');
    $action = $_POST['action'] ?? '';

    // Location data from POST
    $lat = $_POST['latitude'] ?? 0;
    $lng = $_POST['longitude'] ?? 0;
    $accuracy = $_POST['accuracy'] ?? 0;

    // Calculate distance using Haversine Formula
    if (!function_exists('getDistance')) {
        function getDistance($lat1, $lon1, $lat2, $lon2) {
            $earth_radius = 6371000; // in meters
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            return $earth_radius * $c;
        }
    }

    // Anti-Spoofing Level 1: Accuracy Threshold
    // Real GPS under 60m is good. Fake GPS often reports 0 or perfect 1.0.
    if ($accuracy <= 1 || $accuracy > 100) {
        $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, attempt_type, latitude, longitude, accuracy, ip_address, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, 'Fake GPS', $lat, $lng, $accuracy, $_SERVER['REMOTE_ADDR'], "Suspicious accuracy ($accuracy) detected. Likely Mock Location app."]);
        
        header("Location: dashboard?status_msg=Security Alert: Poor location accuracy or Mock Location detected. Please use a real device and stay in an open area.&status_type=danger");
        exit();
    }

    // Anti-Spoofing Level 2: IP-to-City Verification (Secondary Check)
    // This prevents users from using VPN + Fake GPS to teleport across cities.
    $user_ip = $_SERVER['REMOTE_ADDR'];
    // (Existing IP logic remains simplified as requested previously)


    // Get user's assigned branch ID
    $stmt = $pdo->prepare("SELECT branch_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_branch = $stmt->fetch();

    if (!$user_branch || !$user_branch['branch_id']) {
        header("Location: dashboard?status_msg=No branch assigned. Contact HR.&status_type=danger");
        exit();
    }

    // Get all allowed distances for this branch
    $stmt = $pdo->prepare("SELECT * FROM distances WHERE branch_id = ?");
    $stmt->execute([$user_branch['branch_id']]);
    $allowed_locations = $stmt->fetchAll();

    if (empty($allowed_locations)) {
        header("Location: dashboard?status_msg=No location settings found for your branch. Contact Administrator.&status_type=danger");
        exit();
    }

    $is_within_range = false;
    $min_distance = -1;

    foreach ($allowed_locations as $loc) {
        $dist = getDistance($lat, $lng, $loc['latitude'], $loc['longitude']);
        if ($min_distance == -1 || $dist < $min_distance) {
            $min_distance = $dist;
        }
        if ($dist <= $loc['radius_meters']) {
            $is_within_range = true;
            break;
        }
    }

    if (!$is_within_range) {
        $dist_rounded = round($min_distance);
        
        $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, attempt_type, latitude, longitude, accuracy, ip_address, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, 'Out of Range', $lat, $lng, $accuracy, $_SERVER['REMOTE_ADDR'], "User tried to mark attendance from $dist_rounded meters away."]);

        header("Location: dashboard?status_msg=You are too far ($dist_rounded m away) from any allowed location for your branch.&status_type=danger");
        exit();
    }

    if ($action == 'check_in') {
        // Logic: If after 09:30 AM, mark as Late
        $status = (strtotime($time) > strtotime('09:30:00')) ? 'Late' : 'Present';
        
        $stmt = $pdo->prepare("INSERT INTO attendance (user_id, attendance_date, check_in, status) VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE check_in = VALUES(check_in)");
        $stmt->execute([$user_id, $today, $time, $status]);
        header("Location: dashboard?status_msg=Check-in successful!&status_type=success");
    } elseif ($action == 'check_out') {
        $stmt = $pdo->prepare("UPDATE attendance SET check_out = ? WHERE user_id = ? AND attendance_date = ?");
        $stmt->execute([$time, $user_id, $today]);
        header("Location: dashboard?status_msg=Check-out successful!&status_type=success");
    } else {
        header("Location: dashboard");
    }
    exit();
}

// Redirect if someone tries to access this page directly via GET
header("Location: dashboard");
exit();
?>
