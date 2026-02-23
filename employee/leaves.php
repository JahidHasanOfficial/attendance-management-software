<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Employee']);

$active_page = 'leaves';
$page_title = 'My Leave Requests';
$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_leave'])) {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    $stmt = $pdo->prepare("INSERT INTO leaves (user_id, leave_type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $leave_type, $start_date, $end_date, $reason])) {
        header("Location: leaves.php?status=success");
        exit();
    } else {
        $message = '<div class="alert alert-danger">Failed to submit leave request.</div>';
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $message = '<div class="alert alert-success">Leave request submitted successfully! Pending HOD approval.</div>';
}

$stmt = $pdo->prepare("SELECT * FROM leaves WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$leaves = $stmt->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">My Leave History</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
        <i class="bi bi-plus-lg me-1"></i> Apply for Leave
    </button>
</div>

<?php echo $message; ?>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Reason</th>
                    <th>HOD Status</th>
                    <th>HR Status</th>
                    <th>Final Status</th>
                    <th>Applied On</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaves)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No leave requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leaves as $leave): ?>
                        <tr>
                            <td><span class="badge bg-info"><?php echo $leave['leave_type']; ?></span></td>
                            <td>
                                <div class="small fw-bold"><?php echo date('d M Y', strtotime($leave['start_date'])); ?></div>
                                <div class="text-muted small">to <?php echo date('d M Y', strtotime($leave['end_date'])); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($leave['reason']); ?></td>
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
                            <td class="small text-muted"><?php echo date('d M Y', strtotime($leave['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Apply Leave Modal -->
<div class="modal fade" id="applyLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Apply for Leave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Leave Type</label>
                        <select name="leave_type" class="form-select" required>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Casual Leave">Casual Leave</option>
                            <option value="Emergency Leave">Emergency Leave</option>
                            <option value="Maternity/Paternity Leave">Maternity/Paternity Leave</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="State your reason for leave..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="apply_leave" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
