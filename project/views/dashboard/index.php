<?php
// Get current user data
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'];

// Get attendance statistics for admin
if ($isAdmin) {
    // Total employees
    $stmtEmployees = $conn->prepare("SELECT COUNT(*) as total FROM employees");
    $stmtEmployees->execute();
    $totalEmployees = $stmtEmployees->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Today's attendance
    $today = getCurrentDate();
    $stmtPresent = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE DATE(clock_in) = :today AND status = 'present'");
    $stmtPresent->bindParam(':today', $today);
    $stmtPresent->execute();
    $presentToday = $stmtPresent->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Late today
    $stmtLate = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE DATE(clock_in) = :today AND status = 'late'");
    $stmtLate->bindParam(':today', $today);
    $stmtLate->execute();
    $lateToday = $stmtLate->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Absent today (employees minus those present or late)
    $absentToday = $totalEmployees - ($presentToday + $lateToday);
    
    // Recent leaves
    $stmtLeaves = $conn->prepare("SELECT l.*, e.first_name, e.last_name, e.employee_id FROM leaves l 
                                 JOIN employees e ON l.employee_id = e.id 
                                 WHERE l.status = 'pending' 
                                 ORDER BY l.created_at DESC LIMIT 5");
    $stmtLeaves->execute();
    $pendingLeaves = $stmtLeaves->fetchAll(PDO::FETCH_ASSOC);
}

// Get user's attendance status
$today = getCurrentDate();
$stmtAttendance = $conn->prepare("SELECT * FROM attendance WHERE employee_id = :employee_id AND DATE(clock_in) = :today");
$stmtAttendance->bindParam(':employee_id', $userId);
$stmtAttendance->bindParam(':today', $today);
$stmtAttendance->execute();
$todayAttendance = $stmtAttendance->fetch(PDO::FETCH_ASSOC);

// Get recent attendance for the user
$stmtHistory = $conn->prepare("SELECT * FROM attendance WHERE employee_id = :employee_id ORDER BY clock_in DESC LIMIT 5");
$stmtHistory->bindParam(':employee_id', $userId);
$stmtHistory->execute();
$recentAttendance = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

// Get pending leave requests for the user
$stmtUserLeaves = $conn->prepare("SELECT * FROM leaves WHERE employee_id = :employee_id ORDER BY created_at DESC LIMIT 3");
$stmtUserLeaves->bindParam(':employee_id', $userId);
$stmtUserLeaves->execute();
$userLeaves = $stmtUserLeaves->fetchAll(PDO::FETCH_ASSOC);

include 'views/layout/header.php';
?>

<div class="slide-in-left">
    <!-- Admin Dashboard -->
    <?php if ($isAdmin): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-title">Total Employees</div>
                <div class="stat-value"><?php echo $totalEmployees; ?></div>
                <div class="stat-desc">Registered employees</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--success);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-title">Present Today</div>
                <div class="stat-value"><?php echo $presentToday; ?></div>
                <div class="stat-desc"><?php echo round(($presentToday / $totalEmployees) * 100); ?>% of total</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--warning);">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>
                <div class="stat-title">Late Today</div>
                <div class="stat-value"><?php echo $lateToday; ?></div>
                <div class="stat-desc"><?php echo round(($lateToday / $totalEmployees) * 100); ?>% of total</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--danger);">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-title">Absent Today</div>
                <div class="stat-value"><?php echo $absentToday; ?></div>
                <div class="stat-desc"><?php echo round(($absentToday / $totalEmployees) * 100); ?>% of total</div>
            </div>
        </div>
        
        <div class="grid">
            <div class="col-8 col-md-12">
                <div class="card">
                    <h3 class="mb-4">Weekly Attendance Overview</h3>
                    <div class="chart-container">
                        <canvas id="attendance-chart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-4 col-md-12">
                <div class="card">
                    <h3 class="mb-4">Department Distribution</h3>
                    <div class="chart-container">
                        <canvas id="department-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Pending Leave Requests</h3>
                <a href="index.php?page=leave" class="btn btn-sm btn-primary">View All</a>
            </div>
            
            <?php if (count($pendingLeaves) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingLeaves as $leave): ?>
                                <tr>
                                    <td><?php echo $leave['first_name'] . ' ' . $leave['last_name'] . ' (' . $leave['employee_id'] . ')'; ?></td>
                                    <td><?php echo ucfirst($leave['leave_type']); ?></td>
                                    <td><?php echo formatDate($leave['start_date']); ?></td>
                                    <td><?php echo formatDate($leave['end_date']); ?></td>
                                    <td><?php echo getStatusBadge($leave['status']); ?></td>
                                    <td>
                                        <a href="index.php?page=leave&action=approve&id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                        <a href="index.php?page=leave&action=reject&id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No pending leave requests.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Employee Dashboard -->
        <div class="grid">
            <div class="col-8 col-md-12">
                <div class="card clock-card">
                    <h3 class="mb-4">Attendance Clock</h3>
                    <div class="current-time" id="current-time">00:00:00</div>
                    <div class="current-date"><?php echo formatDate(getCurrentDate()); ?></div>
                    
                    <div class="clock-actions">
                        <?php if (isUserClockedIn($conn, $userId)): ?>
                            <form action="index.php?page=attendance&action=clock-out" method="post">
                                <input type="hidden" name="attendance_id" value="<?php echo getUserAttendanceId($conn, $userId); ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-box-arrow-left mr-2"></i> Clock Out
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="index.php?page=attendance&action=clock-in" method="post">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-box-arrow-in-right mr-2"></i> Clock In
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($todayAttendance) && $todayAttendance): ?>
                        <div class="mt-4 text-center">
                            <p>
                                <?php if (empty($todayAttendance['clock_out'])): ?>
                                    You clocked in at <strong><?php echo formatTime($todayAttendance['clock_in']); ?></strong>
                                    <span class="badge bg-<?php echo $todayAttendance['status'] === 'present' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($todayAttendance['status']); ?>
                                    </span>
                                <?php else: ?>
                                    Today's work hours: <strong><?php echo $todayAttendance['work_hours']; ?> hours</strong>
                                    (<?php echo formatTime($todayAttendance['clock_in']); ?> - 
                                    <?php echo formatTime($todayAttendance['clock_out']); ?>)
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-4 col-md-12">
                <div class="card">
                    <h3 class="mb-4">Your Status</h3>
                    <div class="d-flex justify-content-center">
                        <?php if (isset($todayAttendance) && $todayAttendance): ?>
                            <?php if (empty($todayAttendance['clock_out'])): ?>
                                <div class="text-center">
                                    <div class="stat-icon" style="color: var(--success);">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>
                                    <h4 class="mt-2">Working</h4>
                                    <p>You are currently clocked in</p>
                                </div>
                            <?php else: ?>
                                <div class="text-center">
                                    <div class="stat-icon" style="color: var(--primary);">
                                        <i class="bi bi-check-all"></i>
                                    </div>
                                    <h4 class="mt-2">Completed</h4>
                                    <p>You've completed today's work</p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center">
                                <div class="stat-icon" style="color: var(--warning);">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                </div>
                                <h4 class="mt-2">Not Checked In</h4>
                                <p>You haven't clocked in today</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <h3 class="mb-4">Leave Balance</h3>
                    <div class="d-flex justify-content-between">
                        <div class="text-center">
                            <h4>12</h4>
                            <p>Annual</p>
                        </div>
                        <div class="text-center">
                            <h4>7</h4>
                            <p>Sick</p>
                        </div>
                        <div class="text-center">
                            <h4>3</h4>
                            <p>Personal</p>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="index.php?page=leave-form" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle mr-1"></i> Request Leave
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid">
            <div class="col-6 col-md-12">
                <div class="card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>Recent Attendance</h3>
                        <a href="index.php?page=attendance" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    
                    <?php if (count($recentAttendance) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentAttendance as $record): ?>
                                        <tr>
                                            <td><?php echo formatDate(date('Y-m-d', strtotime($record['clock_in']))); ?></td>
                                            <td><?php echo formatTime($record['clock_in']); ?></td>
                                            <td><?php echo $record['clock_out'] ? formatTime($record['clock_out']) : '-'; ?></td>
                                            <td><?php echo $record['work_hours'] ?? '-'; ?></td>
                                            <td><?php echo getStatusBadge($record['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No attendance records found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-6 col-md-12">
                <div class="card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>Leave Requests</h3>
                        <a href="index.php?page=leave" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    
                    <?php if (count($userLeaves) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Leave Type</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userLeaves as $leave): ?>
                                        <tr>
                                            <td><?php echo ucfirst($leave['leave_type']); ?></td>
                                            <td><?php echo formatDate($leave['start_date']); ?></td>
                                            <td><?php echo formatDate($leave['end_date']); ?></td>
                                            <td><?php echo getStatusBadge($leave['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No leave requests found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'views/layout/footer.php'; ?>