<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$conn = getDBConnection();

// Get student's subscribed subjects
$subscribed_subjects = [];
$stmt = $conn->prepare("SELECT DISTINCT subj.subject_id
    FROM subscriptions sub
    JOIN subjects subj ON sub.subject_id = subj.subject_id
    WHERE sub.student_id = ? AND sub.status = 'active'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subscribed_subjects[] = $row['subject_id'];
}
$stmt->close();

// Get live classes for subscribed subjects
$live_classes = [];
if (!empty($subscribed_subjects)) {
    // Safe: Each ID is explicitly cast to integer to prevent SQL injection
    $subject_ids = implode(',', array_map('intval', $subscribed_subjects));
    
    $result = $conn->query("SELECT lc.*, s.subject_name, u.full_name as teacher_name
        FROM live_classes lc
        JOIN subjects s ON lc.subject_id = s.subject_id
        JOIN users u ON lc.teacher_id = u.user_id
        WHERE lc.subject_id IN ($subject_ids)
        ORDER BY lc.scheduled_at DESC");
    while ($row = $result->fetch_assoc()) {
        $live_classes[] = $row;
    }
}

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
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">MyLearn</h4>
                        <small class="text-muted">Student Panel</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/student/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/student/materials.php">
                                <i class="bi bi-file-earmark-text"></i> Learning Materials
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/student/live_classes.php">
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
                    <h2>Live Classes</h2>
                    <p class="text-muted">View and join scheduled live classes</p>
                </div>

                <?php if (empty($subscribed_subjects)): ?>
                    <div class="alert alert-info">
                        You don't have any active subscriptions. Ask your parent to enroll you in subjects.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Title</th>
                                            <th>Teacher</th>
                                            <th>Description</th>
                                            <th>Scheduled At</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($live_classes as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['title']); ?></td>
                                            <td><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($class['description'], 0, 50)); ?>...</td>
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
                                            <td>
                                                <?php if ($class['class_url']): ?>
                                                    <?php if ($class['status'] === 'ongoing'): ?>
                                                    <a href="<?php echo htmlspecialchars($class['class_url']); ?>" class="btn btn-sm btn-success" target="_blank">
                                                        <i class="bi bi-camera-video"></i> Join Now
                                                    </a>
                                                    <?php elseif ($class['status'] === 'scheduled'): ?>
                                                    <a href="<?php echo htmlspecialchars($class['class_url']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="bi bi-link-45deg"></i> Class Link
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                <span class="text-muted">No link</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($live_classes)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No live classes scheduled</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
