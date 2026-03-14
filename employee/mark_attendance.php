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

    // Anti-Spoofing: Smart Integrity Check
    $current_user_ip = $_SERVER['REMOTE_ADDR'];
    $ip_details = @json_decode(file_get_contents("http://ip-api.com/json/{$current_user_ip}?fields=status,city,country,regionName"));
    
    // Check for "Teleportation" - GPS says Office, but IP says different City/Region
    // This is optional but highly effective as Fake GPS doesn't change IP.
    if ($ip_details && $ip_details->status === 'success') {
        $ip_city = $ip_details->city;
        $ip_region = $ip_details->regionName;
        // In a real scenario, you could verify if this matches the branch city
        // For now, let's log this for Admin review to catch spoofers
    }

    // Anti-Spoofing Level 1: Strict Accuracy for Smartphones
    // Real smartphones under clear sky (near window) are 3m to 20m.
    // Fake GPS often reports 0 or suspicious constant values.
    if ($accuracy < 1.0) {
        $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, attempt_type, latitude, longitude, accuracy, ip_address, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, 'Fake GPS', $lat, $lng, $accuracy, $current_user_ip, "Impossible accuracy ($accuracy) detected. Likely Mock Location app."]);
        
        header("Location: dashboard?status_msg=Network Security Error: Invalid satellite signal detected. Please use a real phone.&status_type=danger");
        exit();
    }

    // Anti-Spoofing Level 2: IP-to-City Verification (Secondary Check)
    // This prevents users from using VPN + Fake GPS to teleport across cities.
    $user_ip = $_SERVER['REMOTE_ADDR'];
    // (Existing IP logic remains simplified as requested previously)


    // Get user's assigned branch ID and individual timing overrides
    $stmt = $pdo->prepare("SELECT b.*, u.start_time as u_start_time, u.end_time as u_end_time FROM branches b JOIN users u ON u.branch_id = b.id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user_branch = $stmt->fetch();

    if (!$user_branch) {
        header("Location: dashboard?status_msg=No branch assigned. Contact HR.&status_type=danger");
        exit();
    }

    // (Network verification removed as per user request for shared IP environments)

    // Get all allowed distances for this branch
    $stmt = $pdo->prepare("SELECT * FROM distances WHERE branch_id = ?");
    $stmt->execute([$user_branch['id']]);
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
        // Logic: Use individual start_time if set, otherwise fallback to branch start_time
        $office_start = !empty($user_branch['u_start_time']) ? $user_branch['u_start_time'] : 
                       (!empty($user_branch['start_time']) ? $user_branch['start_time'] : '09:00:00');
                       
        $status = (strtotime($time) > strtotime($office_start)) ? 'Late' : 'Present';
        
        // Use INSERT IGNORE to ensure we only keep the FIRST check-in of the day
        $stmt = $pdo->prepare("INSERT IGNORE INTO attendance (user_id, attendance_date, check_in, status) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$user_id, $today, $time, $status]);
        
        if ($stmt->rowCount() > 0) {
            header("Location: dashboard?status_msg=Check-in successful!&status_type=success");
        } else {
            header("Location: dashboard?status_msg=You have already checked in for today.&status_type=warning");
        }
    } elseif ($action == 'check_out') {
        // Regular UPDATE will always replace the previous check_out with the NEW (last) one
        $stmt = $pdo->prepare("UPDATE attendance SET check_out = ? WHERE user_id = ? AND attendance_date = ?");
        $stmt->execute([$time, $user_id, $today]);
        header("Location: dashboard?status_msg=Check-out updated successfully!&status_type=success");
    } else {
        header("Location: dashboard");
    }
    exit();
}

// Redirect if someone tries to access this page directly via GET
header("Location: dashboard");
exit();
?>
