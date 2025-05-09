<?php
// Check if user is admin
if (!$_SESSION['is_admin']) {
    header("Location: index.php?page=dashboard");
    exit();
}

// Get filter parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$department = isset($_GET['department']) ? $_GET['department'] : 'all';

// Generate report data
try {
    $query = "SELECT e.id, e.first_name, e.last_name, e.employee_id, e.department, e.position,
             COUNT(DISTINCT CASE WHEN a.status = 'present' THEN DATE(a.clock_in) END) as present_days,
             COUNT(DISTINCT CASE WHEN a.status = 'late' THEN DATE(a.clock_in) END) as late_days,
             COUNT(DISTINCT DATE(a.clock_in)) as total_days,
             SUM(a.work_hours) as total_hours,
             AVG(a.work_hours) as avg_hours
             FROM employees e
             LEFT JOIN attendance a ON e.id = a.employee_id ";
    
    $conditions = [];
    $bindParams = [];
    
    if ($month != 'all') {
        $conditions[] = "MONTH(a.clock_in) = :month";
        $bindParams[':month'] = $month;
    }
    
    if ($year != 'all') {
        $conditions[] = "YEAR(a.clock_in) = :year";
        $bindParams[':year'] = $year;
    }
    
    if ($department != 'all') {
        $conditions[] = "e.department = :department";
        $bindParams[':department'] = $department;
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " GROUP BY e.id ORDER BY e.department, e.first_name";
    
    $stmt = $conn->prepare($query);
    
    foreach ($bindParams as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get working days in month
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month != 'all' ? $month : date('m'), $year != 'all' ? $year : date('Y'));
    $workingDays = 0;
    
    // Count actual working days (excluding weekends)
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $date = $year . '-' . ($month != 'all' ? str_pad($month, 2, '0', STR_PAD_LEFT) : date('m')) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $dayOfWeek = date('N', strtotime($date));
        
        // Exclude weekends (6=Saturday, 7=Sunday)
        if ($dayOfWeek < 6) {
            $workingDays++;
        }
    }
    
    // Get department list
    $stmtDepartments = $conn->prepare("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department");
    $stmtDepartments->execute();
    $departments = $stmtDepartments->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $errorMessage = "Error generating report: " . $e->getMessage();
    $reportData = [];
    $departments = [];
    $workingDays = 0;
}

include 'views/layout/header.php';
?>

<div class="slide-in-left">
    <?php if (isset($errorMessage)): ?>
        <div class="alert bg-danger p-3 mb-4 text-white fade-in">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Attendance Report</h3>
            
            <div>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer mr-1"></i> Print Report
                </button>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4 p-3">
            <h4 class="mb-3">Report Filters</h4>
            <div class="grid">
                <div class="col-4 col-md-12">
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
                
                <div class="col-4 col-md-12">
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
                
                <div class="col-4 col-md-12">
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
                
                <div class="col-12">
                    <button onclick="generateReport()" class="btn btn-primary">
                        <i class="bi bi-filter mr-1"></i> Generate Report
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Report Header -->
        <div class="report-header mb-4">
            <h3 class="text-center">Attendance Summary Report</h3>
            <p class="text-center">
                <?php if ($month != 'all'): ?>
                    <?php echo date('F', mktime(0, 0, 0, $month, 1)); ?> 
                <?php endif; ?>
                <?php echo $year != 'all' ? $year : 'All Years'; ?>
                <?php echo $department != 'all' ? ' - ' . $department . ' Department' : ' - All Departments'; ?>
            </p>
            <p class="text-center">
                <small>Generated on <?php echo formatDate(getCurrentDate()); ?></small>
            </p>
        </div>
        
        <!-- Report Summary -->
        <div class="card mb-4 p-3">
            <h4 class="mb-3">Summary</h4>
            <div class="grid">
                <div class="col-3 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <div class="stat-title">Total Employees</div>
                        <div class="stat-value"><?php echo count($reportData); ?></div>
                    </div>
                </div>
                
                <div class="col-3 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <div class="stat-title">Working Days</div>
                        <div class="stat-value"><?php echo $workingDays; ?></div>
                    </div>
                </div>
                
                <div class="col-3 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <div class="stat-title">Average Attendance</div>
                        <div class="stat-value">
                            <?php
                            if (count($reportData) > 0) {
                                $totalAttendance = 0;
                                foreach ($reportData as $employee) {
                                    $totalAttendance += $employee['present_days'] + $employee['late_days'];
                                }
                                $avgAttendance = $totalAttendance / (count($reportData) * $workingDays) * 100;
                                echo round($avgAttendance, 1) . '%';
                            } else {
                                echo '0%';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-3 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <div class="stat-title">Average Work Hours</div>
                        <div class="stat-value">
                            <?php
                            if (count($reportData) > 0) {
                                $totalHours = 0;
                                $employeesWithHours = 0;
                                foreach ($reportData as $employee) {
                                    if ($employee['total_hours'] > 0) {
                                        $totalHours += $employee['avg_hours'];
                                        $employeesWithHours++;
                                    }
                                }
                                $avgHours = $employeesWithHours > 0 ? $totalHours / $employeesWithHours : 0;
                                echo round($avgHours, 1) . ' hrs';
                            } else {
                                echo '0 hrs';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Report Table -->
        <?php if (count($reportData) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Present Days</th>
                            <th>Late Days</th>
                            <th>Absent Days</th>
                            <th>Total Hours</th>
                            <th>Avg. Hours/Day</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $employee): ?>
                            <tr>
                                <td><?php echo $employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'; ?></td>
                                <td><?php echo $employee['department'] ?? '-'; ?></td>
                                <td><?php echo $employee['position'] ?? '-'; ?></td>
                                <td><?php echo $employee['present_days']; ?></td>
                                <td><?php echo $employee['late_days']; ?></td>
                                <td>
                                    <?php 
                                    $absentDays = $workingDays - ($employee['present_days'] + $employee['late_days']);
                                    echo $absentDays > 0 ? $absentDays : 0;
                                    ?>
                                </td>
                                <td><?php echo round($employee['total_hours'], 1) ?? '0'; ?> hrs</td>
                                <td><?php echo round($employee['avg_hours'], 1) ?? '0'; ?> hrs</td>
                                <td>
                                    <?php
                                    $attendancePercent = $workingDays > 0 ? (($employee['present_days'] + $employee['late_days']) / $workingDays) * 100 : 0;
                                    echo round($attendancePercent, 1) . '%';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No data available for the selected filters.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function generateReport() {
    const month = document.getElementById('filter-month').value;
    const year = document.getElementById('filter-year').value;
    const department = document.getElementById('filter-department').value;
    
    window.location.href = `index.php?page=attendance-report&month=${month}&year=${year}&department=${department}`;
}
</script>

<?php include 'views/layout/footer.php'; ?>