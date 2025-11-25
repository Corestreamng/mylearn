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

// Get progress data for each child
$progress_data = [];
foreach ($children as $child) {
    // Get subscribed subjects
    $stmt = $conn->prepare("SELECT DISTINCT s.* 
        FROM subjects s 
        JOIN subscriptions sub ON s.subject_id = sub.subject_id 
        WHERE sub.student_id = ? AND sub.status = 'active'");
    $stmt->bind_param("i", $child['user_id']);
    $stmt->execute();
    $subjects_result = $stmt->get_result();
    
    $child_subjects = [];
    while ($subject = $subjects_result->fetch_assoc()) {
        // Get total materials for this subject
        $mat_stmt = $conn->prepare("SELECT COUNT(*) as total FROM learning_materials WHERE subject_id = ? AND status = 'active'");
        $mat_stmt->bind_param("i", $subject['subject_id']);
        $mat_stmt->execute();
        $total_materials = $mat_stmt->get_result()->fetch_assoc()['total'];
        $mat_stmt->close();
        
        // Get completed materials for this student in this subject
        $prog_stmt = $conn->prepare("SELECT COUNT(*) as completed FROM learning_progress lp 
            JOIN learning_materials lm ON lp.material_id = lm.material_id 
            WHERE lp.student_id = ? AND lm.subject_id = ? AND lp.completed = 1");
        $prog_stmt->bind_param("ii", $child['user_id'], $subject['subject_id']);
        $prog_stmt->execute();
        $completed_materials = $prog_stmt->get_result()->fetch_assoc()['completed'];
        $prog_stmt->close();
        
        // Calculate percentage
        $percentage = $total_materials > 0 ? round(($completed_materials / $total_materials) * 100) : 0;
        
        $child_subjects[] = [
            'subject' => $subject,
            'total_materials' => $total_materials,
            'completed_materials' => $completed_materials,
            'percentage' => $percentage
        ];
    }
    $stmt->close();
    
    // Get overall progress for this child
    $overall_stmt = $conn->prepare("SELECT 
        (SELECT COUNT(*) FROM learning_progress WHERE student_id = ? AND completed = 1) as completed,
        (SELECT COUNT(DISTINCT lm.material_id) FROM learning_materials lm 
         JOIN subscriptions sub ON lm.subject_id = sub.subject_id 
         WHERE sub.student_id = ? AND sub.status = 'active' AND lm.status = 'active') as total");
    $overall_stmt->bind_param("ii", $child['user_id'], $child['user_id']);
    $overall_stmt->execute();
    $overall = $overall_stmt->get_result()->fetch_assoc();
    $overall_stmt->close();
    
    $overall_percentage = $overall['total'] > 0 ? round(($overall['completed'] / $overall['total']) * 100) : 0;
    
    // Get recent activity
    $activity_stmt = $conn->prepare("SELECT lp.*, lm.title, lm.material_type, s.subject_name 
        FROM learning_progress lp 
        JOIN learning_materials lm ON lp.material_id = lm.material_id 
        JOIN subjects s ON lm.subject_id = s.subject_id 
        WHERE lp.student_id = ? 
        ORDER BY lp.updated_at DESC LIMIT 5");
    $activity_stmt->bind_param("i", $child['user_id']);
    $activity_stmt->execute();
    $recent_activity = $activity_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $activity_stmt->close();
    
    $progress_data[$child['user_id']] = [
        'child' => $child,
        'subjects' => $child_subjects,
        'overall_completed' => $overall['completed'] ?? 0,
        'overall_total' => $overall['total'] ?? 0,
        'overall_percentage' => $overall_percentage,
        'recent_activity' => $recent_activity
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Progress - MyLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .progress-circle-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }
        .progress-circle-bg {
            fill: none;
            stroke: #e9ecef;
            stroke-width: 10;
        }
        .progress-circle-fill {
            fill: none;
            stroke-width: 10;
            stroke-linecap: round;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            transition: stroke-dashoffset 1s ease-in-out;
        }
        .progress-circle-text {
            font-size: 1.5rem;
            font-weight: bold;
            fill: #333;
        }
        .progress-excellent { stroke: #28a745; }
        .progress-good { stroke: #667eea; }
        .progress-average { stroke: #ffc107; }
        .progress-needs-work { stroke: #dc3545; }
    </style>
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
                            <a class="nav-link active" href="/parent/progress.php">
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
                    <h2><i class="bi bi-graph-up"></i> Learning Progress</h2>
                    <p class="text-muted">Track your children's learning journey</p>
                </div>

                <?php if (empty($children)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> You haven't added any children yet. 
                    <a href="/parent/children.php">Add a child</a> to start tracking progress.
                </div>
                <?php else: ?>
                
                <?php foreach ($progress_data as $data): ?>
                <div class="card mb-4 learning-progress-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($data['child']['full_name']); ?>
                        </h5>
                        <span class="badge bg-light text-dark">
                            <?php echo $data['overall_completed']; ?>/<?php echo $data['overall_total']; ?> Materials
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Overall Progress Circle -->
                            <div class="col-md-3 text-center mb-4">
                                <h6 class="text-muted mb-3">Overall Progress</h6>
                                <div class="progress-circle-container">
                                    <svg width="120" height="120">
                                        <circle class="progress-circle-bg" cx="60" cy="60" r="50"></circle>
                                        <circle class="progress-circle-fill <?php 
                                            if ($data['overall_percentage'] >= 80) echo 'progress-excellent';
                                            elseif ($data['overall_percentage'] >= 60) echo 'progress-good';
                                            elseif ($data['overall_percentage'] >= 40) echo 'progress-average';
                                            else echo 'progress-needs-work';
                                        ?>" 
                                            cx="60" cy="60" r="50"
                                            stroke-dasharray="314"
                                            stroke-dashoffset="<?php echo 314 - (314 * $data['overall_percentage'] / 100); ?>">
                                        </circle>
                                        <text x="60" y="65" text-anchor="middle" class="progress-circle-text">
                                            <?php echo $data['overall_percentage']; ?>%
                                        </text>
                                    </svg>
                                </div>
                                <?php
                                if ($data['overall_percentage'] >= 80) {
                                    echo '<span class="badge bg-success mt-2">Excellent!</span>';
                                } elseif ($data['overall_percentage'] >= 60) {
                                    echo '<span class="badge bg-primary mt-2">Good Progress</span>';
                                } elseif ($data['overall_percentage'] >= 40) {
                                    echo '<span class="badge bg-warning mt-2">Keep Going</span>';
                                } else {
                                    echo '<span class="badge bg-danger mt-2">Needs Attention</span>';
                                }
                                ?>
                            </div>
                            
                            <!-- Subject Progress -->
                            <div class="col-md-9">
                                <h6 class="text-muted mb-3">Subject-wise Progress</h6>
                                <?php if (empty($data['subjects'])): ?>
                                <p class="text-muted">No active subscriptions yet.</p>
                                <?php else: ?>
                                <?php foreach ($data['subjects'] as $subj): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span><strong><?php echo htmlspecialchars($subj['subject']['subject_name']); ?></strong></span>
                                        <span class="text-muted small">
                                            <?php echo $subj['completed_materials']; ?>/<?php echo $subj['total_materials']; ?> materials
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar progress-bar-gradient" role="progressbar" 
                                             style="width: <?php echo $subj['percentage']; ?>%"
                                             aria-valuenow="<?php echo $subj['percentage']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($data['recent_activity'])): ?>
                        <hr>
                        <h6 class="text-muted"><i class="bi bi-clock-history"></i> Recent Activity</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th>Subject</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Last Accessed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['recent_activity'] as $activity): ?>
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
                                            <span class="badge bg-success"><i class="bi bi-check"></i> Completed</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning">In Progress</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $activity['last_accessed'] ? date('M d, Y H:i', strtotime($activity['last_accessed'])) : 'N/A'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
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
