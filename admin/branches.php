<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin', 'HR']);

$active_page = 'branches';
$page_title = 'Branch Management';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_branch'])) {
    $division_id = $_POST['division_id'];
    $name = $_POST['branch_name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    try {
        $stmt = $pdo->prepare("INSERT INTO branches (division_id, branch_name, start_time, end_time) VALUES (?, ?, ?, ?)");
        $stmt->execute([$division_id, $name, $start_time, $end_time]);
        header("Location: branches?status=success");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_branch'])) {
    $id = $_POST['id'];
    $division_id = $_POST['division_id'];
    $name = $_POST['branch_name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    try {
        $stmt = $pdo->prepare("UPDATE branches SET division_id = ?, branch_name = ?, start_time = ?, end_time = ? WHERE id = ?");
        $stmt->execute([$division_id, $name, $start_time, $end_time, $id]);
        header("Location: branches?status=updated");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM branches WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: branches?status=deleted");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Cannot delete: Branch is linked to other records.</div>';
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message = '<div class="alert alert-success">Branch added successfully!</div>';
    } elseif ($_GET['status'] == 'updated') {
        $message = '<div class="alert alert-success">Branch updated successfully!</div>';
    } elseif ($_GET['status'] == 'deleted') {
        $message = '<div class="alert alert-success">Branch deleted successfully!</div>';
    }
}

$divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name ASC")->fetchAll();
$branches = $pdo->query("SELECT b.*, d.division_name FROM branches b LEFT JOIN divisions d ON b.division_id = d.id ORDER BY b.id DESC")->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4">Add New Branch</h5>
            <form method="POST">
                <input type="hidden" name="add_branch" value="1">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">DIVISION <span class="text-danger">*</span></label>
                    <select name="division_id" class="form-select" required>
                        <option value="">Select Division</option>
                        <?php foreach($divisions as $div): ?>
                            <option value="<?php echo $div['id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">BRANCH NAME <span class="text-danger">*</span></label>
                        <input type="text" name="branch_name" placeholder="Enter branch name" class="form-control" required>
                    </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small fw-bold">START TIME</label>
                        <input type="time" name="start_time" class="form-control" value="09:00" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small fw-bold">END TIME</label>
                        <input type="time" name="end_time" class="form-control" value="17:00" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Create Branch</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php echo $message; ?>
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4">Branch List</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Division</th>
                            <th>Branch Name</th>
                            <th>Office Time</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($branches as $branch): ?>
                         <tr>
                            <td>#<?php echo htmlspecialchars($branch['id']); ?></td>
                            <td><span class="badge bg-soft-primary text-primary border border-primary px-3"><?php echo htmlspecialchars($branch['division_name'] ?? 'N/A'); ?></span></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-clock me-1 text-primary"></i> 
                                    <?php 
                                    $s = $branch['start_time'];
                                    $e = $branch['end_time'];
                                    echo ($s && $s != '00:00:00') ? date('h:i A', strtotime($s)) : '---';
                                    echo ' - ';
                                    echo ($e && $e != '00:00:00') ? date('h:i A', strtotime($e)) : '---';
                                    ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?php echo date('d M Y', strtotime($branch['created_at'])); ?></td>
                            <td>
                                <!-- Edit Button -->
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $branch['id']; ?>"><i class="bi bi-pencil"></i></button>
                                <!-- Delete Button -->
                                <a href="branches?delete=<?php echo $branch['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this branch?');"><i class="bi bi-trash"></i></a>
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
<?php foreach($branches as $branch): ?>
<div class="modal fade" id="editModal<?php echo $branch['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $branch['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editModalLabel<?php echo $branch['id']; ?>">Edit Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_branch" value="1">
                    <input type="hidden" name="id" value="<?php echo $branch['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">DIVISION</label>
                        <select name="division_id" class="form-select" required>
                            <option value="">Select Division</option>
                            <?php foreach($divisions as $div): ?>
                                <option value="<?php echo $div['id']; ?>" <?php echo ($branch['division_id'] == $div['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($div['division_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">BRANCH NAME</label>
                        <input type="text" name="branch_name" class="form-control" value="<?php echo htmlspecialchars($branch['branch_name']); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted small fw-bold">START TIME</label>
                            <input type="time" name="start_time" class="form-control" value="<?php echo ($branch['start_time'] && $branch['start_time'] != '00:00:00') ? date('H:i', strtotime($branch['start_time'])) : ''; ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted small fw-bold">END TIME</label>
                            <input type="time" name="end_time" class="form-control" value="<?php echo ($branch['end_time'] && $branch['end_time'] != '00:00:00') ? date('H:i', strtotime($branch['end_time'])) : ''; ?>" required>
                        </div>
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
.bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
</style>

<?php require_once '../includes/footer_dashboard.php'; ?>
