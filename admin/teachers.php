<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $email = sanitizeInput($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $full_name = sanitizeInput($_POST['full_name']);
            
            $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, user_type) VALUES (?, ?, ?, 'teacher')");
            $stmt->bind_param("sss", $email, $password, $full_name);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Teacher added successfully!</div>';
                logActivity($_SESSION['user_id'], 'create_teacher', "Created teacher: $full_name");
            } else {
                $message = '<div class="alert alert-danger">Error adding teacher. Email may already exist.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'edit') {
            $user_id = intval($_POST['user_id']);
            $full_name = sanitizeInput($_POST['full_name']);
            $status = sanitizeInput($_POST['status']);
            
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, status = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $full_name, $status, $user_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Teacher updated successfully!</div>';
                logActivity($_SESSION['user_id'], 'update_teacher', "Updated teacher: $full_name");
            } else {
                $message = '<div class="alert alert-danger">Error updating teacher.</div>';
            }
            $stmt->close();
        }
    }
}

// Get all teachers
$teachers = [];
$result = $conn->query("SELECT u.*, 
    (SELECT COUNT(*) FROM subjects WHERE teacher_id = u.user_id) as subject_count
    FROM users u WHERE user_type = 'teacher' ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - MyLearn</title>
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
                            <a class="nav-link active" href="/admin/teachers.php">
                                <i class="bi bi-person-badge"></i> Teachers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/students.php">
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
                <div class="dashboard-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Manage Teachers</h2>
                        <p class="text-muted">Add and manage teachers</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                        <i class="bi bi-plus-lg"></i> Add Teacher
                    </button>
                </div>

                <?php echo $message; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subjects</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td><?php echo $teacher['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                        <td><?php echo $teacher['subject_count']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $teacher['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($teacher['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($teacher['created_at'])); ?></td>
                                        <td class="table-actions">
                                            <button class="btn btn-sm btn-info" onclick="editTeacher(<?php echo htmlspecialchars(json_encode($teacher)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($teachers)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No teachers found</td>
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

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Teacher</h5>
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
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Teacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Teacher</h5>
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
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Teacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTeacher(teacher) {
            document.getElementById('edit_user_id').value = teacher.user_id;
            document.getElementById('edit_full_name').value = teacher.full_name;
            document.getElementById('edit_status').value = teacher.status;
            
            var modal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
            modal.show();
        }
    </script>
    <script src="/assets/js/mobile-nav.js"></script>
</body>
</html>
