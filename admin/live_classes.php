<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $subject_id = intval($_POST['subject_id']);
            $teacher_id = intval($_POST['teacher_id']);
            $title = sanitizeInput($_POST['title']);
            $description = sanitizeInput($_POST['description']);
            $class_url = sanitizeInput($_POST['class_url']);
            $scheduled_at = $_POST['scheduled_at'];
            $duration = intval($_POST['duration']);
            
            $stmt = $conn->prepare("INSERT INTO live_classes (subject_id, teacher_id, title, description, class_url, scheduled_at, duration) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssi", $subject_id, $teacher_id, $title, $description, $class_url, $scheduled_at, $duration);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Live class scheduled successfully!</div>';
                logActivity($_SESSION['user_id'], 'admin_schedule_class', "Admin scheduled live class: $title");
            } else {
                $message = '<div class="alert alert-danger">Error scheduling class.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'edit') {
            $class_id = intval($_POST['class_id']);
            $title = sanitizeInput($_POST['title']);
            $description = sanitizeInput($_POST['description']);
            $class_url = sanitizeInput($_POST['class_url']);
            $scheduled_at = $_POST['scheduled_at'];
            $duration = intval($_POST['duration']);
            $teacher_id = intval($_POST['teacher_id']);
            $status = sanitizeInput($_POST['status']);
            
            $stmt = $conn->prepare("UPDATE live_classes SET title = ?, description = ?, class_url = ?, scheduled_at = ?, duration = ?, teacher_id = ?, status = ? WHERE class_id = ?");
            $stmt->bind_param("ssssissi", $title, $description, $class_url, $scheduled_at, $duration, $teacher_id, $status, $class_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Live class updated successfully!</div>';
                logActivity($_SESSION['user_id'], 'admin_update_class', "Admin updated live class ID: $class_id");
            } else {
                $message = '<div class="alert alert-danger">Error updating class.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'update_status') {
            $class_id = intval($_POST['class_id']);
            $status = sanitizeInput($_POST['status']);
            
            $stmt = $conn->prepare("UPDATE live_classes SET status = ? WHERE class_id = ?");
            $stmt->bind_param("si", $status, $class_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Class status updated!</div>';
                logActivity($_SESSION['user_id'], 'admin_update_class_status', "Admin updated class ID $class_id to $status");
            } else {
                $message = '<div class="alert alert-danger">Error updating status.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $class_id = intval($_POST['class_id']);
            
            $stmt = $conn->prepare("DELETE FROM live_classes WHERE class_id = ?");
            $stmt->bind_param("i", $class_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Class deleted successfully!</div>';
                logActivity($_SESSION['user_id'], 'admin_delete_class', "Admin deleted class ID: $class_id");
            } else {
                $message = '<div class="alert alert-danger">Error deleting class.</div>';
            }
            $stmt->close();
        }
    }
}

// Filter parameters
$filter_subject = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$filter_teacher = isset($_GET['teacher']) ? intval($_GET['teacher']) : 0;
$filter_status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query with filters
$where_clauses = [];
$params = [];
$types = '';

if ($filter_subject > 0) {
    $where_clauses[] = "lc.subject_id = ?";
    $params[] = $filter_subject;
    $types .= 'i';
}

if ($filter_teacher > 0) {
    $where_clauses[] = "lc.teacher_id = ?";
    $params[] = $filter_teacher;
    $types .= 'i';
}

if ($filter_status && in_array($filter_status, ['scheduled', 'ongoing', 'completed', 'cancelled'])) {
    $where_clauses[] = "lc.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($search) {
    $where_clauses[] = "(lc.title LIKE ? OR lc.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get all live classes
$query = "SELECT lc.*, s.subject_name, u.full_name as teacher_name 
    FROM live_classes lc 
    JOIN subjects s ON lc.subject_id = s.subject_id 
    JOIN users u ON lc.teacher_id = u.user_id
    $where_sql
    ORDER BY lc.scheduled_at DESC";

$classes = [];
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Get all subjects for dropdown
$subjects = [];
$result = $conn->query("SELECT subject_id, subject_name FROM subjects WHERE status = 'active' ORDER BY subject_name");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// Get all teachers for dropdown
$teachers = [];
$result = $conn->query("SELECT user_id, full_name FROM users WHERE user_type = 'teacher' AND status = 'active' ORDER BY full_name");
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

// Get class statistics
$stats = [];
$result = $conn->query("SELECT status, COUNT(*) as count FROM live_classes GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}
$total_classes = array_sum($stats);

// Get upcoming classes count (next 7 days)
$upcoming_result = $conn->query("SELECT COUNT(*) as count FROM live_classes WHERE scheduled_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) AND status = 'scheduled'");
$upcoming_count = $upcoming_result->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Live Classes - MyLearn</title>
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
                            <a class="nav-link active" href="/admin/live_classes.php">
                                <i class="bi bi-camera-video"></i> Live Classes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/teachers.php">
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
                <div class="dashboard-header d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2><i class="bi bi-camera-video me-2"></i>Live Classes Management</h2>
                        <p class="text-muted mb-0">Schedule and manage all live classes</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class="bi bi-plus-lg"></i> Schedule Class
                    </button>
                </div>

                <?php echo $message; ?>

                <!-- Class Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-event fs-1 text-primary"></i>
                                <h3 class="mt-2"><?php echo $total_classes; ?></h3>
                                <p class="text-muted mb-0">Total Classes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-clock fs-1 text-info"></i>
                                <h3 class="mt-2"><?php echo $stats['scheduled'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Scheduled</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-broadcast fs-1 text-success"></i>
                                <h3 class="mt-2"><?php echo $stats['ongoing'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Live Now</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-week fs-1 text-warning"></i>
                                <h3 class="mt-2"><?php echo $upcoming_count; ?></h3>
                                <p class="text-muted mb-0">Next 7 Days</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" placeholder="Title or description..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Subject</label>
                                <select class="form-select" name="subject">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>" <?php echo $filter_subject == $subject['subject_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Teacher</label>
                                <select class="form-select" name="teacher">
                                    <option value="">All Teachers</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['user_id']; ?>" <?php echo $filter_teacher == $teacher['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="scheduled" <?php echo $filter_status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="ongoing" <?php echo $filter_status === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Classes Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-table me-2"></i>Live Classes (<?php echo count($classes); ?> found)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Subject</th>
                                        <th>Title</th>
                                        <th>Teacher</th>
                                        <th>Scheduled At</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                    <tr class="<?php echo $class['status'] === 'cancelled' ? 'table-secondary' : ''; ?>">
                                        <td><?php echo $class['class_id']; ?></td>
                                        <td><?php echo htmlspecialchars($class['subject_name']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($class['title']); ?></strong>
                                            <?php if ($class['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($class['description'], 0, 40)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-person-badge text-primary"></i>
                                            <?php echo htmlspecialchars($class['teacher_name']); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $scheduled = strtotime($class['scheduled_at']);
                                            $now = time();
                                            $diff = $scheduled - $now;
                                            ?>
                                            <strong><?php echo date('M d, Y', $scheduled); ?></strong>
                                            <br><small><?php echo date('H:i', $scheduled); ?></small>
                                            <?php if ($diff > 0 && $diff < 86400): ?>
                                            <br><span class="badge bg-warning">< 24hrs</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $class['duration']; ?> min</td>
                                        <td>
                                            <?php
                                            $status_colors = ['scheduled' => 'primary', 'ongoing' => 'success', 'completed' => 'secondary', 'cancelled' => 'danger'];
                                            $status_icons = ['scheduled' => 'clock', 'ongoing' => 'broadcast', 'completed' => 'check-circle', 'cancelled' => 'x-circle'];
                                            ?>
                                            <span class="badge bg-<?php echo $status_colors[$class['status']]; ?>">
                                                <i class="bi bi-<?php echo $status_icons[$class['status']]; ?>"></i>
                                                <?php echo ucfirst($class['status']); ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <?php if ($class['class_url']): ?>
                                            <a href="<?php echo htmlspecialchars($class['class_url']); ?>" class="btn btn-sm btn-success" target="_blank" title="Join Class">
                                                <i class="bi bi-camera-video"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-sm btn-info" onclick="editClass(<?php echo htmlspecialchars(json_encode($class)); ?>)" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <?php if ($class['status'] === 'scheduled'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <input type="hidden" name="status" value="ongoing">
                                                <button type="submit" class="btn btn-sm btn-success" title="Start Class">
                                                    <i class="bi bi-play"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($class['status'] === 'ongoing'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="End Class">
                                                    <i class="bi bi-stop"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($class['status'] === 'scheduled'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Cancel Class" onclick="return confirm('Are you sure you want to cancel this class?');">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this class?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($classes)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                            <p class="text-muted mb-0">No classes found</p>
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

    <!-- Add Class Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Schedule Live Class</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subject</label>
                                <select class="form-select" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>">
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assign Teacher</label>
                                <select class="form-select" name="teacher_id" required>
                                    <option value="">Select Teacher</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['user_id']; ?>">
                                        <?php echo htmlspecialchars($teacher['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class URL (Zoom, Google Meet, etc.)</label>
                            <input type="url" class="form-control" name="class_url" placeholder="https://" required>
                            <small class="text-muted">Enter the video conferencing link for this class</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Scheduled Date & Time</label>
                                <input type="datetime-local" class="form-control" name="scheduled_at" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" name="duration" value="60" min="15" max="300" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-calendar-plus"></i> Schedule Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Live Class</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="class_id" id="edit_class_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assign Teacher</label>
                                <select class="form-select" name="teacher_id" id="edit_teacher_id" required>
                                    <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['user_id']; ?>">
                                        <?php echo htmlspecialchars($teacher['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="scheduled">Scheduled</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class Title</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class URL</label>
                            <input type="url" class="form-control" name="class_url" id="edit_class_url" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Scheduled Date & Time</label>
                                <input type="datetime-local" class="form-control" name="scheduled_at" id="edit_scheduled_at" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" name="duration" id="edit_duration" min="15" max="300" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editClass(classData) {
            document.getElementById('edit_class_id').value = classData.class_id;
            document.getElementById('edit_teacher_id').value = classData.teacher_id;
            document.getElementById('edit_title').value = classData.title;
            document.getElementById('edit_description').value = classData.description;
            document.getElementById('edit_class_url').value = classData.class_url;
            document.getElementById('edit_duration').value = classData.duration;
            document.getElementById('edit_status').value = classData.status;
            
            // Format datetime for input
            var scheduledAt = new Date(classData.scheduled_at);
            var formattedDate = scheduledAt.toISOString().slice(0, 16);
            document.getElementById('edit_scheduled_at').value = formattedDate;
            
            var modal = new bootstrap.Modal(document.getElementById('editClassModal'));
            modal.show();
        }
    </script>
    <script src="/assets/js/mobile-nav.js"></script>
</body>
</html>
