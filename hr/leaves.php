<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['HR', 'Super Admin']);

$active_page = 'leaves';
$page_title = 'Leave Approval Management';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $leave_id = $_POST['leave_id'];
    $status = $_POST['status']; // Approved or Rejected
    
    $final_status = $status;

    $stmt = $pdo->prepare("UPDATE leaves SET hr_status = ?, final_status = ? WHERE id = ?");
    if ($stmt->execute([$status, $final_status, $leave_id])) {
        header("Location: leaves.php?status=updated");
        exit();
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'updated') {
    $message = '<div class="alert alert-success">Leave request updated successfully.</div>';
}

// Get leave requests that are approved by HOD and pending HR approval
$stmt = $pdo->query("SELECT l.*, u.name as employee_name, u.designation, d.dept_name 
                     FROM leaves l 
                     JOIN users u ON l.user_id = u.id 
                     JOIN departments d ON u.dept_id = d.id
                     WHERE l.hod_status = 'Approved'
                     ORDER BY l.created_at DESC");
$leaves = $stmt->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<h3 class="fw-bold mb-4">Leave Approval Management (HR)</h3>

<?php echo $message; ?>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Dept</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>HOD Status</th>
                    <th>HR Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaves)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No pending leave requests for HR approval.</td></tr>
                <?php else: ?>
                    <?php foreach ($leaves as $leave): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($leave['employee_name']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($leave['designation']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($leave['dept_name']); ?></td>
                            <td><span class="badge bg-info"><?php echo $leave['leave_type']; ?></span></td>
                            <td>
                                <div class="small fw-bold"><?php echo date('d M Y', strtotime($leave['start_date'])); ?></div>
                                <div class="text-muted small">to <?php echo date('d M Y', strtotime($leave['end_date'])); ?></div>
                            </td>
                            <td><span class="badge bg-success"><?php echo $leave['hod_status']; ?></span></td>
                            <td>
                                <span class="badge bg-<?php echo $leave['hr_status'] == 'Approved' ? 'success' : ($leave['hr_status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo $leave['hr_status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($leave['hr_status'] == 'Pending'): ?>
                                    <div class="d-flex gap-2">
                                        <form method="POST">
                                            <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                            <input type="hidden" name="status" value="Approved">
                                            <button type="submit" name="action" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                            <input type="hidden" name="status" value="Rejected">
                                            <button type="submit" name="action" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Processed</span>
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
