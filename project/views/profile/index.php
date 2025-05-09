<?php
$userId = $_SESSION['user_id'];

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : null;
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $errorMessage = "Please fill in all required fields!";
    } else {
        try {
            // Check if password is being updated
            if (!empty($_POST['new_password'])) {
                // Verify current password
                $currentPassword = $_POST['current_password'];
                
                $stmt = $conn->prepare("SELECT password FROM employees WHERE id = :id");
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($currentPassword, $user['password'])) {
                    $errorMessage = "Current password is incorrect!";
                    throw new Exception("Password verification failed");
                }
                
                // Update with new password
                $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE employees SET 
                                      first_name = :first_name,
                                      last_name = :last_name,
                                      email = :email,
                                      phone = :phone,
                                      password = :password
                                      WHERE id = :id");
                $stmt->bindParam(':password', $newPassword);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE employees SET 
                                      first_name = :first_name,
                                      last_name = :last_name,
                                      email = :email,
                                      phone = :phone
                                      WHERE id = :id");
            }
            
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':id', $userId);
            
            $stmt->execute();
            
            // Update session variables
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['email'] = $email;
            
            $successMessage = "Profile updated successfully!";
        } catch(Exception $e) {
            $errorMessage = $errorMessage ?? "Error: " . $e->getMessage();
        }
    }
}

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $errorMessage = "Error: " . $e->getMessage();
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
        <div class="profile-header">
            <img src="<?php echo $user['profile_image'] ?? 'assets/img/hamzah.jpg'; ?>" alt="<?php echo $user['first_name']; ?>" class="profile-avatar">
            <div class="profile-info">
                <h2><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h2>
                <p><?php echo $user['position'] ?? 'Employee'; ?> - <?php echo $user['department'] ?? 'Department'; ?></p>
                <p><?php echo $user['employee_id']; ?></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <h3 class="mb-4">Personal Information</h3>
            
            <form action="index.php?page=profile" method="post">
                <div class="grid">
                    <div class="col-6 col-md-12">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $user['first_name']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-12">
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $user['last_name']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-12">
                        <div class="form-group">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-12">
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <h4 class="mt-4 mb-3">Change Password</h4>
                    </div>
                    
                    <div class="col-4 col-md-12">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                    </div>
                    
                    <div class="col-4 col-md-12">
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                    </div>
                    
                    <div class="col-4 col-md-12">
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save mr-1"></i> Update Profile
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h3 class="mb-4">Employment Information</h3>
            
            <div class="grid">
                <div class="col-6 col-md-12">
                    <p><strong>Employee ID:</strong> <?php echo $user['employee_id']; ?></p>
                    <p><strong>Department:</strong> <?php echo $user['department'] ?? 'Not specified'; ?></p>
                    <p><strong>Position:</strong> <?php echo $user['position'] ?? 'Not specified'; ?></p>
                </div>
                
                <div class="col-6 col-md-12">
                    <p><strong>Role:</strong> <?php echo getUserRole($user['is_admin']); ?></p>
                    <p><strong>Hire Date:</strong> <?php echo $user['hire_date'] ? formatDate($user['hire_date']) : 'Not specified'; ?></p>
                    <p><strong>Account Created:</strong> <?php echo formatDate(date('Y-m-d', strtotime($user['created_at']))); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>