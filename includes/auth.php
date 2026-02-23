<?php
// includes/auth.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($allowed_roles) {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
    
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        switch ($_SESSION['role']) {
            case 'Super Admin':
                header("Location: admin/dashboard.php");
                break;
            case 'HR':
                header("Location: hr/dashboard.php");
                break;
            case 'HOD':
                header("Location: hod/dashboard.php");
                break;
            case 'Employee':
                header("Location: employee/dashboard.php");
                break;
        }
        exit();
    }
}
?>
