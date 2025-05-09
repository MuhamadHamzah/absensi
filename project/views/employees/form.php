<?php
// Check if user is admin
if (!$_SESSION['is_admin']) {
    header("Location: index.php?page=dashboard");
    exit();
}

$isEdit = isset($_GET['id']);
$employeeId = $isEdit ? $_GET['id'] : null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $employeeCode = sanitizeInput($_POST['employee_id']);
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : null;
    $position = isset($_POST['position']) ? sanitizeInput($_POST['position']) : null;
    $department = isset($_POST['department']) ? sanitizeInput($_POST['department']) : null;
    $hireDate = isset($_POST['hire_date']) ? sanitizeInput($_POST['hire_date']) : null;
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($employeeCode)) {
        $errorMessage = "Please fill in all required fields!";
    } else {
        try {
            if ($isEdit) {
                // Check if password is being updated
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE employees SET 
                                          employee_id = :employee_id,
                                          first_name = :first_name,
                                          last_name = :last_name,
                                          email = :email,
                                          password = :password,
                                          phone = :phone,
                                          position = :position,
                                          department = :department,
                                          hire_date = :hire_date,
                                          is_admin = :is_admin
                                          WHERE id = :id");
                    $stmt->bindParam(':password', $password);
                } else {
                    $stmt = $conn->prepare("UPDATE employees SET 
                                          employee_id = :employee_id,
                                          first_name = :first_name,
                                          last_name = :last_name,
                                          email = :email,
                                          phone = :phone,
                                          position = :position,
                                          department = :department,
                                          hire_date = :hire_date,
                                          is_admin = :is_admin
                                          WHERE id = :id");
                }
                
                $stmt->bindParam(':id', $employeeId);
                $successMessage = "Employee updated successfully!";
            } else {
                // New employee requires password
                if (empty($_POST['password'])) {
                    $errorMessage = "Password is required for new employees!";
                    throw new Exception("Password required");
                }
                
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO employees (
                                      employee_id, first_name, last_name, email, password, 
                                      phone, position, department, hire_date, is_admin
                                      ) VALUES (
                                      :employee_id, :first_name, :last_name, :email, :password,
                                      :phone, :position, :department, :hire_date, :is_admin
                                      )");
                $stmt->bindParam(':password', $password);
                $successMessage = "New employee added successfully!";
            }
            
            // Bind parameters for both insert and update
            $stmt->bindParam(':employee_id', $employeeCode);
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':position', $position);
            $stmt->bindParam(':department', $department);
            $stmt->bindParam(':hire_date', $hireDate);
            $stmt->bindParam(':is_admin', $isAdmin);
            
            $stmt->execute();
            
            // Redirect after successful submission
            header("Location: index.php?page=employees");
            exit();
        } catch(Exception $e) {
            $errorMessage = $errorMessage ?? "Error: " . $e->getMessage();
        }
    }
}

// Get employee data for edit
if ($isEdit) {
    try {
        $stmt = $conn->prepare("SELECT * FROM employees WHERE id = :id");
        $stmt->bindParam(':id', $employeeId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            header("Location: index.php?page=employees");
            exit();
        }
        
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $errorMessage = "Error: " . $e->getMessage();
        header("Location: index.php?page=employees");
        exit();
    }
}

// Get departments
try {
    $stmtDepartments = $conn->prepare("SELECT * FROM departments ORDER BY name");
    $stmtDepartments->execute();
    $departments = $stmtDepartments->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $departments = [];
}

include 'views/layout/header.php';
?>

<div class="slide-in-left">
    <?php if (isset($errorMessage)): ?>
        <div class="alert bg-danger p-3 mb-4 text-white fade-in">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($successMessage)): ?>
        <div class="alert bg-success p-3 mb-4 text-white fade-in">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h3 class="mb-4"><?php echo $isEdit ? 'Edit Employee' : 'Add New Employee'; ?></h3>
        
        <form action="index.php?page=employee-form<?php echo $isEdit ? '&id=' . $employeeId : ''; ?>" method="post" class="needs-validation">
            <div class="grid">
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="employee_id" class="form-label">Employee ID *</label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?php echo $isEdit ? $employee['employee_id'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $isEdit ? $employee['email'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $isEdit ? $employee['first_name'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $isEdit ? $employee['last_name'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="password" class="form-label"><?php echo $isEdit ? 'Password (leave blank to keep current)' : 'Password *'; ?></label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo $isEdit ? '' : 'required'; ?>>
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $isEdit ? $employee['phone'] : ''; ?>">
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-control" id="department" name="department">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['name']; ?>" <?php echo $isEdit && $employee['department'] === $dept['name'] ? 'selected' : ''; ?>>
                                    <?php echo $dept['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="position" class="form-label">Position</label>
                        <input type="text" class="form-control" id="position" name="position" value="<?php echo $isEdit ? $employee['position'] : ''; ?>">
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="hire_date" class="form-label">Hire Date</label>
                        <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?php echo $isEdit ? $employee['hire_date'] : ''; ?>">
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <div class="mt-4">
                            <input type="checkbox" id="is_admin" name="is_admin" <?php echo $isEdit && $employee['is_admin'] ? 'checked' : ''; ?>>
                            <label for="is_admin" class="form-label">Administrator Access</label>
                        </div>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save mr-1"></i> <?php echo $isEdit ? 'Update Employee' : 'Add Employee'; ?>
                        </button>
                        <a href="index.php?page=employees" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>