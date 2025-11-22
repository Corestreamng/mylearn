<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('teacher');

$conn = getDBConnection();
$message = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $subject_id = intval($_POST['subject_id']);
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $material_type = sanitizeInput($_POST['material_type']);
    $content = sanitizeInput($_POST['content']);
    $file_path = '';
    
    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_dir = __DIR__ . '/../assets/uploads/';
        $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['file']['size'];
        $max_size = 50 * 1024 * 1024; // 50MB
        
        // Allowed file types
        $allowed_video = ['mp4', 'avi', 'mov', 'webm'];
        $allowed_audio = ['mp3', 'wav', 'ogg'];
        $allowed_docs = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
        
        // Validate file size
        if ($file_size > $max_size) {
            $message = '<div class="alert alert-danger">File size exceeds 50MB limit.</div>';
        } else {
            // Determine subfolder based on type and validate
            $subfolder = '';
            $is_valid = false;
            
            if (in_array($file_ext, $allowed_video)) {
                $subfolder = 'videos/';
                $is_valid = true;
            } elseif (in_array($file_ext, $allowed_audio)) {
                $subfolder = 'audios/';
                $is_valid = true;
            } elseif (in_array($file_ext, $allowed_docs)) {
                $subfolder = 'documents/';
                $is_valid = true;
            }
            
            if (!$is_valid) {
                $message = '<div class="alert alert-danger">Invalid file type. Allowed: videos, audio, PDF, Word, PowerPoint.</div>';
            } else {
                $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $subfolder . $new_filename;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
                    $file_path = '/assets/uploads/' . $subfolder . $new_filename;
                } else {
                    $message = '<div class="alert alert-danger">Failed to upload file.</div>';
                }
            }
        }
    }
    
    if (!isset($message) || empty($message)) {
        $stmt = $conn->prepare("INSERT INTO learning_materials (subject_id, title, description, material_type, file_path, content, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $subject_id, $title, $description, $material_type, $file_path, $content, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Material uploaded successfully!</div>';
            logActivity($_SESSION['user_id'], 'upload_material', "Uploaded material: $title");
        } else {
            $message = '<div class="alert alert-danger">Error uploading material.</div>';
        }
        $stmt->close();
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $material_id = intval($_POST['material_id']);
    
    $stmt = $conn->prepare("DELETE FROM learning_materials WHERE material_id = ? AND uploaded_by = ?");
    $stmt->bind_param("ii", $material_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Material deleted successfully!</div>';
        logActivity($_SESSION['user_id'], 'delete_material', "Deleted material ID: $material_id");
    } else {
        $message = '<div class="alert alert-danger">Error deleting material.</div>';
    }
    $stmt->close();
}

// Get teacher's subjects
$subjects = [];
$stmt = $conn->prepare("SELECT * FROM subjects WHERE teacher_id = ? AND status = 'active'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Get teacher's materials
$materials = [];
$stmt = $conn->prepare("SELECT lm.*, s.subject_name FROM learning_materials lm 
    JOIN subjects s ON lm.subject_id = s.subject_id 
    WHERE lm.uploaded_by = ? 
    ORDER BY lm.uploaded_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $materials[] = $row;
}
$stmt->close();

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
                        <small class="text-muted">Teacher Panel</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/teacher/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/teacher/materials.php">
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
                <div class="dashboard-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Learning Materials</h2>
                        <p class="text-muted">Upload and manage learning materials</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                        <i class="bi bi-plus-lg"></i> Upload Material
                    </button>
                </div>

                <?php echo $message; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Subject</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Uploaded</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials as $material): ?>
                                    <tr>
                                        <td><?php echo $material['material_id']; ?></td>
                                        <td><?php echo htmlspecialchars($material['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($material['title']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst($material['material_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($material['uploaded_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $material['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($material['status']); ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <?php if ($material['file_path']): ?>
                                            <a href="<?php echo htmlspecialchars($material['file_path']); ?>" class="btn btn-sm btn-success" target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this material?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="material_id" value="<?php echo $material['material_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($materials)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No materials uploaded yet</td>
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

    <!-- Add Material Modal -->
    <div class="modal fade" id="addMaterialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Learning Material</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select class="form-select" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Material Type</label>
                            <select class="form-select" name="material_type" id="material_type" required onchange="toggleContentField()">
                                <option value="">Select Type</option>
                                <option value="text">Text</option>
                                <option value="video">Video</option>
                                <option value="audio">Audio</option>
                                <option value="document">Document</option>
                            </select>
                        </div>
                        <div class="mb-3" id="text_content_field" style="display:none;">
                            <label class="form-label">Text Content</label>
                            <textarea class="form-control" name="content" rows="5"></textarea>
                        </div>
                        <div class="mb-3" id="file_upload_field" style="display:none;">
                            <label class="form-label">Upload File</label>
                            <input type="file" class="form-control" name="file" accept="video/*,audio/*,.pdf,.doc,.docx,.ppt,.pptx">
                            <small class="text-muted">Accepted: Videos, Audio, PDF, Word, PowerPoint</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload Material</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleContentField() {
            var type = document.getElementById('material_type').value;
            var textField = document.getElementById('text_content_field');
            var fileField = document.getElementById('file_upload_field');
            
            if (type === 'text') {
                textField.style.display = 'block';
                fileField.style.display = 'none';
            } else if (type) {
                textField.style.display = 'none';
                fileField.style.display = 'block';
            } else {
                textField.style.display = 'none';
                fileField.style.display = 'none';
            }
        }
    </script>
</body>
</html>
