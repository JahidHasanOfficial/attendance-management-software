<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin', 'HR']);

$active_page = 'departments';
$page_title = 'Department Management';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_dept'])) {
    $dept_name = $_POST['dept_name'];
    try {
        $stmt = $pdo->prepare("INSERT INTO departments (dept_name) VALUES (?)");
        $stmt->execute([$dept_name]);
        header("Location: departments.php?status=success");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $message = '<div class="alert alert-success">Department added successfully!</div>';
}

$depts = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM users u WHERE u.dept_id = d.id) as emp_count FROM departments d")->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<?php echo $message; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card p-4">
            <h5 class="fw-bold mb-4">Add Department</h5>
            <form method="POST">
                <input type="hidden" name="add_dept" value="1">
                <div class="mb-3">
                    <label class="form-label">Department Name</label>
                    <input type="text" name="dept_name" class="form-control" required placeholder="e.g. Sales, Development">
                </div>
                <button type="submit" class="btn btn-primary w-100">Add Department</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card p-4">
            <h5 class="fw-bold mb-4">Department List</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Department Name</th>
                            <th>No. of Employees</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($depts as $dept): ?>
                        <tr>
                            <td><?php echo $dept['id']; ?></td>
                            <td><?php echo $dept['dept_name']; ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo $dept['emp_count']; ?> Employees</span></td>
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
