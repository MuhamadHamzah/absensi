<?php
// Process leave actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $leaveId = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($leaveId) {
        try {
            if ($action === 'approve') {
                $stmt = $conn->prepare("UPDATE leaves SET status = 'approved', approved_by = :approved_by WHERE id = :id");
                $stmt->bindParam(':approved_by', $_SESSION['user_id']);
                $stmt->bindParam(':id', $leaveId);
                $stmt->execute();
                
                $successMessage = "Leave request has been approved.";
            } else if ($action === 'reject') {
                $stmt = $conn->prepare("UPDATE leaves SET status = 'rejected', approved_by = :approved_by WHERE id = :id");
                $stmt->bindParam(':approved_by', $_SESSION['user_id']);
                $stmt->bindParam(':id', $leaveId);
                $stmt->execute();
                
                $successMessage = "Leave request has been rejected.";
            } else if ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM leaves WHERE id = :id AND employee_id = :employee_id");
                $stmt->bindParam(':id', $leaveId);
                $stmt->bindParam(':employee_id', $_SESSION['user_id']);
                $stmt->execute();
                
                $successMessage = "Leave request has been deleted.";
            }
        } catch(PDOException $e) {
            $errorMessage = "Error: " . $e->getMessage();
        }
    }
}

// Get leave requests
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'];

if ($isAdmin) {
    // Admins see all leave requests
    $stmt = $conn->prepare("SELECT l.*, e.first_name, e.last_name, e.employee_id, a.first_name as approver_fname, a.last_name as approver_lname 
                           FROM leaves l 
                           JOIN employees e ON l.employee_id = e.id 
                           LEFT JOIN employees a ON l.approved_by = a.id 
                           ORDER BY l.start_date DESC");
    $stmt->execute();
} else {
    // Employees see only their leave requests
    $stmt = $conn->prepare("SELECT l.*, e.first_name, e.last_name, e.employee_id, a.first_name as approver_fname, a.last_name as approver_lname 
                           FROM leaves l 
                           JOIN employees e ON l.employee_id = e.id 
                           LEFT JOIN employees a ON l.approved_by = a.id 
                           WHERE l.employee_id = :employee_id 
                           ORDER BY l.start_date DESC");
    $stmt->bindParam(':employee_id', $userId);
    $stmt->execute();
}

$leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <h3>Leave Requests</h3>
            <?php if (!$isAdmin): ?>
                <a href="index.php?page=leave-form" class="btn btn-primary">
                    <i class="bi bi-plus-circle mr-1"></i> Request Leave
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (count($leaveRequests) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <?php if ($isAdmin): ?>
                                <th>Employee</th>
                            <?php endif; ?>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <?php if ($isAdmin): ?>
                                <th>Approved By</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaveRequests as $leave): ?>
                            <tr>
                                <?php if ($isAdmin): ?>
                                    <td><?php echo $leave['first_name'] . ' ' . $leave['last_name'] . ' (' . $leave['employee_id'] . ')'; ?></td>
                                <?php endif; ?>
                                <td><?php echo ucfirst($leave['leave_type']); ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?></td>
                                <td><?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['reason'] ?? '-'; ?></td>
                                <td><?php echo getStatusBadge($leave['status']); ?></td>
                                <?php if ($isAdmin): ?>
                                    <td>
                                        <?php 
                                        if ($leave['approved_by']) {
                                            echo $leave['approver_fname'] . ' ' . $leave['approver_lname'];
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($isAdmin && $leave['status'] === 'pending'): ?>
                                        <a href="index.php?page=leave&action=approve&id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-success mr-1">
                                            <i class="bi bi-check"></i>
                                        </a>
                                        <a href="index.php?page=leave&action=reject&id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-danger">
                                            <i class="bi bi-x"></i>
                                        </a>
                                    <?php elseif (!$isAdmin && $leave['status'] === 'pending'): ?>
                                        <a href="index.php?page=leave-form&id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-secondary mr-1">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form id="delete-form-<?php echo $leave['id']; ?>" action="index.php?page=leave&action=delete&id=<?php echo $leave['id']; ?>" method="post" style="display: inline;">
                                            <button type="button" onclick="confirmDelete(<?php echo $leave['id']; ?>, 'leave request')" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">No actions</span>
                                    <?php endif; ?>
                                </td>
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

<?php include 'views/layout/footer.php'; ?>