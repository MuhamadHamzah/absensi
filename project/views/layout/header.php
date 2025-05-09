<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap Icons (alternative to Lucide) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Chart.js for charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if (isset($_SESSION['user_id']) && $page != 'login'): ?>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header mb-4">
                <h1 class="text-primary">Absensi</h1>
                <p>Employee Attendance System</p>
            </div>
            
            <div class="sidebar-content">
                <div class="user-info mb-4">
                    <img src="<?php echo $_SESSION['profile_image'] ?? 'assets/img/hamzah.jpg'; ?>" alt="User" class="profile-avatar" style="width: 50px; height: 50px;">
                    <div>
                        <h3><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h3>
                        <p><?php echo getUserRole($_SESSION['is_admin']); ?></p>
                    </div>
                </div>
                
                <nav class="sidebar-nav">
                    <ul>
                        <li class="mb-2">
                            <a href="index.php?page=dashboard" class="<?php echo $page === 'dashboard' ? 'text-primary' : ''; ?>">
                                <i class="bi bi-speedometer2 mr-2"></i> Dashboard
                            </a>
                        </li>
                        
                        <li class="mb-2">
                            <a href="index.php?page=attendance" class="<?php echo $page === 'attendance' ? 'text-primary' : ''; ?>">
                                <i class="bi bi-clock mr-2"></i> Attendance
                            </a>
                        </li>
                        
                        <?php if ($_SESSION['is_admin']): ?>
                            <li class="mb-2">
                                <a href="index.php?page=employees" class="<?php echo $page === 'employees' ? 'text-primary' : ''; ?>">
                                    <i class="bi bi-people mr-2"></i> Employees
                                </a>
                            </li>
                            
                            <li class="mb-2">
                                <a href="index.php?page=attendance-report" class="<?php echo $page === 'attendance-report' ? 'text-primary' : ''; ?>">
                                    <i class="bi bi-file-earmark-text mr-2"></i> Reports
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="mb-2">
                            <a href="index.php?page=leave" class="<?php echo $page === 'leave' ? 'text-primary' : ''; ?>">
                                <i class="bi bi-calendar-check mr-2"></i> Leave Requests
                            </a>
                        </li>
                        
                        <li class="mb-2">
                            <a href="index.php?page=profile" class="<?php echo $page === 'profile' ? 'text-primary' : ''; ?>">
                                <i class="bi bi-person mr-2"></i> My Profile
                            </a>
                        </li>
                        
                        <li class="mb-2">
                            <a href="index.php?page=logout">
                                <i class="bi bi-box-arrow-right mr-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="content">
            <div class="navbar">
                <div class="d-flex align-items-center">
                    <button id="menu-toggle" class="btn btn-sm btn-primary d-md-none mr-3">
                        <i class="bi bi-list"></i>
                    </button>
                    <h2 class="mb-0">
                        <?php
                        switch($page) {
                            case 'dashboard':
                                echo 'Dashboard';
                                break;
                            case 'attendance':
                                echo 'Attendance Management';
                                break;
                            case 'attendance-report':
                                echo 'Attendance Reports';
                                break;
                            case 'employees':
                                echo 'Employee Management';
                                break;
                            case 'employee-form':
                                echo isset($_GET['id']) ? 'Edit Employee' : 'Add New Employee';
                                break;
                            case 'profile':
                                echo 'My Profile';
                                break;
                            case 'leave':
                                echo 'Leave Requests';
                                break;
                            case 'leave-form':
                                echo isset($_GET['id']) ? 'Edit Leave Request' : 'Submit Leave Request';
                                break;
                            default:
                                echo 'Employee Attendance System';
                        }
                        ?>
                    </h2>
                </div>
                
                <div class="navbar-search">
                    <div class="current-date">
                        <?php echo formatDate(getCurrentDate()); ?>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div class="mobile-nav d-md-none">
                <a href="index.php?page=dashboard" class="mobile-nav-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                </a>
                <a href="index.php?page=attendance" class="mobile-nav-item <?php echo $page === 'attendance' ? 'active' : ''; ?>">
                    <i class="bi bi-clock"></i>
                </a>
                <?php if ($_SESSION['is_admin']): ?>
                    <a href="index.php?page=employees" class="mobile-nav-item <?php echo $page === 'employees' ? 'active' : ''; ?>">
                        <i class="bi bi-people"></i>
                    </a>
                <?php else: ?>
                    <a href="index.php?page=leave" class="mobile-nav-item <?php echo $page === 'leave' ? 'active' : ''; ?>">
                        <i class="bi bi-calendar-check"></i>
                    </a>
                <?php endif; ?>
                <a href="index.php?page=profile" class="mobile-nav-item <?php echo $page === 'profile' ? 'active' : ''; ?>">
                    <i class="bi bi-person"></i>
                </a>
            </div>
<?php endif; ?>