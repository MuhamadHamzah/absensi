<?php
// Process attendance actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Process clock in
    if ($action === 'clock-in') {
        $employeeId = $_SESSION['user_id'];
        $currentDate = getCurrentDate();
        $currentTime = getCurrentTime();
        $clockIn = $currentDate . ' ' . $currentTime;
        $status = getAttendanceStatus($currentTime);
        
        try {
            // Check if already clocked in today
            if (!isUserClockedIn($conn, $employeeId)) {
                $stmt = $conn->prepare("INSERT INTO attendance (employee_id, clock_in, status, note) VALUES (:employee_id, :clock_in, :status, :note)");
                $stmt->bindParam(':employee_id', $employeeId);
                $stmt->bindParam(':clock_in', $clockIn);
                $stmt->bindParam(':status', $status);
                $note = "Self clock-in";
                $stmt->bindParam(':note', $note);
                $stmt->execute();
                
                $successMessage = "You have successfully clocked in at " . formatTime($currentTime);
            } else {
                $errorMessage = "You have already clocked in today!";
            }
        } catch(PDOException $e) {
            $errorMessage = "Error: " . $e->getMessage();
        }
    }
    
    // Process clock out
    if ($action === 'clock-out') {
        $attendanceId = $_POST['attendance_id'] ?? null;
        
        if ($attendanceId) {
            $currentTime = getCurrentTime();
            $clockOut = getCurrentDate() . ' ' . $currentTime;
            
            try {
                // Get clock in time to calculate work hours
                $stmt = $conn->prepare("SELECT clock_in FROM attendance WHERE id = :id");
                $stmt->bindParam(':id', $attendanceId);
                $stmt->execute();
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($record) {
                    $clockIn = $record['clock_in'];
                    $workHours = calculateWorkHours($clockIn, $clockOut);
                    
                    // Update attendance record
                    $stmt = $conn->prepare("UPDATE attendance SET clock_out = :clock_out, work_hours = :work_hours WHERE id = :id");
                    $stmt->bindParam(':clock_out', $clockOut);
                    $stmt->bindParam(':work_hours', $workHours);
                    $stmt->bindParam(':id', $attendanceId);
                    $stmt->execute();
                    
                    $successMessage = "You have successfully clocked out at " . formatTime($currentTime);
                } else {
                    $errorMessage = "Attendance record not found!";
                }
            } catch(PDOException $e) {
                $errorMessage = "Error: " . $e->getMessage();
            }
        } else {
            $errorMessage = "Attendance ID is required for clock out!";
        }
    }
}

// Get attendance records
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'];

// Get filter parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$employee = isset($_GET['employee']) ? $_GET['employee'] : ($isAdmin ? 'all' : $userId);
$department = isset($_GET['department']) ? $_GET['department'] : 'all';

// Prepare attendance query
$query = "SELECT a.*, e.first_name, e.last_name, e.employee_id, e.department 
          FROM attendance a 
          JOIN employees e ON a.employee_id = e.id 
          WHERE 1=1";

// Apply filters
if ($month != 'all') {
    $query .= " AND MONTH(a.clock_in) = :month";
}

if ($year != 'all') {
    $query .= " AND YEAR(a.clock_in) = :year";
}

if ($employee != 'all') {
    $query .= " AND a.employee_id = :employee_id";
}

if ($department != 'all') {
    $query .= " AND e.department = :department";
}

// Add sorting
$query .= " ORDER BY a.clock_in DESC";

// Prepare and execute statement
try {
    $stmt = $conn->prepare($query);
    
    if ($month != 'all') {
        $stmt->bindParam(':month', $month);
    }
    
    if ($year != 'all') {
        $stmt->bindParam(':year', $year);
    }
    
    if ($employee != 'all') {
        $stmt->bindParam(':employee_id', $employee);
    }
    
    if ($department != 'all') {
        $stmt->bindParam(':department', $department);
    }
    
    $stmt->execute();
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $errorMessage = "Error: " . $e->getMessage();
    $attendanceRecords = [];
}

// Get employees list for filter (admin only)
if ($isAdmin) {
    try {
        $stmtEmployees = $conn->prepare("SELECT id, first_name, last_name, employee_id FROM employees ORDER BY first_name");
        $stmtEmployees->execute();
        $employees = $stmtEmployees->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $employees = [];
    }
    
    // Get departments
    try {
        $stmtDepartments = $conn->prepare("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department");
        $stmtDepartments->execute();
        $departments = $stmtDepartments->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $departments = [];
    }
}

include 'views/layout/header.php';
?>

<div class="slide-in-left">
    <?php if (isset($successMessage)): ?>
        <div class="alert bg-success p-3 mb-4 text-white fade-in">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($errorMessage)): ?>
        <div class="alert bg-danger p-3 mb-4 text-white fade-in">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$isAdmin): ?>
        <!-- Employee Clock Card -->
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
        </div>
    <?php endif; ?>
    
    <!-- Attendance Records -->
    <div class="card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Attendance Records</h3>
            
            <?php if ($isAdmin): ?>
                <a href="index.php?page=attendance-report" class="btn btn-sm btn-primary">
                    <i class="bi bi-file-earmark-text mr-1"></i> Generate Report
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4 p-3">
            <h4 class="mb-3">Filter Records</h4>
            <div class="grid">
                <div class="col-3 col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="filter-month">Month</label>
                        <select id="filter-month" class="form-control">
                            <option value="all" <?php echo $month === 'all' ? 'selected' : ''; ?>>All Months</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $month == $i ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-3 col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="filter-year">Year</label>
                        <select id="filter-year" class="form-control">
                            <option value="all" <?php echo $year === 'all' ? 'selected' : ''; ?>>All Years</option>
                            <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $year == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <?php if ($isAdmin): ?>
                    <div class="col-3 col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="filter-employee">Employee</label>
                            <select id="filter-employee" class="form-control">
                                <option value="all" <?php echo $employee === 'all' ? 'selected' : ''; ?>>All Employees</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo $employee == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo $emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-3 col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="filter-department">Department</label>
                            <select id="filter-department" class="form-control">
                                <option value="all" <?php echo $department === 'all' ? 'selected' : ''; ?>>All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department']; ?>" <?php echo $department === $dept['department'] ? 'selected' : ''; ?>>
                                        <?php echo $dept['department']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="col-12">
                    <button onclick="filterAttendance()" class="btn btn-primary">
                        <i class="bi bi-filter mr-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Records Table -->
        <?php if (count($attendanceRecords) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <?php if ($isAdmin): ?>
                                <th>Employee</th>
                                <th>Department</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Work Hours</th>
                            <th>Status</th>
                            <?php if ($isAdmin): ?>
                                <th>Notes</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceRecords as $record): ?>
                            <tr>
                                <?php if ($isAdmin): ?>
                                    <td><?php echo $record['first_name'] . ' ' . $record['last_name'] . ' (' . $record['employee_id'] . ')'; ?></td>
                                    <td><?php echo $record['department']; ?></td>
                                <?php endif; ?>
                                <td><?php echo formatDate(date('Y-m-d', strtotime($record['clock_in']))); ?></td>
                                <td><?php echo formatTime($record['clock_in']); ?></td>
                                <td><?php echo $record['clock_out'] ? formatTime($record['clock_out']) : '-'; ?></td>
                                <td><?php echo $record['work_hours'] ?? '-'; ?></td>
                                <td><?php echo getStatusBadge($record['status']); ?></td>
                                <?php if ($isAdmin): ?>
                                    <td><?php echo $record['note'] ?? '-'; ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No attendance records found for the selected filters.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>