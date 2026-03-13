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
        header("Location: departments?status=success");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_dept'])) {
    $id = $_POST['id'];
    $dept_name = $_POST['dept_name'];
    try {
        $stmt = $pdo->prepare("UPDATE departments SET dept_name = ? WHERE id = ?");
        $stmt->execute([$dept_name, $id]);
        header("Location: departments?status=updated");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: departments?status=deleted");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Cannot delete: Department is linked to other records.</div>';
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message = '<div class="alert alert-success">Department added successfully!</div>';
    } elseif ($_GET['status'] == 'updated') {
        $message = '<div class="alert alert-success">Department updated successfully!</div>';
    } elseif ($_GET['status'] == 'deleted') {
        $message = '<div class="alert alert-success">Department deleted successfully!</div>';
    }
}

$depts = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM users u WHERE u.dept_id = d.id) as emp_count FROM departments d")->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<?php echo $message; ?>

<div class="row g-4 d-flex">
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
                <table class="table table-hover align-middle">
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
                            <td><?php echo htmlspecialchars($dept['id']); ?></td>
                            <td><?php echo htmlspecialchars($dept['dept_name']); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($dept['emp_count']); ?> Employees</span></td>
                            <td>
                                <!-- Edit Button -->
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $dept['id']; ?>"><i class="bi bi-pencil"></i></button>
                                
                                <!-- Delete Button -->
                                <a href="departments?delete=<?php echo $dept['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this department?');"><i class="bi bi-trash"></i></a>
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
<?php foreach($depts as $dept): ?>
<div class="modal fade" id="editModal<?php echo $dept['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $dept['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?php echo $dept['id']; ?>">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_dept" value="1">
                    <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" name="dept_name" class="form-control" value="<?php echo htmlspecialchars($dept['dept_name']); ?>" required>
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
