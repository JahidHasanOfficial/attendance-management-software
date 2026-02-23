<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['HOD']);

$active_page = 'leaves';
$page_title = 'Department Leave Requests';
$dept_id = $_SESSION['dept_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $leave_id = $_POST['leave_id'];
    $status = $_POST['status']; // Approved or Rejected
    
    $final_status = ($status == 'Rejected') ? 'Rejected' : 'Pending';

    $stmt = $pdo->prepare("UPDATE leaves SET hod_status = ?, final_status = ? WHERE id = ?");
    if ($stmt->execute([$status, $final_status, $leave_id])) {
        header("Location: leaves.php?status=updated");
        exit();
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'updated') {
    $message = '<div class="alert alert-success">Leave request updated successfully.</div>';
}

// Get leave requests for the HOD's department (excluding the HOD themselves)
$stmt = $pdo->prepare("SELECT l.*, u.name as employee_name, u.designation 
                       FROM leaves l 
                       JOIN users u ON l.user_id = u.id 
                       WHERE u.dept_id = ? AND l.user_id != ?
                       ORDER BY l.created_at DESC");
$stmt->execute([$dept_id, $_SESSION['user_id']]);
$leaves = $stmt->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<h3 class="fw-bold mb-4">Department Leave Requests</h3>

<?php echo $message; ?>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Reason</th>
                    <th>HOD Status</th>
                    <th>HR Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaves)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No leave requests found for your department.</td></tr>
                <?php else: ?>
                    <?php foreach ($leaves as $leave): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($leave['employee_name']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($leave['designation']); ?></div>
                            </td>
                            <td><span class="badge bg-info"><?php echo $leave['leave_type']; ?></span></td>
                            <td>
                                <div class="small fw-bold"><?php echo date('d M Y', strtotime($leave['start_date'])); ?></div>
                                <div class="text-muted small">to <?php echo date('d M Y', strtotime($leave['end_date'])); ?></div>
                            </td>
                            <td><small><?php echo htmlspecialchars($leave['reason']); ?></small></td>
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
                                <?php if ($leave['hod_status'] == 'Pending'): ?>
                                    <div class="d-flex gap-2">
                                        <form method="POST">
                                            <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                            <input type="hidden" name="status" value="Approved">
                                            <button type="submit" name="action" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                            <input type="hidden" name="status" value="Rejected">
                                            <button type="submit" name="action" class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">No action required</span>
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
