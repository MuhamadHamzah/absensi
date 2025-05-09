<?php
// Check if user is admin
if (!$_SESSION['is_admin']) {
    header("Location: index.php?page=dashboard");
    exit();
}

// Process employee actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Delete employee
    if ($action === 'delete' && isset($_GET['id'])) {
        $employeeId = $_GET['id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM employees WHERE id = :id");
            $stmt->bindParam(':id', $employeeId);
            $stmt->execute();
            
            $successMessage = "Employee has been successfully deleted.";
        } catch(PDOException $e) {
            $errorMessage = "Error deleting employee: " . $e->getMessage();
        }
    }
}

// Get employees list
try {
    $stmt = $conn->prepare("SELECT * FROM employees ORDER BY first_name");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $errorMessage = "Error: " . $e->getMessage();
    $employees = [];
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
    
    <div class="card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Employee Management</h3>
            <a href="index.php?page=employee-form" class="btn btn-primary">
                <i class="bi bi-plus-circle mr-1"></i> Add New Employee
            </a>
        </div>
        
        <?php if (count($employees) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?php echo $employee['employee_id']; ?></td>
                                <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                                <td><?php echo $employee['email']; ?></td>
                                <td><?php echo $employee['department'] ?? '-'; ?></td>
                                <td><?php echo $employee['position'] ?? '-'; ?></td>
                                <td><?php echo getUserRole($employee['is_admin']); ?></td>
                                <td>
                                    <a href="index.php?page=employee-form&id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-secondary mr-1">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form id="delete-form-<?php echo $employee['id']; ?>" action="index.php?page=employees&action=delete&id=<?php echo $employee['id']; ?>" method="post" style="display: inline;">
                                        <button type="button" onclick="confirmDelete(<?php echo $employee['id']; ?>, 'employee')" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No employees found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>