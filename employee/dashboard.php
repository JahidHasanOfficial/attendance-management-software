<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Employee']);

$active_page = 'dashboard';
$page_title = 'Employee Dashboard';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Check today's attendance status
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND attendance_date = ?");
$stmt->execute([$user_id, $today]);
$today_attendance = $stmt->fetch();

// Personal Stats
$total_present = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ? AND status = 'Present'");
$total_present->execute([$user_id]);
$total_present = $total_present->fetchColumn();

$status_msg = $_GET['status_msg'] ?? '';
$status_type = $_GET['status_type'] ?? 'info';

require_once '../includes/header_dashboard.php';
?>

<?php if ($status_msg): ?>
    <div class="alert alert-<?php echo htmlspecialchars($status_type); ?> alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi <?php echo $status_type == 'danger' ? 'bi-exclamation-octagon-fill' : 'bi-check-circle-fill'; ?> fs-4 me-3"></i>
            <div><?php echo htmlspecialchars($status_msg); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-3 g-md-4 mb-4">
    <div class="col-lg-8">
        <div class="card p-3 p-md-4 h-100 shadow-sm border-0">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4">
                <h5 class="fw-bold mb-2 mb-sm-0 text-primary"><i class="bi bi-clock-fill me-2"></i>Daily Attendance</h5>
                <span class="text-muted fw-semibold small bg-light px-3 py-1 rounded-pill"><?php echo date('l, d M Y'); ?></span>
            </div>

            <div class="bg-light p-4 p-md-5 rounded-4 text-center border">
                <?php if (!$today_attendance): ?>
                    <div id="attendanceActionArea">
                        <div class="mb-4">
                            <i class="bi bi-geo-alt-fill text-primary display-4"></i>
                            <h4 class="mt-3 fw-bold">Ready to Start?</h4>
                            <p class="text-muted">Stay within your branch area to mark attendance.</p>
                        </div>
                        <button type="button" onclick="markAttendance('check_in')" class="btn btn-primary btn-lg px-5 py-3 rounded-pill fw-bold shadow">
                            <i class="bi bi-box-arrow-in-right me-2"></i> CHECK IN NOW
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row g-3 mb-4" id="attendanceDisplay">
                        <div class="col-6 border-end">
                            <div class="h3 fw-bold text-success mb-0"><?php echo date('h:i A', strtotime($today_attendance['check_in'])); ?></div>
                            <div class="text-uppercase small fw-bold text-muted">Check In</div>
                        </div>
                        <div class="col-6">
                            <div class="h3 fw-bold text-danger mb-0"><?php echo $today_attendance['check_out'] ? date('h:i A', strtotime($today_attendance['check_out'])) : '--:--'; ?></div>
                            <div class="text-uppercase small fw-bold text-muted">Check Out</div>
                        </div>
                    </div>
                    
                    <div id="checkOutArea">
                        <button type="button" onclick="markAttendance('check_out')" class="btn btn-warning btn-lg px-5 py-3 rounded-pill fw-bold shadow text-white">
                            <i class="bi bi-box-arrow-left me-2"></i> CHECK OUT NOW
                        </button>
                    </div>
                <?php endif; ?>

                <div id="statusSection" class="mt-4 d-none">
                    <div id="loadingSpinner" class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div id="statusMessage" class="mt-2 fw-semibold text-primary small">Acquiring precise location...</div>
                </div>

                <form id="attendanceForm" action="mark_attendance" method="POST">
                    <input type="hidden" name="action" id="attendanceAction">
                    <input type="hidden" name="latitude" id="lat">
                    <input type="hidden" name="longitude" id="lng">
                    <input type="hidden" name="accuracy" id="accuracy">
                    <input type="hidden" name="altitude" id="altitude">
                    <input type="hidden" name="speed" id="speed">
                    <input type="hidden" name="integrity_token" id="integrity_token">
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card p-3 p-md-4 h-100 shadow-sm border-0">
            <h5 class="fw-bold mb-4">Performance Summary</h5>
            <div class="row g-3">
                <div class="col-12">
                    <div class="p-3 bg-primary bg-opacity-10 rounded-4 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-primary fw-bold text-uppercase">Total Present</div>
                            <div class="h3 fw-bold mb-0"><?php echo $total_present; ?> <small class="h6">Days</small></div>
                        </div>
                        <i class="bi bi-calendar-check fs-1 text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-light rounded-4 border border-dashed text-center">
                <small class="text-muted d-block mb-1">Assigned Branch</small>
                <div class="fw-bold text-dark">
                    <?php
                    $stmt = $pdo->prepare("SELECT b.branch_name FROM branches b JOIN users u ON u.branch_id = b.id WHERE u.id = ?");
                    $stmt->execute([$user_id]);
                    echo htmlspecialchars($stmt->fetchColumn() ?: 'Not Assigned');
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card p-3 p-md-4 shadow-sm border-0">
    <h5 class="fw-bold mb-4"><i class="bi bi-list-task me-2 text-primary"></i>Recent Logs</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 rounded-start">Date</th>
                    <th class="border-0">Check In</th>
                    <th class="border-0">Check Out</th>
                    <th class="border-0 text-end rounded-end">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY attendance_date DESC LIMIT 10");
                $stmt->execute([$user_id]);
                while($row = $stmt->fetch()):
                ?>
                <tr>
                    <td class="fw-semibold"><?php echo date('d M Y', strtotime($row['attendance_date'])); ?></td>
                    <td><span class="text-success me-1">☀️</span> <?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : '-'; ?></td>
                    <td><span class="text-danger me-1">🌙</span> <?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : '-'; ?></td>
                    <td class="text-end">
                        <span class="badge rounded-pill bg-<?php echo $row['status'] == 'Present' ? 'success' : 'warning'; ?> px-3 py-2">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>

<script>
function markAttendance(action) {
    const statusSection = document.getElementById('statusSection');
    const statusMessage = document.getElementById('statusMessage');
    const form = document.getElementById('attendanceForm');
    
    document.getElementById('attendanceAction').value = action;
    
    // UI state
    const actionArea = document.getElementById('attendanceActionArea');
    const checkOutArea = document.getElementById('checkOutArea');
    
    const failIntegrity = (reason) => {
        statusMessage.innerHTML = `<span class="text-danger small"><i class="bi bi-exclamation-triangle-fill"></i> ${reason}</span>`;
        console.error("Security Block:", reason);
        setTimeout(() => {
            statusSection.classList.add('d-none');
            if(actionArea) actionArea.classList.remove('d-none');
            if(checkOutArea) checkOutArea.classList.remove('d-none');
        }, 5000);
    };

    const handleGeoError = (error) => {
        let msg = "Location Error: " + error.message;
        statusMessage.innerHTML = `<span class="text-danger small">${msg}</span>`;
        setTimeout(() => {
            statusSection.classList.add('d-none');
            if(actionArea) actionArea.classList.remove('d-none');
            if(checkOutArea) checkOutArea.classList.remove('d-none');
        }, 3000);
    };

    if(actionArea) actionArea.classList.add('d-none');
    if(checkOutArea) checkOutArea.classList.add('d-none');
    
    statusSection.classList.remove('d-none');
    statusMessage.innerHTML = '<i class="bi bi-shield-lock"></i> Initializing Secure Geo-Link...';

    if (navigator.geolocation) {
        const options = { 
            enableHighAccuracy: true, 
            timeout: 25000, 
            maximumAge: 0 
        };

        // 0. Automation Check
        if (navigator.webdriver) {
            failIntegrity("Security Alert: Automated browser environment detected.");
            return;
        }

        let readings = [];
        let watchId = navigator.geolocation.watchPosition(
            (position) => {
                const coords = position.coords;
                
                // 1. Filter invalid uninitialized signal (0,0)
                if (coords.latitude === 0 && coords.longitude === 0) {
                    statusMessage.innerHTML = `<i class="bi bi-satellite-fill"></i> Waiting for valid GPS lock...`;
                    return;
                }

                // 2. Android/Browser Mock Check (Various implementations)
                const isMocked = coords.mocked || position.mocked || (position.raw && position.raw.mocked);
                
                if (isMocked === true) {
                    navigator.geolocation.clearWatch(watchId);
                    failIntegrity("Security Alert: Developer Mock Location Detected.");
                    return;
                }

                readings.push({
                    lat: coords.latitude,
                    lng: coords.longitude,
                    accuracy: coords.accuracy,
                    altitude: coords.altitude,
                    speed: coords.speed,
                    timestamp: position.timestamp
                });

                statusMessage.innerHTML = `<i class="bi bi-satellite"></i> Authenticating Signal (${readings.length}/5)...`;

                if (readings.length >= 5) {
                    navigator.geolocation.clearWatch(watchId);
                    
                    // 2. Hardware Jitter Analysis (Anti-Programmatic)
                    const uniqueLats = new Set(readings.map(r => r.lat)).size;
                    const uniqueLngs = new Set(readings.map(r => r.lng)).size;
                    
                    // If 5 readings are EXACTLY identical, it's 100% a mock app injecting coordinates
                    if (uniqueLats === 1 && uniqueLngs === 1) {
                        failIntegrity("Security Alert: Static signal detected. Please use real GPS.");
                        return;
                    }

                    // 3. Programmatic Jitter Pattern Check
                    if (readings.length > 3) {
                        let stepSizes = [];
                        for(let i=1; i<readings.length; i++) {
                            stepSizes.push((readings[i].lat - readings[i-1].lat).toFixed(8));
                        }
                        const uniqueSteps = new Set(stepSizes).size;
                        if (uniqueSteps === 1 && stepSizes[0] !== "0.00000000") {
                            failIntegrity("Security Alert: Synthetic movement signature detected.");
                            return;
                        }
                    }

                    // 4. Verification & Submission
                    const final = readings[readings.length-1];
                    document.getElementById('lat').value = final.lat;
                    document.getElementById('lng').value = final.lng;
                    document.getElementById('accuracy').value = final.accuracy;
                    document.getElementById('altitude').value = final.altitude || 0;
                    document.getElementById('speed').value = final.speed || 0;
                    document.getElementById('integrity_token').value = btoa(Date.now() + "_" + Math.random());

                    statusMessage.innerHTML = '<span class="text-success small"><i class="bi bi-check-circle-fill"></i> Secure Link Verified!</span>';
                    setTimeout(() => form.submit(), 1000);
                }
            },
            (error) => {
                navigator.geolocation.clearWatch(watchId);
                handleGeoError(error);
            },
            options
        );

        // Fail-safe
        setTimeout(() => {
            navigator.geolocation.clearWatch(watchId);
            if (readings.length > 0 && readings.length < 5) {
                const last = readings[readings.length-1];
                
                // Set values
                document.getElementById('lat').value = last.lat;
                document.getElementById('lng').value = last.lng;
                document.getElementById('accuracy').value = last.accuracy;
                document.getElementById('altitude').value = last.altitude || 0;
                document.getElementById('speed').value = last.speed || 0;
                document.getElementById('integrity_token').value = btoa(Date.now() + "_" + Math.random());

                if (last.accuracy < 1.0) {
                    failIntegrity("Security Alert: Unstable artificial signal.");
                } else {
                    statusMessage.innerHTML = '<span class="text-success small"><i class="bi bi-check-circle-fill"></i> Verified via Satellite!</span>';
                    setTimeout(() => form.submit(), 1000);
                }
            } else if (readings.length === 0) {
                statusMessage.innerHTML = '<span class="text-danger small">No GPS Lock. Move to an outdoor area.</span>';
                setTimeout(() => {
                    statusSection.classList.add('d-none');
                    if(actionArea) actionArea.classList.remove('d-none');
                    if(checkOutArea) checkOutArea.classList.remove('d-none');
                }, 3000);
            }
        }, 22000);

    } else {
        statusMessage.innerHTML = 'Geolocation not supported.';
    }
}
</script>
