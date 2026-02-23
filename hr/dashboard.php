<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['HR', 'Super Admin']);

$active_page = 'dashboard';
$page_title = 'HR Dashboard';

// HR Specific View
$total_employees = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Employee')")->fetchColumn();
$recent_leaves = 0; // Placeholder for leave management

require_once '../includes/header_dashboard.php';
?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stats-card primary h-100 p-3 text-center">
            <h6 class="text-primary fw-bold">TOTAL EMPLOYEES</h6>
            <div class="h2 fw-bold"><?php echo $total_employees; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card success h-100 p-3 text-center">
            <h6 class="text-success fw-bold">PRESENT TODAY</h6>
            <div class="h2 fw-bold">12</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card danger h-100 p-3 text-center">
            <h6 class="text-danger fw-bold">ON LEAVE</h6>
            <div class="h2 fw-bold">2</div>
        </div>
    </div>
</div>

<div class="card p-4">
    <h5 class="fw-bold mb-4">Employee Status overview</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Today Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT u.name, d.dept_name, a.status 
                                   FROM users u 
                                   LEFT JOIN departments d ON u.dept_id = d.id 
                                   LEFT JOIN attendance a ON u.id = a.user_id AND a.attendance_date = CURDATE()
                                   WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'Employee')");
                while($row = $stmt->fetch()):
                ?>
                <tr>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['dept_name']; ?></td>
                    <td>
                        <span class="badge bg-<?php echo $row['status'] == 'Present' ? 'success' : ($row['status'] == 'Late' ? 'warning' : 'secondary'); ?>">
                            <?php echo $row['status'] ?? 'Not Checked In'; ?>
                        </span>
                    </td>
                    <td><button class="btn btn-sm btn-outline-primary">View History</button></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>
