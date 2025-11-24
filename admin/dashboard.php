<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$conn = getDBConnection();

// Get statistics
$stats = [];

// Total users by type
$result = $conn->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
while ($row = $result->fetch_assoc()) {
    $stats[$row['user_type']] = $row['count'];
}

// Total subjects
$result = $conn->query("SELECT COUNT(*) as count FROM subjects WHERE status = 'active'");
$stats['subjects'] = $result->fetch_assoc()['count'];

// Total materials
$result = $conn->query("SELECT COUNT(*) as count FROM learning_materials WHERE status = 'active'");
$stats['materials'] = $result->fetch_assoc()['count'];

// Total active subscriptions
$result = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
$stats['subscriptions'] = $result->fetch_assoc()['count'];

// Get data for charts
// Monthly subscriptions for last 6 months
$monthly_subscriptions = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $result = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE DATE_FORMAT(start_date, '%Y-%m') = '$month'");
    $monthly_subscriptions[$month] = $result->fetch_assoc()['count'];
}

// Revenue data
$result = $conn->query("SELECT SUM(amount) as total FROM subscriptions WHERE payment_status = 'completed'");
$total_revenue = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT SUM(amount) as total FROM subscriptions WHERE payment_status = 'completed' AND MONTH(start_date) = MONTH(CURRENT_DATE())");
$monthly_revenue = $result->fetch_assoc()['total'] ?? 0;

// Subscription status breakdown
$subscription_status = [];
$result = $conn->query("SELECT status, COUNT(*) as count FROM subscriptions GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $subscription_status[$row['status']] = $row['count'];
}

// Materials by type
$materials_by_type = [];
$result = $conn->query("SELECT material_type, COUNT(*) as count FROM learning_materials WHERE status = 'active' GROUP BY material_type");
while ($row = $result->fetch_assoc()) {
    $materials_by_type[$row['material_type']] = $row['count'];
}

// Recent activities
$activities = [];
$result = $conn->query("SELECT al.*, u.full_name FROM activity_logs al JOIN users u ON al.user_id = u.user_id ORDER BY al.created_at DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MyLearn</title>
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
                            <a class="nav-link active" href="/admin/dashboard.php">
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
                <div class="dashboard-header">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
                    <p class="text-muted">Administrator Dashboard</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <h6 class="text-muted">Teachers</h6>
                                <h2><?php echo $stats['teacher'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <h6 class="text-muted">Students</h6>
                                <h2><?php echo $stats['student'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <h6 class="text-muted">Subjects</h6>
                                <h2><?php echo $stats['subjects']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card info">
                            <div class="card-body">
                                <h6 class="text-muted">Active Subscriptions</h6>
                                <h2><?php echo $stats['subscriptions']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card stat-card danger">
                            <div class="card-body">
                                <h6 class="text-muted">Parents</h6>
                                <h2><?php echo $stats['parent'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <h6 class="text-muted">Learning Materials</h6>
                                <h2><?php echo $stats['materials']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Analytics Section -->
                <div class="row mb-4">
                    <div class="col-md-8 mb-3">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Subscriptions Trend (Last 6 Months)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="subscriptionsChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Materials Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="materialsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Revenue Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h6 class="text-muted">Total Revenue</h6>
                                        <h3 class="text-success">₦<?php echo number_format($total_revenue, 2); ?></h3>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted">This Month</h6>
                                        <h3 class="text-primary">₦<?php echo number_format($monthly_revenue, 2); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Subscription Status</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Activities</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Action</th>
                                                <th>Description</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activities as $activity): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($activities)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No activities yet</td>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Subscriptions Trend Chart
        const subscriptionsCtx = document.getElementById('subscriptionsChart').getContext('2d');
        new Chart(subscriptionsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($monthly_subscriptions)); ?>,
                datasets: [{
                    label: 'Subscriptions',
                    data: <?php echo json_encode(array_values($monthly_subscriptions)); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Materials Distribution Chart
        const materialsCtx = document.getElementById('materialsChart').getContext('2d');
        new Chart(materialsCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map('ucfirst', array_keys($materials_by_type))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($materials_by_type)); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Subscription Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map('ucfirst', array_keys($subscription_status))); ?>,
                datasets: [{
                    label: 'Count',
                    data: <?php echo json_encode(array_values($subscription_status)); ?>,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
