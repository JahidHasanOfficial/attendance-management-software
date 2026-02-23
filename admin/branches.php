<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin', 'HR']);

$active_page = 'branches';
$page_title = 'Branch Management';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_branch'])) {
    $name = $_POST['branch_name'];
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    $radius = $_POST['radius_meters'];

    try {
        $stmt = $pdo->prepare("INSERT INTO branches (branch_name, latitude, longitude, radius_meters) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $lat, $lng, $radius]);
        header("Location: branches.php?status=success");
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $message = '<div class="alert alert-success">Branch added successfully!</div>';
}

$branches = $pdo->query("SELECT * FROM branches ORDER BY id DESC")->fetchAll();

require_once '../includes/header_dashboard.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card p-4">
            <h5 class="fw-bold mb-4">Add New Branch</h5>
            <form method="POST">
                <input type="hidden" name="add_branch" value="1">
                <div class="mb-3">
                    <label class="form-label">Branch Name</label>
                    <input type="text" name="branch_name" class="form-control" required placeholder="e.g. Dhaka HQ">
                </div>
                <div class="mb-3">
                    <label class="form-label">Latitude</label>
                    <input type="number" step="any" name="latitude" class="form-control" required placeholder="e.g. 23.8103">
                </div>
                <div class="mb-3">
                    <label class="form-label">Longitude</label>
                    <input type="number" step="any" name="longitude" class="form-control" required placeholder="e.g. 90.4125">
                </div>
                <div class="mb-3">
                    <label class="form-label">Allowed Radius (Meters)</label>
                    <input type="number" name="radius_meters" class="form-control" value="500" required>
                    <small class="text-muted">Employees must be within this distance to mark attendance.</small>
                </div>
                <button type="submit" class="btn btn-primary w-100">Create Branch</button>
            </form>
            
            <hr class="my-4">
            <div class="alert alert-info py-2">
                <small><i class="bi bi-info-circle me-1"></i> Tip: Use Google Maps to find Latitude and Longitude.</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php echo $message; ?>
        <div class="card p-4">
            <h5 class="fw-bold mb-4">Branch List</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Branch Name</th>
                            <th>Coordinates</th>
                            <th>Radius</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($branches as $branch): ?>
                        <tr>
                            <td class="fw-bold"><?php echo $branch['branch_name']; ?></td>
                            <td>
                                <span class="badge bg-light text-dark"><?php echo $branch['latitude']; ?>, <?php echo $branch['longitude']; ?></span>
                            </td>
                            <td><?php echo $branch['radius_meters']; ?>m</td>
                            <td class="small text-muted"><?php echo date('d M Y', strtotime($branch['created_at'])); ?></td>
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
