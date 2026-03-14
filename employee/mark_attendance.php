<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Employee']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $time = date('H:i:s');
    $action = $_POST['action'] ?? '';
    $integrity_token = $_POST['integrity_token'] ?? '';

    if (empty($integrity_token)) {
        header("Location: dashboard?status_msg=Security Violation: Unauthorized submission attempt.&status_type=danger");
        exit();
    }

    // Location data from POST
    $lat = $_POST['latitude'] ?? 0;
    $lng = $_POST['longitude'] ?? 0;
    $accuracy = $_POST['accuracy'] ?? 0;
    $altitude = $_POST['altitude'] ?? 0;
    $speed = $_POST['speed'] ?? 0;

    // Advanced Jitter & Signal Analysis (Server-Side)
    // Real devices with < 10m accuracy almost always report some altitude and non-zero speed if handheld.
    if ($accuracy < 5.0 && $altitude == 0 && $speed == 0) {
        // This is a common signature of "High Accuracy" Mocking apps
        // Real hardware < 5m accuracy almost always has a non-zero altitude reading from GPS satellites
        $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, attempt_type, latitude, longitude, accuracy, ip_address, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, 'Fake GPS', $lat, $lng, $accuracy, $_SERVER['REMOTE_ADDR'], "Static signal signature: Acc=$accuracy, Alt=$altitude, Speed=$speed. Likely high-confidence emulation."]);
        
        // We log and block as suspicious
        header("Location: dashboard?status_msg=Security Alert: Detected high-confidence signal injection. Please use your phone's real sensors.&status_type=danger");
        exit();
    }

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
    $ip_details = null;
    
    // Skip external API for local development
    if ($current_user_ip !== '127.0.0.1' && $current_user_ip !== '::1') {
        $ctx = stream_context_create(['http' => ['timeout' => 4]]); 
        // Request lat,lon and proxy status for more advanced check
        $ip_details = @json_decode(file_get_contents("http://ip-api.com/json/{$current_user_ip}?fields=status,city,regionName,lat,lon,proxy,hosting", false, $ctx));
    }
    
    // Get user's assigned branch
    $stmt = $pdo->prepare("SELECT b.*, u.start_time as u_start_time, u.end_time as u_end_time 
                           FROM branches b 
                           JOIN users u ON u.branch_id = b.id 
                           WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user_branch = $stmt->fetch();

    if (!$user_branch) {
        header("Location: dashboard?status_msg=No branch assigned.&status_type=danger");
        exit();
    }

    // IP-to-GPS Distance Verification
    if ($ip_details && $ip_details->status === 'success') {
        $ip_lat = $ip_details->lat;
        $ip_lon = $ip_details->lon;
        
        // Calculate distance between IP reported location and GPS reported location
        $ip_gps_dist = getDistance($lat, $lng, $ip_lat, $ip_lon);
        
        // If GPS says office (Dhaka) but IP says user is > 50km away (e.g. at home in another district)
        if ($ip_gps_dist > 50000) { // 50km threshold is safe for Bangladesh ISP routing
             $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, attempt_type, latitude, longitude, accuracy, ip_address, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
             $stmt->execute([$user_id, 'Suspicious IP', $lat, $lng, $accuracy, $current_user_ip, "IP-GPS Mismatch: IP location ($ip_lat, $ip_lon) is " . round($ip_gps_dist/1000) . "km away from GPS location."]);
             
             header("Location: dashboard?status_msg=Security Alert: Your internet signal is coming from a different city. Please turn off VPN and Use Mobile Data.&status_type=danger");
             exit();
        }

        // Check for Proxy/VPN/Datacenter IPs
        if ($ip_details->proxy || $ip_details->hosting) {
             $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, attempt_type, latitude, longitude, accuracy, ip_address, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
             $stmt->execute([$user_id, 'Suspicious IP', $lat, $lng, $accuracy, $current_user_ip, "VPN/Proxy/Datacenter IP detected."]);
             
             header("Location: dashboard?status_msg=Security Alert: VPN or Proxy detected. Please use your real mobile network.&status_type=danger");
             exit();
        }
    }

    // Anti-Spoofing Level 1: Precise Accuracy Check
    // Mock apps often report exactly 1.0, 5.0, 10.0 or 0.0. Real hardware has floating point jitter.
    $is_exact_mock = in_array((float)$accuracy, [1.0, 5.0, 10.0, 15.0, 20.0]);
    if ($accuracy < 0.3 || ($is_exact_mock && $altitude == 0 && $speed == 0)) {
        $msg = ($accuracy < 0.3) ? "Impossible precision ($accuracy m)" : "Static accuracy pattern ($accuracy m)";
        $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, attempt_type, latitude, longitude, accuracy, ip_address, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, 'Fake GPS', $lat, $lng, $accuracy, $current_user_ip, "$msg detected combined with zero sensor data."]);
        
        header("Location: dashboard?status_msg=Security Error: Artificial satellite signal detected. Please disable Mock Location.&status_type=danger");
        exit();
    }

    // Reject invalid coordinates (0,0 is usually a failure)
    if ($lat == 0 && $lng == 0) {
        header("Location: dashboard?status_msg=Security Error: Your device reported an invalid location (0,0). Please refresh and try again.&status_type=danger");
        exit();
    }

    // Calculate Distance to Branch
    $branch_lat = $user_branch['latitude'];
    $branch_lng = $user_branch['longitude'];
    $dist = getDistance($lat, $lng, $branch_lat, $branch_lng);
    $allowed_radius = $user_branch['radius_meters'] ?: 100;

    if ($dist > $allowed_radius) {
        $dist_rounded = round($dist);
        $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, attempt_type, latitude, longitude, accuracy, ip_address, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, 'Out of Range', $lat, $lng, $accuracy, $_SERVER['REMOTE_ADDR'], "User ($lat, $lng) is $dist_rounded meters away from branch ($branch_lat, $branch_lng)."]);

        header("Location: dashboard?status_msg=You are too far ($dist_rounded m away) from your branch. [Detected: $lat, $lng | Branch: $branch_lat, $branch_lng]&status_type=danger");
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
