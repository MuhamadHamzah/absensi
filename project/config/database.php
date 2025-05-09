<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'attendance_system';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}

// Initialize database if it doesn't exist
function initializeDatabase($conn) {
    $sql = file_get_contents('supabase/20250507141251_winter_castle.sql');
    $conn->exec($sql);
    
    // Check if admin exists, if not create one
    $stmt = $conn->prepare("SELECT COUNT(*) FROM employees WHERE is_admin = 1");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO employees (employee_id, first_name, last_name, email, password, position, department, is_admin) 
                              VALUES ('ADMIN001', 'Admin', 'User', 'admin@company.com', :password, 'Administrator', 'Management', 1)");
        $stmt->bindParam(':password', $password);
        $stmt->execute();
    }
}

// Uncomment to initialize database
// initializeDatabase($conn);
?>