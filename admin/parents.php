<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$conn = getDBConnection();

// Get all parents with their children count
$parents = [];
$result = $conn->query("SELECT u.*, 
    (SELECT COUNT(*) FROM users WHERE parent_id = u.user_id) as children_count
    FROM users u WHERE user_type = 'parent' ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $parents[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parents - MyLearn</title>
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
                            <a class="nav-link" href="/admin/subjects.php">
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
                            <a class="nav-link active" href="/admin/parents.php">
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
                <div class="dashboard-header">
                    <h2>Manage Parents</h2>
                    <p class="text-muted">View parent accounts</p>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Children</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parents as $parent): ?>
                                    <tr>
                                        <td><?php echo $parent['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($parent['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($parent['email']); ?></td>
                                        <td><?php echo $parent['children_count']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $parent['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($parent['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($parent['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($parents)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No parents found</td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
