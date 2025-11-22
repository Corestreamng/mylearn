<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('parent');

$conn = getDBConnection();

// Get all subscriptions
$subscriptions = [];
$result = $conn->query("SELECT sub.*, 
    s.full_name as student_name,
    subj.subject_name,
    u.full_name as teacher_name
    FROM subscriptions sub
    JOIN users s ON sub.student_id = s.user_id
    JOIN subjects subj ON sub.subject_id = subj.subject_id
    LEFT JOIN users u ON subj.teacher_id = u.user_id
    WHERE sub.parent_id = {$_SESSION['user_id']}
    ORDER BY sub.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $subscriptions[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions - MyLearn</title>
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
                        <small class="text-muted">Parent Panel</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/parent/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/parent/children.php">
                                <i class="bi bi-people"></i> My Children
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/parent/enroll.php">
                                <i class="bi bi-plus-circle"></i> Enroll Child
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/parent/subscriptions.php">
                                <i class="bi bi-credit-card"></i> Subscriptions
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
                    <h2>Subscriptions</h2>
                    <p class="text-muted">View and manage all subscriptions</p>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Duration</th>
                                        <th>Amount</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptions as $sub): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sub['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                                        <td><?php echo $sub['teacher_name'] ? htmlspecialchars($sub['teacher_name']) : 'N/A'; ?></td>
                                        <td><?php echo $sub['duration_months']; ?> month(s)</td>
                                        <td>$<?php echo number_format($sub['amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($sub['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($sub['end_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $sub['payment_status'] === 'completed' ? 'success' : ($sub['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($sub['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $sub['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($sub['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($subscriptions)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No subscriptions yet</td>
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
