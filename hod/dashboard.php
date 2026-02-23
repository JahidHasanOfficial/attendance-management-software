<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['HOD']);

$active_page = 'dashboard';
$page_title = 'Department Head Dashboard';
$dept_id = $_SESSION['dept_id'];

// Get Dept Name
$stmt = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ?");
$stmt->execute([$dept_id]);
$dept_name = $stmt->fetchColumn();

require_once '../includes/header_dashboard.php';
?>

<div class="alert alert-info py-3 shadow-sm border-0 mb-4">
    <i class="bi bi-info-circle-fill me-2"></i> Managing Department: <strong><?php echo $dept_name; ?></strong>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card p-4 h-100">
            <h5 class="fw-bold mb-4">My Team (<?php echo $dept_name; ?>)</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status Today</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT u.name, a.status FROM users u 
                                             LEFT JOIN attendance a ON u.id = a.user_id AND a.attendance_date = CURDATE()
                                             WHERE u.dept_id = ? AND u.role_id = (SELECT id FROM roles WHERE role_name = 'Employee')");
                        $stmt->execute([$dept_id]);
                        while($row = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><?php echo $row['name']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['status'] == 'Present' ? 'success' : ($row['status'] == 'Late' ? 'warning' : 'secondary'); ?>">
                                    <?php echo $row['status'] ?? 'Not Checked In'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card p-4 h-100">
            <h5 class="fw-bold mb-4">Leave Requests</h5>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-file-earmark-text h1 mb-3 d-block opacity-25"></i>
                <p>No pending leave requests for your department.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>
