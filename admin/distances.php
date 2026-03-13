<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin', 'HR']);

$active_page = 'distances';
$page_title = 'Distance & Location Settings';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_distance'])) {
    $branch_id = $_POST['branch_id'];
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    $radius = $_POST['radius_meters'];

    try {
        $stmt = $pdo->prepare("INSERT INTO distances (branch_id, latitude, longitude, radius_meters) VALUES (?, ?, ?, ?)");
        $stmt->execute([$branch_id, $lat, $lng, $radius]);
        header("Location: distances?status=success");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_distance'])) {
    $id = $_POST['id'];
    $branch_id = $_POST['branch_id'];
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    $radius = $_POST['radius_meters'];

    try {
        $stmt = $pdo->prepare("UPDATE distances SET branch_id = ?, latitude = ?, longitude = ?, radius_meters = ? WHERE id = ?");
        $stmt->execute([$branch_id, $lat, $lng, $radius, $id]);
        header("Location: distances?status=updated");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM distances WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: distances?status=deleted");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message = '<div class="alert alert-success">Distance setting added successfully!</div>';
    } elseif ($_GET['status'] == 'updated') {
        $message = '<div class="alert alert-success">Distance setting updated successfully!</div>';
    } elseif ($_GET['status'] == 'deleted') {
        $message = '<div class="alert alert-success">Distance setting deleted successfully!</div>';
    }
}

$branches = $pdo->query("SELECT * FROM branches ORDER BY branch_name ASC")->fetchAll();
$distances = $pdo->query("SELECT dist.*, b.branch_name FROM distances dist JOIN branches b ON dist.branch_id = b.id ORDER BY dist.id DESC")->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4">Add Distance Setting</h5>
            <form method="POST">
                <input type="hidden" name="add_distance" value="1">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SELECT BRANCH <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select" required>
                        <option value="">Select Branch</option>
                        <?php foreach($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">LATITUDE <span class="text-danger">*</span></label>
                    <input type="number" step="any" name="latitude" class="form-control" required placeholder="e.g. 23.8103">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">LONGITUDE <span class="text-danger">*</span></label>
                    <input type="number" step="any" name="longitude" class="form-control" required placeholder="e.g. 90.4125">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">RADIUS (METERS) <span class="text-danger">*</span></label>
                    <input type="number" name="radius_meters" class="form-control" value="500" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Save Settings</button>
            </form>
            <div class="alert alert-info py-2 mt-4">
                <small><i class="bi bi-info-circle me-1"></i> Tip: Use Google Maps to find Latitude and Longitude.</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php echo $message; ?>
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4">Distance Settings List</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Coordinates</th>
                            <th>Radius</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($distances as $dist): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($dist['branch_name']); ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($dist['latitude']); ?>, <?php echo htmlspecialchars($dist['longitude']); ?></span>
                            </td>
                            <td><span class="fw-bold text-success"><?php echo $dist['radius_meters']; ?>m</span></td>
                            <td>
                                <!-- Edit Button -->
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $dist['id']; ?>"><i class="bi bi-pencil"></i></button>
                                <!-- Delete Button -->
                                <a href="distances?delete=<?php echo $dist['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');"><i class="bi bi-trash"></i></a>
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
<?php foreach($distances as $dist): ?>
<div class="modal fade" id="editModal<?php echo $dist['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $dist['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editModalLabel<?php echo $dist['id']; ?>">Edit Distance Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_distance" value="1">
                    <input type="hidden" name="id" value="<?php echo $dist['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">BRANCH</label>
                        <select name="branch_id" class="form-select" required>
                            <?php foreach($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ($dist['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">LATITUDE</label>
                        <input type="number" step="any" name="latitude" class="form-control" value="<?php echo $dist['latitude']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">LONGITUDE</label>
                        <input type="number" step="any" name="longitude" class="form-control" value="<?php echo $dist['longitude']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">RADIUS (METERS)</label>
                        <input type="number" name="radius_meters" class="form-control" value="<?php echo $dist['radius_meters']; ?>" required>
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

<?php require_once '../includes/footer_dashboard.php'; ?>
