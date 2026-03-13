<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin', 'HR']);

$active_page = 'designations';
$page_title = 'Designation Management';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_designation'])) {
    $designation_name = trim($_POST['designation_name']);
    try {
        $stmt = $pdo->prepare("INSERT INTO designations (designation_name) VALUES (?)");
        $stmt->execute([$designation_name]);
        header("Location: designations?status=success");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $message = '<div class="alert alert-danger">Error: This designation already exists.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_designation'])) {
    $id = $_POST['id'];
    $designation_name = trim($_POST['designation_name']);
    try {
        $stmt = $pdo->prepare("UPDATE designations SET designation_name = ? WHERE id = ?");
        $stmt->execute([$designation_name, $id]);
        header("Location: designations?status=updated");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $message = '<div class="alert alert-danger">Error: This designation already exists.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM designations WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: designations?status=deleted");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Cannot delete: Designation is linked to other records.</div>';
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message = '<div class="alert alert-success">Designation added successfully!</div>';
    } elseif ($_GET['status'] == 'updated') {
        $message = '<div class="alert alert-success">Designation updated successfully!</div>';
    } elseif ($_GET['status'] == 'deleted') {
        $message = '<div class="alert alert-success">Designation deleted successfully!</div>';
    }
}

$designations = $pdo->query("SELECT dg.*, (SELECT COUNT(*) FROM users u WHERE u.designation_id = dg.id) as emp_count FROM designations dg")->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<?php echo $message; ?>

<div class="row g-4 d-flex">
    <div class="col-md-4">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4">Add Designation</h5>
            <form method="POST">
                <input type="hidden" name="add_designation" value="1">
                <div class="mb-3">
                    <label class="form-label">Designation Name</label>
                    <input type="text" name="designation_name" class="form-control" required placeholder="e.g. Senior Developer, HR Manager">
                </div>
                <button type="submit" class="btn btn-primary w-100">Add Designation</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4">Designation List</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Designation Name</th>
                            <th>No. of Employees</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($designations as $dg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dg['id']); ?></td>
                            <td><?php echo htmlspecialchars($dg['designation_name']); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($dg['emp_count']); ?> Employees</span></td>
                            <td>
                                <!-- Edit Button -->
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $dg['id']; ?>"><i class="bi bi-pencil"></i></button>
                                
                                <!-- Delete Button -->
                                <a href="designations?delete=<?php echo $dg['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this designation?');"><i class="bi bi-trash"></i></a>
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
<?php foreach($designations as $dg): ?>
<div class="modal fade" id="editModal<?php echo $dg['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $dg['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?php echo $dg['id']; ?>">Edit Designation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_designation" value="1">
                    <input type="hidden" name="id" value="<?php echo $dg['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Designation Name</label>
                        <input type="text" name="designation_name" class="form-control" value="<?php echo htmlspecialchars($dg['designation_name']); ?>" required>
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
