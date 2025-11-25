<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('parent');

$conn = getDBConnection();

// Get parent's children
$children = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE parent_id = ? AND user_type = 'student'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}
$stmt->close();

// Get total active subscriptions
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE parent_id = ? AND status = 'active'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$active_subscriptions = $result->fetch_assoc()['count'];
$stmt->close();

// Get available subjects
$result = $conn->query("SELECT COUNT(*) as count FROM subjects WHERE status = 'active'");
$available_subjects = $result->fetch_assoc()['count'];

// Get recent subscriptions
$recent_subscriptions = [];
$stmt = $conn->prepare("SELECT sub.*, s.full_name as student_name, subj.subject_name 
    FROM subscriptions sub
    JOIN users s ON sub.student_id = s.user_id
    JOIN subjects subj ON sub.subject_id = subj.subject_id
    WHERE sub.parent_id = ?
    ORDER BY sub.created_at DESC LIMIT 5");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_subscriptions[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - MyLearn</title>
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
                        <small class="text-muted">Parent Panel</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="/parent/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/parent/children.php">
                                <i class="bi bi-people"></i> My Children
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/parent/progress.php">
                                <i class="bi bi-graph-up"></i> Learning Progress
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/parent/enroll.php">
                                <i class="bi bi-plus-circle"></i> Enroll Child
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/parent/subscriptions.php">
                                <i class="bi bi-credit-card"></i> Subscriptions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/parent/notifications.php">
                                <i class="bi bi-bell"></i> Notifications
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
                    <p class="text-muted">Parent Dashboard</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <h6 class="text-muted">My Children</h6>
                                <h2><?php echo count($children); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <h6 class="text-muted">Active Subscriptions</h6>
                                <h2><?php echo $active_subscriptions; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <h6 class="text-muted">Available Subjects</h6>
                                <h2><?php echo $available_subjects; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Children Overview -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>My Children</h5>
                                <a href="/parent/children.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg"></i> Add Child
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($children)): ?>
                                    <p class="text-muted">No children registered yet. <a href="/parent/children.php">Add a child</a> to get started.</p>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($children as $child): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card material-card">
                                                <div class="card-body">
                                                    <h5><?php echo htmlspecialchars($child['full_name']); ?></h5>
                                                    <p class="text-muted"><?php echo htmlspecialchars($child['email']); ?></p>
                                                    <span class="badge bg-<?php echo $child['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($child['status']); ?>
                                                    </span>
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

                <!-- Recent Subscriptions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Subscriptions</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Subject</th>
                                                <th>Duration</th>
                                                <th>Amount</th>
                                                <th>End Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_subscriptions as $sub): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($sub['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                                                <td><?php echo $sub['duration_months']; ?> month(s)</td>
                                                <td>$<?php echo number_format($sub['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($sub['end_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $sub['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($sub['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($recent_subscriptions)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No subscriptions yet</td>
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
