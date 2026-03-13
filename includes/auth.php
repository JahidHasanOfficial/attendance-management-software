<?php
// includes/auth.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($allowed_roles) {
    if (!isLoggedIn()) {
        header("Location: ../login");
        exit();
    }
    
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: ../unauthorized");
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        switch ($_SESSION['role']) {
            case 'Super Admin':
                header("Location: admin/dashboard");
                break;
            case 'HR':
                header("Location: hr/dashboard");
                break;
            case 'HOD':
                header("Location: hod/dashboard");
                break;
            case 'Employee':
                header("Location: employee/dashboard");
                break;
        }
        exit();
    }
}
?>
