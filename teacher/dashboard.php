<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('teacher');

$conn = getDBConnection();

// Get teacher's subjects
$subjects = [];
$result = $conn->query("SELECT * FROM subjects WHERE teacher_id = {$_SESSION['user_id']} AND status = 'active'");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// Get total materials uploaded
$result = $conn->query("SELECT COUNT(*) as count FROM learning_materials WHERE uploaded_by = {$_SESSION['user_id']}");
$total_materials = $result->fetch_assoc()['count'];

// Get upcoming live classes
$upcoming_classes = [];
$result = $conn->query("SELECT lc.*, s.subject_name FROM live_classes lc 
    JOIN subjects s ON lc.subject_id = s.subject_id 
    WHERE lc.teacher_id = {$_SESSION['user_id']} 
    AND lc.scheduled_at > NOW() 
    AND lc.status = 'scheduled'
    ORDER BY lc.scheduled_at ASC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $upcoming_classes[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - MyLearn</title>
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
                        <small class="text-muted">Teacher Panel</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="/teacher/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/teacher/materials.php">
                                <i class="bi bi-file-earmark-text"></i> Learning Materials
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/teacher/live_classes.php">
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
                    <p class="text-muted">Teacher Dashboard</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <h6 class="text-muted">My Subjects</h6>
                                <h2><?php echo count($subjects); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <h6 class="text-muted">Materials Uploaded</h6>
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
                                <h5>My Subjects</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($subjects)): ?>
                                    <p class="text-muted">No subjects assigned yet.</p>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($subjects as $subject): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card material-card">
                                                <div class="card-body">
                                                    <h5><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                                    <p class="text-muted"><?php echo htmlspecialchars(substr($subject['description'], 0, 100)); ?></p>
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
                                                <th>Scheduled At</th>
                                                <th>Duration</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcoming_classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['title']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($class['scheduled_at'])); ?></td>
                                                <td><?php echo $class['duration']; ?> min</td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo ucfirst($class['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($upcoming_classes)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No upcoming classes</td>
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
</body>
</html>
