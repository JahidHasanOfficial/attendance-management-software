<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

$active_page = 'attendance';
$page_title = 'Attendance Report';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$dept_id = $_SESSION['dept_id'];

// Get Search/Filter Params
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to start of month
$end_date = $_GET['end_date'] ?? date('Y-m-d');

require_once '../includes/header_dashboard.php';
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end mb-4">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">START DATE</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">END DATE</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="bi bi-filter me-2"></i> Filter Report
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle border-top">
                <thead class="bg-light">
                    <tr>
                        <th>Date</th>
                        <?php if ($role != 'Employee'): ?><th>Employee</th><?php endif; ?>
                        <th>Office Hours</th>
                        <th>Duty Time</th>
                        <th>Late/Early</th>
                        <th>Total Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Dynamic query to get office times (User override or Branch default)
                    $query = "SELECT a.*, u.name, d.dept_name, 
                              COALESCE(NULLIF(u.start_time, '00:00:00'), NULLIF(b.start_time, '00:00:00'), '09:00:00') as effective_start,
                              COALESCE(NULLIF(u.end_time, '00:00:00'), NULLIF(b.end_time, '00:00:00'), '17:00:00') as effective_end
                              FROM attendance a 
                              JOIN users u ON a.user_id = u.id 
                              LEFT JOIN branches b ON u.branch_id = b.id
                              LEFT JOIN departments d ON u.dept_id = d.id
                              WHERE a.attendance_date BETWEEN ? AND ?";
                    
                    $params = [$start_date, $end_date];

                    if ($role == 'Employee') {
                        $query .= " AND a.user_id = ?";
                        $params[] = $user_id;
                    } elseif ($role == 'HOD') {
                        $query .= " AND u.dept_id = ?";
                        $params[] = $dept_id;
                    }
                    
                    $query .= " ORDER BY a.attendance_date DESC";
                    
                    $stmt = $pdo->prepare($query);
                    $stmt->execute($params);
                    
                    while($row = $stmt->fetch()):
                        // Time Calculations
                        $office_start = strtotime($row['effective_start']);
                        $office_end = strtotime($row['effective_end']);
                        $check_in = strtotime($row['check_in']);
                        $check_out = $row['check_out'] ? strtotime($row['check_out']) : null;

                        // 1. Late Calculation
                        $late_minutes = 0;
                        if ($check_in > $office_start) {
                            $late_minutes = floor(($check_in - $office_start) / 60);
                        }

                        // 2. Exit Analytics: Early or Extra
                        $early_exit_minutes = 0;
                        $extra_time_minutes = 0;
                        if ($check_out) {
                            if ($check_out < $office_end) {
                                $early_exit_minutes = floor(($office_end - $check_out) / 60);
                            } elseif ($check_out > $office_end) {
                                $extra_time_minutes = floor(($check_out - $office_end) / 60);
                            }
                        }

                        // 3. Total Hours Worked
                        $total_hours_text = "--";
                        if ($check_out) {
                            $diff = $check_out - $check_in;
                            $h = floor($diff / 3600);
                            $m = floor(($diff % 3600) / 60);
                            $total_hours_text = "{$h}h {$m}m";
                        }
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?php echo date('d M Y', strtotime($row['attendance_date'])); ?></div>
                            <div class="small text-muted"><?php echo date('l', strtotime($row['attendance_date'])); ?></div>
                        </td>
                        <?php if ($role != 'Employee'): ?>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($row['dept_name']); ?></div>
                        </td>
                        <?php endif; ?>
                        
                        <!-- Office Schedule -->
                        <td class="small">
                            <span class="text-primary fw-bold"><?php echo date('h:i A', $office_start); ?></span><br>
                            <span class="text-danger fw-bold"><?php echo date('h:i A', $office_end); ?></span>
                        </td>

                        <!-- Actual Duty Time -->
                        <td>
                            <div class="small text-success"><i class="bi bi-box-arrow-in-right"></i> <?php echo date('h:i A', $check_in); ?></div>
                            <div class="small text-danger"><i class="bi bi-box-arrow-left"></i> <?php echo $check_out ? date('h:i A', $check_out) : 'In Office'; ?></div>
                        </td>

                        <!-- Late/Early/Extra Analytics -->
                        <td>
                            <?php if ($late_minutes > 0): 
                                $lh = floor($late_minutes / 60);
                                $lm = $late_minutes % 60;
                                $late_text = ($lh > 0 ? "{$lh}h " : "") . "{$lm}m";
                            ?>
                                <div class="badge bg-soft-danger text-danger mb-1">
                                    <i class="bi bi-clock-history"></i> Late: <?php echo $late_text; ?>
                                </div><br>
                            <?php endif; ?>
                            
                            <?php if ($early_exit_minutes > 0): 
                                $eh = floor($early_exit_minutes / 60);
                                $em = $early_exit_minutes % 60;
                                $early_text = ($eh > 0 ? "{$eh}h " : "") . "{$em}m";
                            ?>
                                <div class="badge bg-soft-warning text-dark">
                                    <i class="bi bi-door-open"></i> Early Out: <?php echo $early_text; ?>
                                </div>
                            <?php elseif ($extra_time_minutes > 0): 
                                $xh = floor($extra_time_minutes / 60);
                                $xm = $extra_time_minutes % 60;
                                $extra_text = ($xh > 0 ? "{$xh}h " : "") . "{$xm}m";
                            ?>
                                <div class="badge bg-soft-info text-info">
                                    <i class="bi bi-plus-circle"></i> Extra: <?php echo $extra_text; ?>
                                </div>
                            <?php elseif ($check_out && $check_out >= $office_end): ?>
                                <div class="badge bg-soft-success text-success">
                                    <i class="bi bi-check-circle"></i> On Time Exit
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Total Working Hours -->
                        <td>
                            <div class="fw-bold text-dark"><?php echo $total_hours_text; ?></div>
                        </td>

                        <!-- Status Badge -->
                        <td>
                            <?php
                            $badge = 'bg-secondary';
                            if ($row['status'] == 'Present') $badge = 'bg-success';
                            elseif ($row['status'] == 'Late') $badge = 'bg-warning text-dark';
                            elseif ($row['status'] == 'Absent') $badge = 'bg-danger';
                            ?>
                            <span class="badge <?php echo $badge; ?> rounded-pill px-3"><?php echo $row['status']; ?></span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); }
.bg-soft-warning { background-color: rgba(255, 193, 7, 0.1); }
.bg-soft-success { background-color: rgba(25, 135, 84, 0.1); }
.table > :not(caption) > * > * { padding: 1rem 0.75rem; }
</style>

<?php require_once '../includes/footer_dashboard.php'; ?>
