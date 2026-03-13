<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
            --sidebar-width: 250px;
        }
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; overflow-x: hidden; }
        
        /* Sidebar Responsive */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            color: white;
            z-index: 1050;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link { color: rgba(255,255,255,.8); padding: 1rem 1.5rem; font-weight: 500; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,.1); }
        
        .main-content { transition: all 0.3s; padding: 1.5rem; }
        .navbar { background: white; box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15); transition: all 0.3s; }

        @media (min-width: 992px) {
            .main-content { margin-left: var(--sidebar-width); }
            .navbar { margin-left: var(--sidebar-width); }
        }

        @media (max-width: 991.98px) {
            .sidebar { left: calc(var(--sidebar-width) * -1); }
            .sidebar.show { left: 0; }
            .navbar-brand { font-size: 0.9rem; }
        }

        .card { border: none; box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15); border-radius: .5rem; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .stats-card { border-left: .25rem solid !important; }
        .stats-card.primary { border-left-color: var(--primary-color) !important; }
        .stats-card.success { border-left-color: var(--success-color) !important; }
        .stats-card.warning { border-left-color: var(--warning-color) !important; }
        .stats-card.danger { border-left-color: var(--danger-color) !important; }
        
        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
        }
        .sidebar-overlay.show { display: block; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="sidebar" id="sidebar">
        <div class="p-3 d-flex justify-content-between align-items-center border-bottom border-white border-opacity-25">
            <h5 class="fw-bold mb-0">AMS PRO</h5>
            <button class="btn btn-link text-white d-lg-none" onclick="toggleSidebar()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'dashboard' ? 'active' : ''; ?>" href="dashboard">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <?php if ($_SESSION['role'] == 'Super Admin' || $_SESSION['role'] == 'HR'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'users' ? 'active' : ''; ?>" href="../admin/users">
                    <i class="bi bi-people me-2"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'departments' ? 'active' : ''; ?>" href="../admin/departments">
                    <i class="bi bi-building me-2"></i> Departments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'designations' ? 'active' : ''; ?>" href="../admin/designations">
                    <i class="bi bi-briefcase me-2"></i> Designations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'divisions' ? 'active' : ''; ?>" href="../admin/divisions">
                    <i class="bi bi-diagram-3 me-2"></i> Divisions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'branches' ? 'active' : ''; ?>" href="../admin/branches">
                    <i class="bi bi-geo-alt me-2"></i> Branches
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'distances' ? 'active' : ''; ?>" href="../admin/distances">
                    <i class="bi bi-pin-map me-2"></i> Distance Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'security_logs' ? 'active' : ''; ?>" href="../admin/security_logs">
                    <i class="bi bi-shield-lock-fill me-2 text-danger"></i> Security Logs
                </a>
            </li><?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'attendance' ? 'active' : ''; ?>" href="attendance">
                    <i class="bi bi-calendar-check me-2"></i> Attendance
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'leaves' ? 'active' : ''; ?>" href="leaves">
                    <i class="bi bi-calendar-event me-2"></i> Leaves
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'profile' ? 'active' : ''; ?>" href="profile">
                    <i class="bi bi-person-badge me-2"></i> Profile
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="../logout">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>

<?php
// Fetch current user data for profile image
if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    $stmt_header = $pdo->prepare("SELECT face_image FROM users WHERE id = ?");
    $stmt_header->execute([$current_user_id]);
    $header_user = $stmt_header->fetch();
    $profile_img_path = !empty($header_user['face_image']) ? '../uploads/profiles/' . $header_user['face_image'] : '';
}
?>
    <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top">
        <div class="container-fluid">
            <button class="btn btn-link d-lg-none me-3" onclick="toggleSidebar()">
                <i class="bi bi-list fs-4"></i>
            </button>
            <span class="navbar-brand text-muted fw-bold d-none d-sm-inline-block"><?php echo $page_title ?? 'Dashboard'; ?></span>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-dark fw-bold d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <?php if (!empty($profile_img_path) && file_exists($profile_img_path)): ?>
                            <img src="<?php echo $profile_img_path; ?>" class="rounded-circle me-1" style="width: 30px; height: 30px; object-fit: cover; border: 1px solid #ddd;">
                        <?php else: ?>
                            <i class="bi bi-person-circle me-1"></i> 
                        <?php endif; ?>
                        <span class="d-none d-md-inline"><?php echo $_SESSION['name']; ?></span>
                        <span class="badge bg-primary ms-1 small d-none d-sm-inline-block"><?php echo $_SESSION['role']; ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="main-content">

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        document.getElementById('sidebarOverlay').onclick = toggleSidebar;
    </script>
