<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$conn = getDBConnection();

// Get student's active subscriptions
$subscriptions = [];
$stmt = $conn->prepare("SELECT sub.*, subj.subject_name, u.full_name as teacher_name
    FROM subscriptions sub
    JOIN subjects subj ON sub.subject_id = subj.subject_id
    LEFT JOIN users u ON subj.teacher_id = u.user_id
    WHERE sub.student_id = ? AND sub.status = 'active'
    ORDER BY sub.end_date ASC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subscriptions[] = $row;
}
$stmt->close();

// Get upcoming live classes for subscribed subjects
$upcoming_classes = [];
if (!empty($subscriptions)) {
    $subject_ids = array_column($subscriptions, 'subject_id');
    // Safe: Each ID is explicitly cast to integer to prevent SQL injection
    $ids_string = implode(',', array_map('intval', $subject_ids));
    
    $result = $conn->query("SELECT lc.*, s.subject_name, u.full_name as teacher_name
        FROM live_classes lc
        JOIN subjects s ON lc.subject_id = s.subject_id
        JOIN users u ON lc.teacher_id = u.user_id
        WHERE lc.subject_id IN ($ids_string) 
        AND lc.scheduled_at > NOW() 
        AND lc.status IN ('scheduled', 'ongoing')
        ORDER BY lc.scheduled_at ASC LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        $upcoming_classes[] = $row;
    }
}

// Get total materials available
$total_materials = 0;
if (!empty($subscriptions)) {
    $subject_ids = array_column($subscriptions, 'subject_id');
    // Safe: Each ID is explicitly cast to integer to prevent SQL injection
    $ids_string = implode(',', array_map('intval', $subject_ids));
    
    $result = $conn->query("SELECT COUNT(*) as count FROM learning_materials WHERE subject_id IN ($ids_string) AND status = 'active'");
    $total_materials = $result->fetch_assoc()['count'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - MyLearn</title>
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
                        <small class="text-muted">Student Panel</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="/student/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/student/progress.php">
                                <i class="bi bi-graph-up"></i> My Progress
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/student/materials.php">
                                <i class="bi bi-file-earmark-text"></i> Learning Materials
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/student/live_classes.php">
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
                <div class="dashboard-header">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
                    <p class="text-muted">Student Dashboard</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <h6 class="text-muted">My Subjects</h6>
                                <h2><?php echo count($subscriptions); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <h6 class="text-muted">Learning Materials</h6>
                                <h2><?php echo $total_materials; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <h6 class="text-muted">Upcoming Classes</h6>
                                <h2><?php echo count($upcoming_classes); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Subjects -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>My Active Subscriptions</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($subscriptions)): ?>
                                    <div class="alert alert-info">
                                        You don't have any active subscriptions yet. Ask your parent to enroll you in subjects.
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($subscriptions as $sub): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card material-card">
                                                <div class="card-body">
                                                    <h5><?php echo htmlspecialchars($sub['subject_name']); ?></h5>
                                                    <p class="text-muted">
                                                        <small><strong>Teacher:</strong> <?php echo htmlspecialchars($sub['teacher_name']); ?></small>
                                                    </p>
                                                    <p class="text-muted">
                                                        <small><strong>Expires:</strong> <?php echo date('M d, Y', strtotime($sub['end_date'])); ?></small>
                                                    </p>
                                                    <a href="/student/materials.php?subject_id=<?php echo $sub['subject_id']; ?>" class="btn btn-sm btn-primary">
                                                        View Materials
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Live Classes -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Upcoming Live Classes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Title</th>
                                                <th>Teacher</th>
                                                <th>Scheduled At</th>
                                                <th>Duration</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcoming_classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['title']); ?></td>
                                                <td><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($class['scheduled_at'])); ?></td>
                                                <td><?php echo $class['duration']; ?> min</td>
                                                <td>
                                                    <?php if ($class['class_url'] && $class['status'] === 'ongoing'): ?>
                                                    <a href="<?php echo htmlspecialchars($class['class_url']); ?>" class="btn btn-sm btn-success" target="_blank">
                                                        <i class="bi bi-camera-video"></i> Join Now
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="badge bg-primary"><?php echo ucfirst($class['status']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($upcoming_classes)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No upcoming classes</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('show')) {
                icon.classList.remove('bi-list');
                icon.classList.add('bi-x');
            } else {
                icon.classList.remove('bi-x');
                icon.classList.add('bi-list');
            }
        });
        
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            const icon = sidebarToggle.querySelector('i');
            icon.classList.remove('bi-x');
            icon.classList.add('bi-list');
        });
    </script>
</body>
</html>
