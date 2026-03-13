<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin']);

$active_page = 'profile';
$page_title = 'User Profile';
$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    $image_update_query = "";
    $params = [$name, $phone];
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            $new_filename = "profile_" . $user_id . "_" . time() . "." . $ext;
            $upload_path = "../uploads/profiles/";
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path . $new_filename)) {
                $image_update_query = ", face_image = ?";
                $params[] = $new_filename;
            } else {
                $message = '<div class="alert alert-danger">Failed to upload image.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Invalid image format. Only JPG, PNG, and GIF are allowed.</div>';
        }
    }
    
    if (empty($message)) {
        $password_update_query = "";
        if (!empty($password)) {
            $password_update_query = ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $params[] = $user_id;
        
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? $image_update_query $password_update_query WHERE id = ?");
        if ($stmt->execute($params)) {
             header("Location: profile?status=updated");
             exit();
        } else {
            $message = '<div class="alert alert-danger">Failed to update profile.</div>';
        }
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'updated') {
    $message = '<div class="alert alert-success">Profile updated successfully!</div>';
}

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

require_once '../includes/header_dashboard.php';
?>

<div class="row g-4 d-flex justify-content-center">
    <div class="col-md-4">
        <div class="card p-4 text-center shadow-sm">
            <?php if (!empty($user['face_image']) && file_exists("../uploads/profiles/" . $user['face_image'])): ?>
                <img src="../uploads/profiles/<?php echo htmlspecialchars($user['face_image']); ?>" class="rounded-circle mb-3 mx-auto shadow" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #fff;">
            <?php else: ?>
                <div class="bg-light rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center shadow-sm" style="width: 150px; height: 150px; border: 4px solid #fff;">
                    <i class="bi bi-person-fill text-secondary" style="font-size: 5rem;"></i>
                </div>
            <?php endif; ?>
            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
            <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['designation'] ?? 'Admin'); ?></p>
            <div>
                <span class="badge bg-primary px-3 py-2 rounded-pill"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-4 border-bottom pb-2">Edit Profile</h5>
            <?php echo $message; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">NAME <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">EMAIL</label>
                        <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        <small class="text-muted" style="font-size: 0.75rem;">Email cannot be changed.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">PHONE</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">NEW PASSWORD</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                    </div>
                    <div class="col-12 mt-4">
                        <label class="form-label text-muted small fw-bold">PROFILE IMAGE</label>
                        <input type="file" name="profile_image" class="form-control" accept="image/png, image/jpeg, image/gif">
                        <small class="text-muted" style="font-size: 0.75rem;">Recommended size: 150x150px. Max size 2MB.</small>
                    </div>
                    <div class="col-12 mt-4 text-end">
                        <button type="submit" name="update_profile" class="btn btn-primary px-4 py-2 fw-bold">
                            <i class="bi bi-save me-2"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>
