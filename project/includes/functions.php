<?php
// Get current date in Y-m-d format
function getCurrentDate() {
    return date('Y-m-d');
}

// Get current time in H:i:s format
function getCurrentTime() {
    return date('H:i:s');
}

// Format date for display
function formatDate($date) {
    return date('d F Y', strtotime($date));
}

// Format time for display
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// Calculate work hours between clock in and clock out
function calculateWorkHours($clockIn, $clockOut) {
    $start = strtotime($clockIn);
    $end = strtotime($clockOut);
    $diff = $end - $start;
    
    // Convert to hours with 2 decimal places
    return round($diff / 3600, 2);
}

// Get attendance status based on clock in time
function getAttendanceStatus($clockIn) {
    // Company start time (9:00 AM)
    $startTime = strtotime('09:00:00');
    $actualTime = strtotime($clockIn);
    
    // If clocked in after 9:15 AM, mark as late
    if ($actualTime > strtotime('09:15:00')) {
        return 'late';
    }
    
    return 'present';
}

// Get colored status badge
function getStatusBadge($status) {
    switch ($status) {
        case 'present':
            return '<span class="badge bg-success">Present</span>';
        case 'late':
            return '<span class="badge bg-warning">Late</span>';
        case 'absent':
            return '<span class="badge bg-danger">Absent</span>';
        case 'pending':
            return '<span class="badge bg-info">Pending</span>';
        case 'approved':
            return '<span class="badge bg-success">Approved</span>';
        case 'rejected':
            return '<span class="badge bg-danger">Rejected</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

// Get user role text
function getUserRole($isAdmin) {
    return $isAdmin ? 'Administrator' : 'Employee';
}

// Check if user is already clocked in for today
function isUserClockedIn($conn, $employeeId) {
    $today = getCurrentDate();
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = :employee_id AND DATE(clock_in) = :today AND clock_out IS NULL");
    $stmt->bindParam(':employee_id', $employeeId);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    
    return $stmt->rowCount() > 0;
}

// Get attendance ID if user is clocked in
function getUserAttendanceId($conn, $employeeId) {
    $today = getCurrentDate();
    $stmt = $conn->prepare("SELECT id FROM attendance WHERE employee_id = :employee_id AND DATE(clock_in) = :today AND clock_out IS NULL");
    $stmt->bindParam(':employee_id', $employeeId);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['id'];
    }
    
    return null;
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>