<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// All roles can view this, but the scope changes
$active_page = 'attendance';
$page_title = 'Attendance Report';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$dept_id = $_SESSION['dept_id'];

require_once '../includes/header_dashboard.php';
?>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Attendance History</h5>
        <div class="d-flex gap-2">
            <input type="date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
            <button class="btn btn-sm btn-primary">Filter</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light">
                <tr>
                    <th>Date</th>
                    <?php if ($role != 'Employee'): ?><th>Employee</th><?php endif; ?>
                    <?php if ($role != 'Employee'): ?><th>Department</th><?php endif; ?>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT a.*, u.name, d.dept_name FROM attendance a 
                          JOIN users u ON a.user_id = u.id 
                          LEFT JOIN departments d ON u.dept_id = d.id";
                
                if ($role == 'Employee') {
                    $query .= " WHERE a.user_id = $user_id";
                } elseif ($role == 'HOD') {
                    $query .= " WHERE u.dept_id = $dept_id";
                }
                
                $query .= " ORDER BY a.attendance_date DESC LIMIT 50";
                
                $stmt = $pdo->query($query);
                while($row = $stmt->fetch()):
                ?>
                <tr>
                    <td class="fw-semibold"><?php echo date('d M Y', strtotime($row['attendance_date'])); ?></td>
                    <?php if ($role != 'Employee'): ?><td><?php echo $row['name']; ?></td><?php endif; ?>
                    <?php if ($role != 'Employee'): ?><td><?php echo $row['dept_name']; ?></td><?php endif; ?>
                    <td><i class="bi bi-box-arrow-in-right text-success me-1"></i> <?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : '-'; ?></td>
                    <td><i class="bi bi-box-arrow-left text-danger me-1"></i> <?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : '-'; ?></td>
                    <td>
                        <?php
                        $badge = 'bg-secondary';
                        if ($row['status'] == 'Present') $badge = 'bg-success';
                        elseif ($row['status'] == 'Late') $badge = 'bg-warning';
                        elseif ($row['status'] == 'Absent') $badge = 'bg-danger';
                        ?>
                        <span class="badge <?php echo $badge; ?> p-2 px-3"><?php echo $row['status']; ?></span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>
