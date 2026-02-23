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
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fc; }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            color: white;
            z-index: 1000;
        }
        .sidebar .nav-link { color: rgba(255,255,255,.8); padding: 1rem 1.5rem; font-weight: 500; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,.1); }
        .main-content { margin-left: var(--sidebar-width); padding: 2rem; }
        .navbar { margin-left: var(--sidebar-width); background: white; box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15); }
        .card { border: none; box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15); border-radius: .5rem; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .stats-card { border-left: .25rem solid !important; }
        .stats-card.primary { border-left-color: var(--primary-color) !important; }
        .stats-card.success { border-left-color: var(--success-color) !important; }
        .stats-card.warning { border-left-color: var(--warning-color) !important; }
        .stats-card.danger { border-left-color: var(--danger-color) !important; }
    </style>
</head>
<body>
    <div class="sidebar d-none d-md-block">
        <div class="p-3 text-center border-bottom border-white border-opacity-25">
            <h5 class="fw-bold mb-0">AMS PRO</h5>
        </div>
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <?php if ($_SESSION['role'] == 'Super Admin' || $_SESSION['role'] == 'HR'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'users' ? 'active' : ''; ?>" href="../admin/users.php">
                    <i class="bi bi-people me-2"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'departments' ? 'active' : ''; ?>" href="../admin/departments.php">
                    <i class="bi bi-building me-2"></i> Departments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'branches' ? 'active' : ''; ?>" href="../admin/branches.php">
                    <i class="bi bi-geo-alt me-2"></i> Branches
                </a>
            </li><?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'attendance' ? 'active' : ''; ?>" href="attendance.php">
                    <i class="bi bi-calendar-check me-2"></i> Attendance
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo $active_page == 'leaves' ? 'active' : ''; ?>" href="leaves.php">
                    <i class="bi bi-calendar-event me-2"></i> Leaves
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top">
        <div class="container-fluid">
            <span class="navbar-brand text-muted"><?php echo $page_title ?? 'Dashboard'; ?></span>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-dark fw-bold" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i> <?php echo $_SESSION['name']; ?> (<?php echo $_SESSION['role']; ?>)
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
