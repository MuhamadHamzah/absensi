<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Simple routing
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && $page != 'login') {
    header("Location: index.php?page=login");
    exit();
}

// Include the appropriate page
switch ($page) {
    case 'login':
        include 'views/auth/login.php';
        break;
    case 'dashboard':
        include 'views/dashboard/index.php';
        break;
    case 'employees':
        include 'views/employees/index.php';
        break;
    case 'employee-form':
        include 'views/employees/form.php';
        break;
    case 'attendance':
        include 'views/attendance/index.php';
        break;
    case 'attendance-report':
        include 'views/attendance/report.php';
        break;
    case 'profile':
        include 'views/profile/index.php';
        break;
    case 'leave':
        include 'views/leave/index.php';
        break;
    case 'leave-form':
        include 'views/leave/form.php';
        break;
    case 'logout':
        session_destroy();
        header("Location: index.php?page=login");
        exit();
        break;
    default:
        include 'views/auth/login.php';
}
?>