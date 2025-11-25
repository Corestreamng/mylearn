<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$conn = getDBConnection();

// Get student's subscribed subjects
$subjects = [];
$stmt = $conn->prepare("SELECT DISTINCT s.* 
    FROM subjects s 
    JOIN subscriptions sub ON s.subject_id = sub.subject_id 
    WHERE sub.student_id = ? AND sub.status = 'active'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Get progress data for each subject
$progress_data = [];
foreach ($subjects as $subject) {
    // Get total materials for this subject
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM learning_materials WHERE subject_id = ? AND status = 'active'");
    $stmt->bind_param("i", $subject['subject_id']);
    $stmt->execute();
    $total_materials = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Get completed materials
    $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM learning_progress lp 
        JOIN learning_materials lm ON lp.material_id = lm.material_id 
        WHERE lp.student_id = ? AND lm.subject_id = ? AND lp.completed = 1");
    $stmt->bind_param("ii", $_SESSION['user_id'], $subject['subject_id']);
    $stmt->execute();
    $completed_materials = $stmt->get_result()->fetch_assoc()['completed'];
    $stmt->close();
    
    // Get in-progress materials
    $stmt = $conn->prepare("SELECT COUNT(*) as in_progress FROM learning_progress lp 
        JOIN learning_materials lm ON lp.material_id = lm.material_id 
        WHERE lp.student_id = ? AND lm.subject_id = ? AND lp.completed = 0");
    $stmt->bind_param("ii", $_SESSION['user_id'], $subject['subject_id']);
    $stmt->execute();
    $in_progress = $stmt->get_result()->fetch_assoc()['in_progress'];
    $stmt->close();
    
    // Get materials by type with completion
    $stmt = $conn->prepare("SELECT 
        lm.material_type,
        COUNT(*) as total,
        SUM(CASE WHEN lp.completed = 1 THEN 1 ELSE 0 END) as completed
        FROM learning_materials lm
        LEFT JOIN learning_progress lp ON lm.material_id = lp.material_id AND lp.student_id = ?
        WHERE lm.subject_id = ? AND lm.status = 'active'
        GROUP BY lm.material_type");
    $stmt->bind_param("ii", $_SESSION['user_id'], $subject['subject_id']);
    $stmt->execute();
    $by_type = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $percentage = $total_materials > 0 ? round(($completed_materials / $total_materials) * 100) : 0;
    
    $progress_data[] = [
        'subject' => $subject,
        'total_materials' => $total_materials,
        'completed_materials' => $completed_materials,
        'in_progress' => $in_progress,
        'percentage' => $percentage,
        'by_type' => $by_type
    ];
}

// Get overall stats
$overall_completed = 0;
$overall_total = 0;
foreach ($progress_data as $pd) {
    $overall_completed += $pd['completed_materials'];
    $overall_total += $pd['total_materials'];
}
$overall_percentage = $overall_total > 0 ? round(($overall_completed / $overall_total) * 100) : 0;

// Get recent activity
$recent_activity = [];
$stmt = $conn->prepare("SELECT lp.*, lm.title, lm.material_type, s.subject_name 
    FROM learning_progress lp 
    JOIN learning_materials lm ON lp.material_id = lm.material_id 
    JOIN subjects s ON lm.subject_id = s.subject_id 
    WHERE lp.student_id = ? 
    ORDER BY lp.updated_at DESC LIMIT 10");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}
$stmt->close();

// Get achievements/milestones
$achievements = [];
if ($overall_percentage >= 25) $achievements[] = ['icon' => 'bi-star', 'title' => 'Getting Started', 'desc' => 'Completed 25% of materials', 'color' => 'warning'];
if ($overall_percentage >= 50) $achievements[] = ['icon' => 'bi-star-half', 'title' => 'Halfway There', 'desc' => 'Completed 50% of materials', 'color' => 'info'];
if ($overall_percentage >= 75) $achievements[] = ['icon' => 'bi-star-fill', 'title' => 'Almost Done', 'desc' => 'Completed 75% of materials', 'color' => 'primary'];
if ($overall_percentage >= 100) $achievements[] = ['icon' => 'bi-trophy', 'title' => 'Champion', 'desc' => 'Completed all materials!', 'color' => 'success'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress - MyLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .progress-circle-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }
        .progress-circle-bg {
            fill: none;
            stroke: #e9ecef;
            stroke-width: 12;
        }
        .progress-circle-fill {
            fill: none;
            stroke-width: 12;
            stroke-linecap: round;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            transition: stroke-dashoffset 1s ease-in-out;
        }
        .progress-circle-text {
            font-size: 2rem;
            font-weight: bold;
            fill: #333;
        }
        .progress-circle-label {
            font-size: 0.8rem;
            fill: #6c757d;
        }
        .progress-excellent { stroke: url(#gradient-excellent); }
        .progress-good { stroke: url(#gradient-good); }
        .progress-average { stroke: url(#gradient-average); }
        .progress-needs-work { stroke: url(#gradient-needs-work); }
        
        .achievement-card {
            transition: all 0.3s ease;
        }
        .achievement-card:hover {
            transform: scale(1.05);
        }
        .achievement-icon {
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <!-- SVG Gradients for Progress Circles -->
    <svg width="0" height="0">
        <defs>
            <linearGradient id="gradient-excellent" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" style="stop-color:#28a745"/>
                <stop offset="100%" style="stop-color:#20c997"/>
            </linearGradient>
            <linearGradient id="gradient-good" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" style="stop-color:#667eea"/>
                <stop offset="100%" style="stop-color:#764ba2"/>
            </linearGradient>
            <linearGradient id="gradient-average" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" style="stop-color:#ffc107"/>
                <stop offset="100%" style="stop-color:#fd7e14"/>
            </linearGradient>
            <linearGradient id="gradient-needs-work" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" style="stop-color:#dc3545"/>
                <stop offset="100%" style="stop-color:#fd7e14"/>
            </linearGradient>
        </defs>
    </svg>
    
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
                            <a class="nav-link" href="/student/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/student/progress.php">
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
                    <h2><i class="bi bi-graph-up"></i> My Learning Progress</h2>
                    <p class="text-muted">Track your achievements and progress</p>
                </div>

                <!-- Overall Progress -->
                <div class="row mb-4">
                    <div class="col-md-4 text-center">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Overall Progress</h6>
                                <div class="progress-circle-container">
                                    <svg width="150" height="150">
                                        <circle class="progress-circle-bg" cx="75" cy="75" r="60"></circle>
                                        <circle class="progress-circle-fill <?php 
                                            if ($overall_percentage >= 80) echo 'progress-excellent';
                                            elseif ($overall_percentage >= 60) echo 'progress-good';
                                            elseif ($overall_percentage >= 40) echo 'progress-average';
                                            else echo 'progress-needs-work';
                                        ?>" 
                                            cx="75" cy="75" r="60"
                                            stroke-dasharray="377"
                                            stroke-dashoffset="<?php echo 377 - (377 * $overall_percentage / 100); ?>">
                                        </circle>
                                        <text x="75" y="70" text-anchor="middle" class="progress-circle-text">
                                            <?php echo $overall_percentage; ?>%
                                        </text>
                                        <text x="75" y="90" text-anchor="middle" class="progress-circle-label">
                                            Complete
                                        </text>
                                    </svg>
                                </div>
                                <p class="mt-3 mb-0">
                                    <strong><?php echo $overall_completed; ?></strong> of <strong><?php echo $overall_total; ?></strong> materials completed
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-trophy"></i> Achievements</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($achievements)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-star display-4 text-muted"></i>
                                    <p class="text-muted mt-2">Complete more materials to unlock achievements!</p>
                                </div>
                                <?php else: ?>
                                <div class="row">
                                    <?php foreach ($achievements as $achievement): ?>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="card achievement-card text-center bg-<?php echo $achievement['color']; ?> bg-opacity-10">
                                            <div class="card-body py-3">
                                                <i class="bi <?php echo $achievement['icon']; ?> achievement-icon text-<?php echo $achievement['color']; ?>"></i>
                                                <h6 class="mt-2 mb-1"><?php echo $achievement['title']; ?></h6>
                                                <small class="text-muted"><?php echo $achievement['desc']; ?></small>
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

                <!-- Subject Progress -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-book"></i> Subject Progress</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($progress_data)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> You don't have any active subscriptions yet. 
                                    Ask your parent to enroll you in subjects.
                                </div>
                                <?php else: ?>
                                <div class="row">
                                    <?php foreach ($progress_data as $pd): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($pd['subject']['subject_name']); ?></h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row align-items-center mb-3">
                                                    <div class="col-8">
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar progress-bar-gradient" role="progressbar" 
                                                                 style="width: <?php echo $pd['percentage']; ?>%"
                                                                 aria-valuenow="<?php echo $pd['percentage']; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                                <?php echo $pd['percentage']; ?>%
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4 text-end">
                                                        <span class="badge bg-primary"><?php echo $pd['completed_materials']; ?>/<?php echo $pd['total_materials']; ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="row text-center">
                                                    <div class="col-4">
                                                        <small class="text-success"><strong><?php echo $pd['completed_materials']; ?></strong></small>
                                                        <br><small class="text-muted">Completed</small>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-warning"><strong><?php echo $pd['in_progress']; ?></strong></small>
                                                        <br><small class="text-muted">In Progress</small>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-secondary"><strong><?php echo max(0, $pd['total_materials'] - $pd['completed_materials'] - $pd['in_progress']); ?></strong></small>
                                                        <br><small class="text-muted">Not Started</small>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($pd['by_type'])): ?>
                                                <hr>
                                                <small class="text-muted d-block mb-2">By Type:</small>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <?php foreach ($pd['by_type'] as $type): ?>
                                                    <span class="badge bg-light text-dark">
                                                        <?php
                                                        $icons = ['video' => 'bi-play-circle', 'audio' => 'bi-music-note', 'document' => 'bi-file-pdf', 'text' => 'bi-file-text'];
                                                        echo '<i class="bi ' . ($icons[$type['material_type']] ?? 'bi-file') . '"></i> ';
                                                        echo ucfirst($type['material_type']) . ': ' . intval($type['completed']) . '/' . $type['total'];
                                                        ?>
                                                    </span>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-footer bg-white">
                                                <a href="/student/materials.php?subject_id=<?php echo $pd['subject']['subject_id']; ?>" class="btn btn-sm btn-primary w-100">
                                                    <i class="bi bi-book"></i> Continue Learning
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

                <!-- Recent Activity -->
                <?php if (!empty($recent_activity)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Material</th>
                                                <th>Subject</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Time Spent</th>
                                                <th>Last Accessed</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_activity as $activity): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['subject_name']); ?></td>
                                                <td>
                                                    <?php
                                                    $type_icons = [
                                                        'video' => '<i class="bi bi-play-circle text-danger"></i>',
                                                        'audio' => '<i class="bi bi-music-note text-primary"></i>',
                                                        'document' => '<i class="bi bi-file-pdf text-warning"></i>',
                                                        'text' => '<i class="bi bi-file-text text-info"></i>'
                                                    ];
                                                    echo ($type_icons[$activity['material_type']] ?? '') . ' ' . ucfirst($activity['material_type']);
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($activity['completed']): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Completed</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-warning"><i class="bi bi-hourglass-split"></i> In Progress</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $mins = floor($activity['time_spent'] / 60);
                                                    $secs = $activity['time_spent'] % 60;
                                                    echo $mins > 0 ? "{$mins}m {$secs}s" : "{$secs}s";
                                                    ?>
                                                </td>
                                                <td><?php echo $activity['last_accessed'] ? date('M d, Y H:i', strtotime($activity['last_accessed'])) : 'N/A'; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
