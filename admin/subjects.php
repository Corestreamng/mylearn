<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $subject_name = sanitizeInput($_POST['subject_name']);
            $description = sanitizeInput($_POST['description']);
            $price_per_month = floatval($_POST['price_per_month']);
            $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
            
            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, description, price_per_month, teacher_id, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdii", $subject_name, $description, $price_per_month, $teacher_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Subject added successfully!</div>';
                logActivity($_SESSION['user_id'], 'create_subject', "Created subject: $subject_name");
            } else {
                $message = '<div class="alert alert-danger">Error adding subject.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'edit') {
            $subject_id = intval($_POST['subject_id']);
            $subject_name = sanitizeInput($_POST['subject_name']);
            $description = sanitizeInput($_POST['description']);
            $price_per_month = floatval($_POST['price_per_month']);
            $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
            $status = sanitizeInput($_POST['status']);
            
            $stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, description = ?, price_per_month = ?, teacher_id = ?, status = ? WHERE subject_id = ?");
            $stmt->bind_param("ssdisi", $subject_name, $description, $price_per_month, $teacher_id, $status, $subject_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Subject updated successfully!</div>';
                logActivity($_SESSION['user_id'], 'update_subject', "Updated subject: $subject_name");
            } else {
                $message = '<div class="alert alert-danger">Error updating subject.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $subject_id = intval($_POST['subject_id']);
            
            $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
            $stmt->bind_param("i", $subject_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Subject deleted successfully!</div>';
                logActivity($_SESSION['user_id'], 'delete_subject', "Deleted subject ID: $subject_id");
            } else {
                $message = '<div class="alert alert-danger">Error deleting subject.</div>';
            }
            $stmt->close();
        }
    }
}

// Get all subjects
$subjects = [];
$result = $conn->query("SELECT s.*, u.full_name as teacher_name FROM subjects s LEFT JOIN users u ON s.teacher_id = u.user_id ORDER BY s.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// Get all teachers for dropdown
$teachers = [];
$result = $conn->query("SELECT user_id, full_name FROM users WHERE user_type = 'teacher' AND status = 'active'");
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
    <title>Manage Subjects - MyLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar">
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
                            <a class="nav-link active" href="/admin/subjects.php">
                                <i class="bi bi-book"></i> Subjects
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/teachers.php">
                                <i class="bi bi-person-badge"></i> Teachers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/students.php">
                                <i class="bi bi-people"></i> Students
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
                        <h2>Manage Subjects</h2>
                        <p class="text-muted">Create and manage subjects</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="bi bi-plus-lg"></i> Add Subject
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
                                        <th>Subject Name</th>
                                        <th>Description</th>
                                        <th>Price/Month (₦)</th>
                                        <th>Teacher</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td><?php echo $subject['subject_id']; ?></td>
                                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($subject['description'], 0, 50)) . '...'; ?></td>
                                        <td><strong>₦<?php echo number_format($subject['price_per_month'] ?? 0, 2); ?></strong></td>
                                        <td><?php echo $subject['teacher_name'] ? htmlspecialchars($subject['teacher_name']) : 'Unassigned'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $subject['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($subject['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($subject['created_at'])); ?></td>
                                        <td class="table-actions">
                                            <button class="btn btn-sm btn-info" onclick="editSubject(<?php echo htmlspecialchars(json_encode($subject)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No subjects found</td>
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

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Subject</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Subject Name</label>
                            <input type="text" class="form-control" name="subject_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per Month (₦)</label>
                            <input type="number" class="form-control" name="price_per_month" step="0.01" min="0" value="0" required>
                            <small class="text-muted">Enter the monthly subscription price in Naira</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Teacher (Optional)</label>
                            <select class="form-select" name="teacher_id">
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['user_id']; ?>">
                                    <?php echo htmlspecialchars($teacher['full_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Subject</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="subject_id" id="edit_subject_id">
                        <div class="mb-3">
                            <label class="form-label">Subject Name</label>
                            <input type="text" class="form-control" name="subject_name" id="edit_subject_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per Month (₦)</label>
                            <input type="number" class="form-control" name="price_per_month" id="edit_price_per_month" step="0.01" min="0" required>
                            <small class="text-muted">Enter the monthly subscription price in Naira</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Teacher</label>
                            <select class="form-select" name="teacher_id" id="edit_teacher_id">
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['user_id']; ?>">
                                    <?php echo htmlspecialchars($teacher['full_name']); ?>
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
                        <button type="submit" class="btn btn-primary">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editSubject(subject) {
            document.getElementById('edit_subject_id').value = subject.subject_id;
            document.getElementById('edit_subject_name').value = subject.subject_name;
            document.getElementById('edit_description').value = subject.description;
            document.getElementById('edit_price_per_month').value = subject.price_per_month || 0;
            document.getElementById('edit_teacher_id').value = subject.teacher_id || '';
            document.getElementById('edit_status').value = subject.status;
            
            var modal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
            modal.show();
        }
    </script>
</body>
</html>
