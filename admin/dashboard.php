<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin']);

$active_page = 'dashboard';
$page_title = 'Super Admin Dashboard';

// Summary Stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_depts = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$today_present = $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'Present'")->fetchColumn();
$today_late = $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'Late'")->fetchColumn();

require_once '../includes/header_dashboard.php';
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stats-card primary h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-primary fw-bold text-uppercase small">Total Users</div>
                    <div class="h3 fw-bold mb-0"><?php echo $total_users; ?></div>
                </div>
                <i class="bi bi-people h1 text-gray-300"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card success h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-success fw-bold text-uppercase small">Departments</div>
                    <div class="h3 fw-bold mb-0"><?php echo $total_depts; ?></div>
                </div>
                <i class="bi bi-building h1 text-gray-300"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card info h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-info fw-bold text-uppercase small">Today Present</div>
                    <div class="h3 fw-bold mb-0"><?php echo $today_present; ?></div>
                </div>
                <i class="bi bi-calendar-check h1 text-gray-300"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card warning h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-warning fw-bold text-uppercase small">Today Late</div>
                    <div class="h3 fw-bold mb-0"><?php echo $today_late; ?></div>
                </div>
                <i class="bi bi-clock-history h1 text-gray-300"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card p-4">
            <h5 class="fw-bold mb-4">Recent Attendance Activity</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Time In</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT u.name, d.dept_name, a.check_in, a.status 
                                           FROM attendance a 
                                           JOIN users u ON a.user_id = u.id 
                                           JOIN departments d ON u.dept_id = d.id 
                                           ORDER BY a.id DESC LIMIT 5");
                        while($row = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['dept_name']; ?></td>
                            <td><?php echo $row['check_in']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['status'] == 'Present' ? 'success' : 'warning'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4">
            <h5 class="fw-bold mb-4">Quick Links</h5>
            <div class="d-grid gap-2">
                <a href="users.php" class="btn btn-outline-primary text-start"><i class="bi bi-person-plus me-2"></i> Add New User</a>
                <a href="departments.php" class="btn btn-outline-success text-start"><i class="bi bi-building-add me-2"></i> Manage Departments</a>
                <a href="attendance.php" class="btn btn-outline-info text-start"><i class="bi bi-file-earmark-bar-graph me-2"></i> Attendance Sheet</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>
