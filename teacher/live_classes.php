<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('teacher');

$conn = getDBConnection();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $subject_id = intval($_POST['subject_id']);
            $title = sanitizeInput($_POST['title']);
            $description = sanitizeInput($_POST['description']);
            $class_url = sanitizeInput($_POST['class_url']);
            $scheduled_at = $_POST['scheduled_at'];
            $duration = intval($_POST['duration']);
            
            $stmt = $conn->prepare("INSERT INTO live_classes (subject_id, teacher_id, title, description, class_url, scheduled_at, duration) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssi", $subject_id, $_SESSION['user_id'], $title, $description, $class_url, $scheduled_at, $duration);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Live class scheduled successfully!</div>';
                logActivity($_SESSION['user_id'], 'schedule_class', "Scheduled live class: $title");
            } else {
                $message = '<div class="alert alert-danger">Error scheduling class.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'update_status') {
            $class_id = intval($_POST['class_id']);
            $status = sanitizeInput($_POST['status']);
            
            $stmt = $conn->prepare("UPDATE live_classes SET status = ? WHERE class_id = ? AND teacher_id = ?");
            $stmt->bind_param("sii", $status, $class_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Class status updated!</div>';
                logActivity($_SESSION['user_id'], 'update_class_status', "Updated class ID $class_id to $status");
            } else {
                $message = '<div class="alert alert-danger">Error updating status.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $class_id = intval($_POST['class_id']);
            
            $stmt = $conn->prepare("DELETE FROM live_classes WHERE class_id = ? AND teacher_id = ?");
            $stmt->bind_param("ii", $class_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Class deleted successfully!</div>';
                logActivity($_SESSION['user_id'], 'delete_class', "Deleted class ID: $class_id");
            } else {
                $message = '<div class="alert alert-danger">Error deleting class.</div>';
            }
            $stmt->close();
        }
    }
}

// Get teacher's subjects
$subjects = [];
$stmt = $conn->prepare("SELECT * FROM subjects WHERE teacher_id = ? AND status = 'active'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Get teacher's live classes
$classes = [];
$stmt = $conn->prepare("SELECT lc.*, s.subject_name FROM live_classes lc 
    JOIN subjects s ON lc.subject_id = s.subject_id 
    WHERE lc.teacher_id = ? 
    ORDER BY lc.scheduled_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Classes - MyLearn</title>
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
                        <small class="text-muted">Teacher Panel</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/teacher/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/teacher/materials.php">
                                <i class="bi bi-file-earmark-text"></i> Learning Materials
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/teacher/live_classes.php">
                                <i class="bi bi-camera-video"></i> Live Classes
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
                        <h2>Live Classes</h2>
                        <p class="text-muted">Schedule and manage live classes</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class="bi bi-plus-lg"></i> Schedule Class
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
                                        <th>Subject</th>
                                        <th>Title</th>
                                        <th>Scheduled At</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td><?php echo $class['class_id']; ?></td>
                                        <td><?php echo htmlspecialchars($class['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($class['title']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($class['scheduled_at'])); ?></td>
                                        <td><?php echo $class['duration']; ?> min</td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $class['status'] === 'scheduled' ? 'primary' : 
                                                    ($class['status'] === 'ongoing' ? 'success' : 
                                                    ($class['status'] === 'completed' ? 'secondary' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($class['status']); ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <?php if ($class['class_url']): ?>
                                            <a href="<?php echo htmlspecialchars($class['class_url']); ?>" class="btn btn-sm btn-success" target="_blank">
                                                <i class="bi bi-camera-video"></i> Join
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($class['status'] === 'scheduled'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <input type="hidden" name="status" value="ongoing">
                                                <button type="submit" class="btn btn-sm btn-info">
                                                    <i class="bi bi-play"></i> Start
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($class['status'] === 'ongoing'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn btn-sm btn-secondary">
                                                    <i class="bi bi-stop"></i> End
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this class?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($classes)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No classes scheduled yet</td>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Schedule Live Class</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
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
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Scheduled Date & Time</label>
                            <input type="datetime-local" class="form-control" name="scheduled_at" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" name="duration" value="60" min="15" max="300" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Schedule Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/mobile-nav.js"></script>
</body>
</html>
