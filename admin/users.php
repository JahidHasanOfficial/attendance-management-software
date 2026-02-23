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
    $role_id = $_POST['role_id'];
    $dept_id = $_POST['dept_id'];
    $designation = $_POST['designation'];
    $phone = $_POST['phone'];
    $branch_id = $_POST['branch_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id, dept_id, branch_id, designation, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role_id, $dept_id, $branch_id, $designation, $phone]);
        header("Location: users.php?status=success");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $message = '<div class="alert alert-success">User added successfully!</div>';
}

$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
$depts = $pdo->query("SELECT * FROM departments")->fetchAll();
$branches = $pdo->query("SELECT * FROM branches")->fetchAll();
$users = $pdo->query("SELECT u.*, r.role_name, d.dept_name, b.branch_name FROM users u 
                    JOIN roles r ON u.role_id = r.id 
                    LEFT JOIN departments d ON u.dept_id = d.id 
                    LEFT JOIN branches b ON u.branch_id = b.id
                    ORDER BY u.id DESC")->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<?php echo $message; ?>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card p-4">
            <h5 class="fw-bold mb-4">Add New User</h5>
            <form method="POST">
                <input type="hidden" name="add_user" value="1">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
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
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select" required>
                        <?php foreach($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>"><?php echo $branch['branch_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" placeholder="e.g. Software Engineer">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="e.g. 017xxxxxxxx">
                </div>
                <button type="submit" class="btn btn-primary w-100">Create User</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card p-4">
            <h5 class="fw-bold mb-4">User List</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Designation</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Dept</th>
                            <th>Branch</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['name']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['designation']; ?></td>
                            <td><?php echo $user['phone']; ?></td>
                            <td><span class="badge bg-secondary"><?php echo $user['role_name']; ?></span></td>
                            <td><?php echo $user['dept_name'] ?? 'N/A'; ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo $user['branch_name'] ?? 'N/A'; ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-info text-white"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>
