<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin', 'HR']);

$active_page = 'divisions';
$page_title = 'Division Management';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_division'])) {
    $division_name = trim($_POST['division_name']);
    try {
        $stmt = $pdo->prepare("INSERT INTO divisions (division_name) VALUES (?)");
        $stmt->execute([$division_name]);
        header("Location: divisions?status=success");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $message = '<div class="alert alert-danger">Error: This division already exists.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_division'])) {
    $id = $_POST['id'];
    $division_name = trim($_POST['division_name']);
    try {
        $stmt = $pdo->prepare("UPDATE divisions SET division_name = ? WHERE id = ?");
        $stmt->execute([$division_name, $id]);
        header("Location: divisions?status=updated");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $message = '<div class="alert alert-danger">Error: This division already exists.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM divisions WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: divisions?status=deleted");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Cannot delete: Division is linked to branches or other records.</div>';
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message = '<div class="alert alert-success">Division added successfully!</div>';
    } elseif ($_GET['status'] == 'updated') {
        $message = '<div class="alert alert-success">Division updated successfully!</div>';
    } elseif ($_GET['status'] == 'deleted') {
        $message = '<div class="alert alert-success">Division deleted successfully!</div>';
    }
}

$divisions = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM branches b WHERE b.division_id = d.id) as branch_count FROM divisions d")->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<?php echo $message; ?>

<div class="row g-4 d-flex">
    <div class="col-md-4">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4">Add Division</h5>
            <form method="POST">
                <input type="hidden" name="add_division" value="1">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">DIVISION NAME</label>
                    <input type="text" name="division_name" class="form-control" required placeholder="e.g. Dhaka Division, Chittagong Division">
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Add Division</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4">Division List</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Division Name</th>
                            <th>No. of Branches</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($divisions as $div): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($div['id']); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($div['division_name']); ?></td>
                            <td><span class="badge bg-soft-info text-info border border-info px-3"><?php echo htmlspecialchars($div['branch_count']); ?> Branches</span></td>
                            <td>
                                <!-- Edit Button -->
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $div['id']; ?>"><i class="bi bi-pencil"></i></button>
                                
                                <!-- Delete Button -->
                                <a href="divisions?delete=<?php echo $div['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this division? This will only work if no branches are linked.');"><i class="bi bi-trash"></i></a>
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
<?php foreach($divisions as $div): ?>
<div class="modal fade" id="editModal<?php echo $div['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $div['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editModalLabel<?php echo $div['id']; ?>">Edit Division</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_division" value="1">
                    <input type="hidden" name="id" value="<?php echo $div['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">DIVISION NAME</label>
                        <input type="text" name="division_name" class="form-control" value="<?php echo htmlspecialchars($div['division_name']); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<style>
.bg-soft-info { background-color: rgba(13, 202, 240, 0.1); }
</style>

<?php require_once '../includes/footer_dashboard.php'; ?>
