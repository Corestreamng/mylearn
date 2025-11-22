<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$conn = getDBConnection();

// Get student's subscribed subjects
$subscribed_subjects = [];
$result = $conn->query("SELECT DISTINCT subj.subject_id, subj.subject_name
    FROM subscriptions sub
    JOIN subjects subj ON sub.subject_id = subj.subject_id
    WHERE sub.student_id = {$_SESSION['user_id']} AND sub.status = 'active'");
while ($row = $result->fetch_assoc()) {
    $subscribed_subjects[] = $row;
}

// Get materials for subscribed subjects
$materials = [];
if (!empty($subscribed_subjects)) {
    $subject_ids = array_column($subscribed_subjects, 'subject_id');
    $ids_string = implode(',', $subject_ids);
    
    // Filter by subject if provided
    $filter = '';
    if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])) {
        $subject_id = intval($_GET['subject_id']);
        $filter = " AND lm.subject_id = $subject_id";
    }
    
    $result = $conn->query("SELECT lm.*, s.subject_name, u.full_name as uploaded_by_name
        FROM learning_materials lm
        JOIN subjects s ON lm.subject_id = s.subject_id
        JOIN users u ON lm.uploaded_by = u.user_id
        WHERE lm.subject_id IN ($ids_string) AND lm.status = 'active' $filter
        ORDER BY lm.uploaded_at DESC");
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Materials - MyLearn</title>
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
                            <a class="nav-link active" href="/student/materials.php">
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
                    <h2>Learning Materials</h2>
                    <p class="text-muted">Access all your learning content</p>
                </div>

                <?php if (empty($subscribed_subjects)): ?>
                    <div class="alert alert-info">
                        You don't have any active subscriptions. Ask your parent to enroll you in subjects.
                    </div>
                <?php else: ?>
                    <!-- Filter by Subject -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" class="row g-3">
                                <div class="col-auto">
                                    <label class="col-form-label">Filter by Subject:</label>
                                </div>
                                <div class="col-auto">
                                    <select class="form-select" name="subject_id" onchange="this.form.submit()">
                                        <option value="">All Subjects</option>
                                        <?php foreach ($subscribed_subjects as $subject): ?>
                                        <option value="<?php echo $subject['subject_id']; ?>" <?php echo isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['subject_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Materials Grid -->
                    <div class="row">
                        <?php foreach ($materials as $material): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card material-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-info">
                                            <?php echo ucfirst($material['material_type']); ?>
                                        </span>
                                        <small class="text-muted"><?php echo htmlspecialchars($material['subject_name']); ?></small>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars(substr($material['description'], 0, 100)); ?>...</p>
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($material['uploaded_by_name']); ?><br>
                                        <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($material['uploaded_at'])); ?>
                                    </small>
                                    <hr>
                                    <?php if ($material['material_type'] === 'text'): ?>
                                        <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#textModal<?php echo $material['material_id']; ?>">
                                            <i class="bi bi-eye"></i> Read Content
                                        </button>
                                    <?php elseif ($material['file_path']): ?>
                                        <?php if ($material['material_type'] === 'video'): ?>
                                            <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#videoModal<?php echo $material['material_id']; ?>">
                                                <i class="bi bi-play-circle"></i> Play Video
                                            </button>
                                        <?php elseif ($material['material_type'] === 'audio'): ?>
                                            <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#audioModal<?php echo $material['material_id']; ?>">
                                                <i class="bi bi-music-note"></i> Play Audio
                                            </button>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($material['file_path']); ?>" class="btn btn-primary btn-sm w-100" target="_blank">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Modals for viewing content -->
                        <?php if ($material['material_type'] === 'text'): ?>
                        <div class="modal fade" id="textModal<?php echo $material['material_id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><?php echo nl2br(htmlspecialchars($material['content'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($material['material_type'] === 'video' && $material['file_path']): ?>
                        <div class="modal fade" id="videoModal<?php echo $material['material_id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <video controls class="w-100">
                                            <source src="<?php echo htmlspecialchars($material['file_path']); ?>">
                                            Your browser does not support video playback.
                                        </video>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($material['material_type'] === 'audio' && $material['file_path']): ?>
                        <div class="modal fade" id="audioModal<?php echo $material['material_id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <audio controls class="w-100">
                                            <source src="<?php echo htmlspecialchars($material['file_path']); ?>">
                                            Your browser does not support audio playback.
                                        </audio>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php endforeach; ?>
                        <?php if (empty($materials)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                No learning materials available yet.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
