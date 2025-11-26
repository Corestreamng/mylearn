<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $full_name = sanitizeInput($_POST['full_name']);
            $email = sanitizeInput($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            
            $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, user_type, parent_id) VALUES (?, ?, ?, 'student', ?)");
            $stmt->bind_param("sssi", $email, $password, $full_name, $parent_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Student added successfully!</div>';
                logActivity($_SESSION['user_id'], 'create_student', "Created student: $full_name");
            } else {
                $message = '<div class="alert alert-danger">Error adding student. Email may already exist.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'edit') {
            $user_id = intval($_POST['user_id']);
            $full_name = sanitizeInput($_POST['full_name']);
            $status = sanitizeInput($_POST['status']);
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, status = ?, parent_id = ? WHERE user_id = ?");
            $stmt->bind_param("ssii", $full_name, $status, $parent_id, $user_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Student updated successfully!</div>';
                logActivity($_SESSION['user_id'], 'update_student', "Updated student ID: $user_id");
            } else {
                $message = '<div class="alert alert-danger">Error updating student.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'toggle_status') {
            $user_id = intval($_POST['user_id']);
            $new_status = sanitizeInput($_POST['new_status']);
            
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_status, $user_id);
            
            if ($stmt->execute()) {
                $action_text = $new_status === 'active' ? 'activated' : 'deactivated';
                $message = '<div class="alert alert-success">Student ' . $action_text . ' successfully!</div>';
                logActivity($_SESSION['user_id'], 'toggle_student_status', "Student ID $user_id $action_text");
            } else {
                $message = '<div class="alert alert-danger">Error updating status.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $user_id = intval($_POST['user_id']);
            
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND user_type = 'student'");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Student deleted successfully!</div>';
                logActivity($_SESSION['user_id'], 'delete_student', "Deleted student ID: $user_id");
            } else {
                $message = '<div class="alert alert-danger">Error deleting student.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'reset_password') {
            $user_id = intval($_POST['user_id']);
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_password, $user_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Password reset successfully!</div>';
                logActivity($_SESSION['user_id'], 'reset_student_password', "Reset password for student ID: $user_id");
            } else {
                $message = '<div class="alert alert-danger">Error resetting password.</div>';
            }
            $stmt->close();
        }
    }
}

// Get all parents for dropdown
$parents = [];
$result = $conn->query("SELECT user_id, full_name, email FROM users WHERE user_type = 'parent' AND status = 'active' ORDER BY full_name");
while ($row = $result->fetch_assoc()) {
    $parents[] = $row;
}

// Get all students with their parent info
$students = [];
$result = $conn->query("SELECT s.*, p.full_name as parent_name, p.email as parent_email,
    (SELECT COUNT(*) FROM subscriptions WHERE student_id = s.user_id AND status = 'active') as active_subscriptions
    FROM users s 
    LEFT JOIN users p ON s.parent_id = p.user_id 
    WHERE s.user_type = 'student' 
    ORDER BY s.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - MyLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Mobile Navigation Toggle -->
    <button class="mobile-nav-toggle d-md-none" type="button" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar" id="sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">MyLearn</h4>
                        <small class="text-muted">Admin Panel</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/users.php">
                                <i class="bi bi-people-fill"></i> All Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/subjects.php">
                                <i class="bi bi-book"></i> Subjects
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/materials.php">
                                <i class="bi bi-file-earmark-text"></i> Materials
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/live_classes.php">
                                <i class="bi bi-camera-video"></i> Live Classes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/teachers.php">
                                <i class="bi bi-person-badge"></i> Teachers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/admin/students.php">
                                <i class="bi bi-mortarboard"></i> Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/parents.php">
                                <i class="bi bi-person-check"></i> Parents
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/subscriptions.php">
                                <i class="bi bi-credit-card"></i> Subscriptions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/activities.php">
                                <i class="bi bi-activity"></i> Activity Logs
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="/includes/logout.php?logout=1">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto main-content">
                <div class="dashboard-header d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2><i class="bi bi-mortarboard me-2"></i>Manage Students</h2>
                        <p class="text-muted mb-0">Full CRUD operations for student accounts</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="bi bi-plus-lg"></i> Add Student
                    </button>
                </div>

                <?php echo $message; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-table me-2"></i>Students List (<?php echo count($students); ?> total)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Student Name</th>
                                        <th>Parent</th>
                                        <th>Active Subs</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr class="<?php echo $student['status'] === 'inactive' ? 'table-secondary' : ''; ?>">
                                        <td><?php echo $student['user_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                        <td>
                                            <?php if ($student['parent_name']): ?>
                                            <i class="bi bi-person text-primary"></i> <?php echo htmlspecialchars($student['parent_name']); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($student['parent_email']); ?></small>
                                            <?php else: ?>
                                            <span class="text-muted">No parent</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $student['active_subscriptions']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($student['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                        <td class="table-actions">
                                            <button class="btn btn-sm btn-info" onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="resetPassword(<?php echo $student['user_id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')" title="Reset Password">
                                                <i class="bi bi-key"></i>
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $student['user_id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $student['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $student['status'] === 'active' ? 'secondary' : 'success'; ?>" 
                                                        title="<?php echo $student['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="bi bi-<?php echo $student['status'] === 'active' ? 'x-circle' : 'check-circle'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this student? This will also delete their subscriptions and progress.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $student['user_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="bi bi-inbox fs-1 text-muted"></i>
                                            <p class="text-muted mb-0">No students found</p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Parent (Optional)</label>
                            <select class="form-select" name="parent_id">
                                <option value="">No Parent</option>
                                <?php foreach ($parents as $parent): ?>
                                <option value="<?php echo $parent['user_id']; ?>">
                                    <?php echo htmlspecialchars($parent['full_name']); ?> (<?php echo htmlspecialchars($parent['email']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Parent</label>
                            <select class="form-select" name="parent_id" id="edit_parent_id">
                                <option value="">No Parent</option>
                                <?php foreach ($parents as $parent): ?>
                                <option value="<?php echo $parent['user_id']; ?>">
                                    <?php echo htmlspecialchars($parent['full_name']); ?> (<?php echo htmlspecialchars($parent['email']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="reset_user_id">
                        <p>Reset password for: <strong id="reset_user_name"></strong></p>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning"><i class="bi bi-key"></i> Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editStudent(student) {
            document.getElementById('edit_user_id').value = student.user_id;
            document.getElementById('edit_full_name').value = student.full_name;
            document.getElementById('edit_parent_id').value = student.parent_id || '';
            document.getElementById('edit_status').value = student.status;
            
            var modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
            modal.show();
        }
        
        function resetPassword(userId, userName) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_user_name').textContent = userName;
            
            var modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            modal.show();
        }
    </script>
    <script src="/assets/js/mobile-nav.js"></script>
</body>
</html>
