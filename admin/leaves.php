<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin']);

$active_page = 'leaves';
$page_title = 'Company-wide Leave Management';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $leave_id = $_POST['leave_id'];
    $status = $_POST['status']; // Approved or Rejected
    
    // As Super Admin, we can bypass HOD and set final status directly if needed, 
    // but here we follow the HR logic for consistency.
    $stmt = $pdo->prepare("UPDATE leaves SET hr_status = ?, final_status = ? WHERE id = ?");
    if ($stmt->execute([$status, $status, $leave_id])) {
        header("Location: leaves.php?status=updated");
        exit();
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'updated') {
    $message = '<div class="alert alert-success">Leave request updated successfully by Super Admin.</div>';
}

// Get ALL leave requests for Admin to see
$stmt = $pdo->query("SELECT l.*, u.name as employee_name, u.designation, d.dept_name, b.branch_name 
                     FROM leaves l 
                     JOIN users u ON l.user_id = u.id 
                     JOIN departments d ON u.dept_id = d.id
                     JOIN branches b ON u.branch_id = b.id
                     ORDER BY l.created_at DESC");
$leaves = $stmt->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<h3 class="fw-bold mb-4">All Company Leave Requests</h3>

<?php echo $message; ?>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Dept/Branch</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>HOD Status</th>
                    <th>HR Status</th>
                    <th>Final</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaves)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No leave requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leaves as $leave): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($leave['employee_name']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($leave['designation']); ?></div>
                            </td>
                            <td>
                                <div class="small fw-bold"><?php echo htmlspecialchars($leave['dept_name']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($leave['branch_name']); ?></div>
                            </td>
                            <td><span class="badge bg-info"><?php echo $leave['leave_type']; ?></span></td>
                            <td>
                                <div class="small fw-bold"><?php echo date('d M Y', strtotime($leave['start_date'])); ?></div>
                                <div class="text-muted small">to <?php echo date('d M Y', strtotime($leave['end_date'])); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $leave['hod_status'] == 'Approved' ? 'success' : ($leave['hod_status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo $leave['hod_status']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $leave['hr_status'] == 'Approved' ? 'success' : ($leave['hr_status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo $leave['hr_status']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $leave['final_status'] == 'Approved' ? 'success' : ($leave['final_status'] == 'Rejected' ? 'danger' : 'warning'); ?> p-2">
                                    <?php echo $leave['final_status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($leave['final_status'] == 'Pending'): ?>
                                    <div class="d-flex gap-2">
                                        <form method="POST">
                                            <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                            <input type="hidden" name="status" value="Approved">
                                            <button type="submit" name="action" class="btn btn-sm btn-success" title="Approve as Admin">Approve</button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                            <input type="hidden" name="status" value="Rejected">
                                            <button type="submit" name="action" class="btn btn-sm btn-danger" title="Reject as Admin">Reject</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <i class="bi bi-check-all text-success"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>
