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
        setTimeout(() => {
            statusSection.classList.add('d-none');
            if(actionArea) actionArea.classList.remove('d-none');
            if(checkOutArea) checkOutArea.classList.remove('d-none');
        }, 3000);
    };

    const handleGeoError = (error) => {
        let msg = "Error: " + error.message;
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
    statusMessage.innerHTML = '<i class="bi bi-geo-fill"></i> Getting location...';

    if (navigator.geolocation) {
        const options = { 
            enableHighAccuracy: true, 
            timeout: 10000, 
            maximumAge: 0 
        };

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;

                if (accuracy < 0.5) {
                    failIntegrity("Security Alert: Artificial signal detected.");
                    return;
                }

                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
                document.getElementById('accuracy').value = accuracy;

                statusMessage.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Verified!</span>';
                form.submit();
            },
            (error) => handleGeoError(error),
            options
        );
    } else {
        statusMessage.innerHTML = 'Geolocation not supported.';
    }
}
</script>
