<?php
// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $error = '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM employees WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['employee_id'] = $user['employee_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    $_SESSION['profile_image'] = $user['profile_image'];
                    
                    // Redirect to dashboard
                    header("Location: index.php?page=dashboard");
                    exit();
                } else {
                    $error = 'Invalid email or password';
                }
            } else {
                $error = 'Invalid email or password';
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

include 'views/layout/header.php';
?>

<div class="login-page">
    <div class="login-card fade-in">
        <div class="login-header">
            <div class="login-logo">
                <i class="bi bi-clock-fill text-primary"></i> Absensi
            </div>
            <h2>Employee Attendance System</h2>
            <p>Sign in to your account</p>
        </div>
        
        <?php if (isset($error) && !empty($error)): ?>
            <div class="alert bg-danger text-white mb-4 p-2 text-center">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="index.php?page=login" method="post">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <p class="text-secondary">Demo Accounts:</p>
            <div class="d-flex justify-content-center">
                <div class="mr-4">
                    <p class="mb-1"><small>Admin</small></p>
                    <p class="mb-1"><small></small></p>
                    <p><small></small></p>
                </div>
                <div>
                    <p class="mb-1"><small>Employee</small></p>
                    <p class="mb-1"><small></small></p>
                    <p><small></small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>