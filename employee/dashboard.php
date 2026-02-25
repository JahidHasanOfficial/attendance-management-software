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

require_once '../includes/header_dashboard.php';
?>

<div class="row g-3 g-md-4 mb-4">
    <div class="col-lg-8">
        <div class="card p-3 p-md-4 h-100">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4">
                <h5 class="fw-bold mb-2 mb-sm-0">Today's Attendance</h5>
                <span class="text-muted fw-semibold small"><?php echo date('l, d M Y'); ?></span>
            </div>

            <div class="bg-light p-3 p-md-5 rounded text-center">
                <?php if (!$today_attendance): ?>
                    <form id="attendanceForm" action="mark_attendance.php" method="POST">
                        <input type="hidden" name="action" value="check_in">
                        <input type="hidden" name="latitude" id="lat">
                        <input type="hidden" name="longitude" id="lng">
                        <button type="button" onclick="getLocation()" class="btn btn-primary btn-lg px-4 px-md-5 py-3 rounded-pill fw-bold shadow-sm w-100 w-sm-auto">
                            <i class="bi bi-box-arrow-in-right me-2"></i> CHECK IN NOW
                        </button>
                    </form>
                    <p id="locationStatus" class="mt-3 text-muted small">Click the button to check in from your branch.</p>
                <?php elseif ($today_attendance && !$today_attendance['check_out']): ?>
                    <div class="mb-4">
                        <div class="display-5 fw-bold text-success mb-0"><?php echo date('h:i A', strtotime($today_attendance['check_in'])); ?></div>
                        <div class="text-uppercase small fw-bold text-muted">Check In Time</div>
                    </div>
                    <form action="mark_attendance.php" method="POST">
                        <input type="hidden" name="action" value="check_out">
                        <button type="submit" class="btn btn-warning btn-lg px-4 px-md-5 py-3 rounded-pill fw-bold shadow-sm text-white w-100 w-sm-auto">
                            <i class="bi bi-box-arrow-left me-2"></i> CHECK OUT NOW
                        </button>
                    </form>
                <?php else: ?>
                    <div class="row g-2">
                        <div class="col-6 border-end">
                            <div class="h4 h2-md fw-bold text-success mb-0"><?php echo date('h:i A', strtotime($today_attendance['check_in'])); ?></div>
                            <div class="text-uppercase x-small fw-bold text-muted">Check In</div>
                        </div>
                        <div class="col-6">
                            <div class="h4 h2-md fw-bold text-danger mb-0"><?php echo date('h:i A', strtotime($today_attendance['check_out'])); ?></div>
                            <div class="text-uppercase x-small fw-bold text-muted">Check Out</div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="badge bg-success p-2 px-3"><i class="bi bi-check-circle-fill me-1"></i> Shift Completed</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card p-3 p-md-4 h-100">
            <h5 class="fw-bold mb-4">Your Summary</h5>
            <div class="row g-3">
                <div class="col-12">
                    <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted fw-bold">TOTAL PRESENT</div>
                            <div class="h4 fw-bold mb-0"><?php echo $total_present; ?> Days</div>
                        </div>
                        <i class="bi bi-calendar-check fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted fw-bold">LATE ARRIVALS</div>
                            <div class="h4 fw-bold mb-0">3 Days</div>
                        </div>
                        <i class="bi bi-clock-history fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card p-3 p-md-4">
    <h5 class="fw-bold mb-4">Recent Attendance Logs</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th class="text-end">Status</th>
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
                    <td><span class="text-success small"><i class="bi bi-box-arrow-in-right me-1"></i></span> <?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : '-'; ?></td>
                    <td><span class="text-danger small"><i class="bi bi-box-arrow-left me-1"></i></span> <?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : '-'; ?></td>
                    <td class="text-end">
                        <span class="badge rounded-pill bg-<?php echo $row['status'] == 'Present' ? 'success' : 'warning'; ?> px-3">
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
function getLocation() {
    const status = document.getElementById('locationStatus');
    const form = document.getElementById('attendanceForm');
    
    if (navigator.geolocation) {
        status.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Detecting your location...';
        navigator.geolocation.getCurrentPosition(
            (position) => {
                document.getElementById('lat').value = position.coords.latitude;
                document.getElementById('lng').value = position.coords.longitude;
                form.submit();
            },
            (error) => {
                let msg = "Error: ";
                switch(error.code) {
                    case error.PERMISSION_DENIED: msg += "Location permission denied."; break;
                    case error.POSITION_UNAVAILABLE: msg += "Location info unavailable."; break;
                    case error.TIMEOUT: msg += "Location request timed out."; break;
                    default: msg += "Unknown error.";
                }
                status.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${msg}</span>`;
            }
        );
    } else {
        status.innerHTML = '<span class="text-danger">Geolocation is not supported by this browser.</span>';
    }
}
</script>
