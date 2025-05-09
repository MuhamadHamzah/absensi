<?php
// Check if it's edit mode
$isEdit = isset($_GET['id']);
$leaveId = $isEdit ? $_GET['id'] : null;
$userId = $_SESSION['user_id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $startDate = sanitizeInput($_POST['start_date']);
    $endDate = sanitizeInput($_POST['end_date']);
    $leaveType = sanitizeInput($_POST['leave_type']);
    $reason = sanitizeInput($_POST['reason']);
    
    // Validate
    if (empty($startDate) || empty($endDate) || empty($leaveType)) {
        $errorMessage = "Please fill in all required fields!";
    } else if (strtotime($startDate) > strtotime($endDate)) {
        $errorMessage = "End date must be after start date!";
    } else {
        try {
            if ($isEdit) {
                // Check if this leave belongs to the user
                $checkStmt = $conn->prepare("SELECT * FROM leaves WHERE id = :id AND employee_id = :employee_id");
                $checkStmt->bindParam(':id', $leaveId);
                $checkStmt->bindParam(':employee_id', $userId);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() === 0) {
                    header("Location: index.php?page=leave");
                    exit();
                }
                
                // Update leave request
                $stmt = $conn->prepare("UPDATE leaves SET 
                                       start_date = :start_date,
                                       end_date = :end_date,
                                       leave_type = :leave_type,
                                       reason = :reason
                                       WHERE id = :id AND employee_id = :employee_id");
                $stmt->bindParam(':id', $leaveId);
                $stmt->bindParam(':employee_id', $userId);
                $successMessage = "Leave request updated successfully!";
            } else {
                // Insert new leave request
                $stmt = $conn->prepare("INSERT INTO leaves (
                                       employee_id, start_date, end_date, leave_type, reason
                                       ) VALUES (
                                       :employee_id, :start_date, :end_date, :leave_type, :reason
                                       )");
                $successMessage = "Leave request submitted successfully!";
            }
            
            $stmt->bindParam(':employee_id', $userId);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->bindParam(':leave_type', $leaveType);
            $stmt->bindParam(':reason', $reason);
            
            $stmt->execute();
            
            // Redirect back to leave page
            header("Location: index.php?page=leave");
            exit();
        } catch(PDOException $e) {
            $errorMessage = "Error: " . $e->getMessage();
        }
    }
}

// Get leave data for edit
if ($isEdit) {
    try {
        $stmt = $conn->prepare("SELECT * FROM leaves WHERE id = :id AND employee_id = :employee_id");
        $stmt->bindParam(':id', $leaveId);
        $stmt->bindParam(':employee_id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            header("Location: index.php?page=leave");
            exit();
        }
        
        $leave = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if the leave request is still pending
        if ($leave['status'] !== 'pending') {
            $errorMessage = "You can only edit pending leave requests!";
            header("Location: index.php?page=leave");
            exit();
        }
    } catch(PDOException $e) {
        $errorMessage = "Error: " . $e->getMessage();
        header("Location: index.php?page=leave");
        exit();
    }
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
        <h3 class="mb-4"><?php echo $isEdit ? 'Edit Leave Request' : 'Submit Leave Request'; ?></h3>
        
        <form action="index.php?page=leave-form<?php echo $isEdit ? '&id=' . $leaveId : ''; ?>" method="post">
            <div class="grid">
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="leave_type" class="form-label">Leave Type *</label>
                        <select class="form-control" id="leave_type" name="leave_type" required>
                            <option value="">Select Leave Type</option>
                            <option value="sick" <?php echo $isEdit && $leave['leave_type'] === 'sick' ? 'selected' : ''; ?>>Sick Leave</option>
                            <option value="vacation" <?php echo $isEdit && $leave['leave_type'] === 'vacation' ? 'selected' : ''; ?>>Vacation</option>
                            <option value="personal" <?php echo $isEdit && $leave['leave_type'] === 'personal' ? 'selected' : ''; ?>>Personal Leave</option>
                            <option value="other" <?php echo $isEdit && $leave['leave_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="start_date" class="form-label">Start Date *</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $isEdit ? $leave['start_date'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="col-6 col-md-12">
                    <div class="form-group">
                        <label for="end_date" class="form-label">End Date *</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $isEdit ? $leave['end_date'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="form-group">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="4"><?php echo $isEdit ? $leave['reason'] : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save mr-1"></i> <?php echo $isEdit ? 'Update Request' : 'Submit Request'; ?>
                        </button>
                        <a href="index.php?page=leave" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>