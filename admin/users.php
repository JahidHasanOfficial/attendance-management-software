<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin', 'HR']);

$active_page = 'users';
$page_title = 'User Management';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $employee_id = $_POST['employee_id'];
    $role_id = $_POST['role_id'];
    $dept_id = $_POST['dept_id'];
    $designation_id = $_POST['designation_id'];
    $phone = $_POST['phone'];
    $branch_id = $_POST['branch_id'];
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $status = $_POST['status'] ?? 'Active';

    try {
        $stmt = $pdo->prepare("INSERT INTO users (employee_id, name, email, password, role_id, dept_id, designation_id, branch_id, start_time, end_time, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$employee_id, $name, $email, $password, $role_id, $dept_id, $designation_id, $branch_id, $start_time, $end_time, $phone, $status]);
        header("Location: users?status=success");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $msg = $e->getMessage();
            if (strpos($msg, 'email') !== false) {
                $message = '<div class="alert alert-danger">Error: This Email is already in use.</div>';
            } elseif (strpos($msg, 'phone') !== false) {
                $message = '<div class="alert alert-danger">Error: This Phone Number is already registered.</div>';
            } elseif (strpos($msg, 'employee_id') !== false) {
                $message = '<div class="alert alert-danger">Error: This Employee ID is already taken.</div>';
            } else {
                $message = '<div class="alert alert-danger">Error: A unique value constraint was violated.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $employee_id = $_POST['employee_id'];
    $role_id = $_POST['role_id'];
    $dept_id = $_POST['dept_id'];
    $designation_id = $_POST['designation_id'];
    $branch_id = $_POST['branch_id'];
    $phone = $_POST['phone'];
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $status = $_POST['status'];

    try {
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET employee_id = ?, name = ?, email = ?, password = ?, role_id = ?, dept_id = ?, designation_id = ?, branch_id = ?, start_time = ?, end_time = ?, phone = ?, status = ? WHERE id = ?");
            $stmt->execute([$employee_id, $name, $email, $password, $role_id, $dept_id, $designation_id, $branch_id, $start_time, $end_time, $phone, $status, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET employee_id = ?, name = ?, email = ?, role_id = ?, dept_id = ?, designation_id = ?, branch_id = ?, start_time = ?, end_time = ?, phone = ?, status = ? WHERE id = ?");
            $stmt->execute([$employee_id, $name, $email, $role_id, $dept_id, $designation_id, $branch_id, $start_time, $end_time, $phone, $status, $id]);
        }
        header("Location: users?status=updated");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $msg = $e->getMessage();
            if (strpos($msg, 'email') !== false) {
                $message = '<div class="alert alert-danger">Error: This Email is already in use by another user.</div>';
            } elseif (strpos($msg, 'phone') !== false) {
                $message = '<div class="alert alert-danger">Error: This Phone Number is already registered.</div>';
            } elseif (strpos($msg, 'employee_id') !== false) {
                $message = '<div class="alert alert-danger">Error: This Employee ID is already taken.</div>';
            } else {
                $message = '<div class="alert alert-danger">Error: A unique value constraint was violated.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: users?status=deleted");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Cannot delete: User has attendance or leave records.</div>';
    }
}

if (isset($_GET['status_msg'])) {
    if ($_GET['status_msg'] == 'success') {
        $message = '<div class="alert alert-success">User added successfully!</div>';
    } elseif ($_GET['status_msg'] == 'updated') {
        $message = '<div class="alert alert-success">User updated successfully!</div>';
    } elseif ($_GET['status_msg'] == 'deleted') {
        $message = '<div class="alert alert-success">User deleted successfully!</div>';
    }
}

// Fixing the status param to status_msg to avoid conflict with user status
if (isset($_GET['status']) && !in_array($_GET['status'], ['Active', 'Inactive'])) {
    header("Location: users?status_msg=" . $_GET['status']);
    exit();
}

$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
$depts = $pdo->query("SELECT * FROM departments")->fetchAll();
$branches = $pdo->query("SELECT * FROM branches")->fetchAll();
$designations = $pdo->query("SELECT * FROM designations")->fetchAll();

$users = $pdo->query("SELECT u.*, r.role_name, d.dept_name, b.branch_name, dg.designation_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.id 
                    LEFT JOIN departments d ON u.dept_id = d.id 
                    LEFT JOIN branches b ON u.branch_id = b.id
                    LEFT JOIN designations dg ON u.designation_id = dg.id
                    ORDER BY u.id DESC")->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<?php echo $message; ?>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4">Add New User</h5>
            <form method="POST">
                <input type="hidden" name="add_user" value="1">
                <div class="mb-3">
                    <label class="form-label">Employee ID</label>
                    <input type="text" name="employee_id" class="form-control" required placeholder="e.g. EMP-1001">
                </div>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. John Doe" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="e.g. john.doe@example.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="e.g. password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-select" required>
                        <?php foreach($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo $role['role_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Department</label>
                    <select name="dept_id" class="form-select" required>
                        <?php foreach($depts as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo $dept['dept_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Designation</label>
                    <select name="designation_id" class="form-select" required>
                        <option value="">Select Designation</option>
                        <?php foreach($designations as $dg): ?>
                            <option value="<?php echo $dg['id']; ?>"><?php echo $dg['designation_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select" required>
                        <?php foreach($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>"><?php echo $branch['branch_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-primary small fw-bold">Phone Number</label>
                    <input type="text" name="phone" class="form-control" required placeholder="e.g. 017xxxxxxxx">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label text-danger small fw-bold">START TIME (OPTIONAL)</label>
                        <input type="time" name="start_time" class="form-control">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-danger small fw-bold">END TIME (OPTIONAL)</label>
                        <input type="time" name="end_time" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Create User</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4">User List</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Emp ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Dept/Designation</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($user['employee_id'] ?? 'N/A'); ?></span></td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($user['email']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($user['phone']); ?></div>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['role_name']); ?></span></td>
                            <td>
                            <td>
                                <div class="small fw-bold"><?php echo htmlspecialchars($user['dept_name'] ?? 'N/A'); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($user['designation_name'] ?? 'N/A'); ?></div>
                                <?php if ($user['start_time']): ?>
                                    <div class="badge bg-soft-warning text-dark mt-1" style="font-size: 0.7rem;">
                                        <i class="bi bi-clock"></i> <?php echo date('h:i A', strtotime($user['start_time'])); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['status'] == 'Active' ? 'success' : 'danger'; ?>">
                                    <?php echo $user['status']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $user['id']; ?>"><i class="bi bi-pencil"></i></button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="users?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modals -->
<?php foreach($users as $user): ?>
<div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?php echo $user['id']; ?>">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_user" value="1">
                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($user['employee_id'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role_id" class="form-select" required>
                            <?php foreach($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo ($user['role_id'] == $role['id']) ? 'selected' : ''; ?>><?php echo $role['role_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="dept_id" class="form-select" required>
                            <?php foreach($depts as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo ($user['dept_id'] == $dept['id']) ? 'selected' : ''; ?>><?php echo $dept['dept_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Designation</label>
                        <select name="designation_id" class="form-select" required>
                            <option value="">Select Designation</option>
                            <?php foreach($designations as $dg): ?>
                                <option value="<?php echo $dg['id']; ?>" <?php echo ($user['designation_id'] == $dg['id']) ? 'selected' : ''; ?>><?php echo $dg['designation_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select" required>
                            <?php foreach($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ($user['branch_id'] == $branch['id']) ? 'selected' : ''; ?>><?php echo $branch['branch_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-primary small fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-danger small fw-bold">START TIME (OPTIONAL)</label>
                            <input type="time" name="start_time" class="form-control" value="<?php echo ($user['start_time'] && $user['start_time'] != '00:00:00') ? date('H:i', strtotime($user['start_time'])) : ''; ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label text-danger small fw-bold">END TIME (OPTIONAL)</label>
                            <input type="time" name="end_time" class="form-control" value="<?php echo ($user['end_time'] && $user['end_time'] != '00:00:00') ? date('H:i', strtotime($user['end_time'])) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" <?php echo ($user['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($user['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php require_once '../includes/footer_dashboard.php'; ?>
