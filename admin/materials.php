<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

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
    
    // Only insert if no error occurred during file upload
    if (empty($message)) {
        $stmt = $conn->prepare("INSERT INTO learning_materials (subject_id, title, description, material_type, file_path, content, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $subject_id, $title, $description, $material_type, $file_path, $content, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Material uploaded successfully!</div>';
            logActivity($_SESSION['user_id'], 'admin_upload_material', "Admin uploaded material: $title");
        } else {
            $message = '<div class="alert alert-danger">Error uploading material.</div>';
        }
        $stmt->close();
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $material_id = intval($_POST['material_id']);
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $status = sanitizeInput($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE learning_materials SET title = ?, description = ?, status = ? WHERE material_id = ?");
    $stmt->bind_param("sssi", $title, $description, $status, $material_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Material updated successfully!</div>';
        logActivity($_SESSION['user_id'], 'admin_update_material', "Admin updated material ID: $material_id");
    } else {
        $message = '<div class="alert alert-danger">Error updating material.</div>';
    }
    $stmt->close();
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $material_id = intval($_POST['material_id']);
    
    // Get file path to delete file
    $stmt = $conn->prepare("SELECT file_path FROM learning_materials WHERE material_id = ?");
    $stmt->bind_param("i", $material_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $material = $result->fetch_assoc();
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM learning_materials WHERE material_id = ?");
    $stmt->bind_param("i", $material_id);
    
    if ($stmt->execute()) {
        // Delete the actual file if it exists
        if ($material && $material['file_path']) {
            $file_to_delete = __DIR__ . '/..' . $material['file_path'];
            if (file_exists($file_to_delete)) {
                unlink($file_to_delete);
            }
        }
        $message = '<div class="alert alert-success">Material deleted successfully!</div>';
        logActivity($_SESSION['user_id'], 'admin_delete_material', "Admin deleted material ID: $material_id");
    } else {
        $message = '<div class="alert alert-danger">Error deleting material.</div>';
    }
    $stmt->close();
}

// Handle toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $material_id = intval($_POST['material_id']);
    $new_status = sanitizeInput($_POST['new_status']);
    
    $stmt = $conn->prepare("UPDATE learning_materials SET status = ? WHERE material_id = ?");
    $stmt->bind_param("si", $new_status, $material_id);
    
    if ($stmt->execute()) {
        $action_text = $new_status === 'active' ? 'activated' : 'deactivated';
        $message = '<div class="alert alert-success">Material ' . $action_text . ' successfully!</div>';
        logActivity($_SESSION['user_id'], 'admin_toggle_material', "Admin $action_text material ID: $material_id");
    } else {
        $message = '<div class="alert alert-danger">Error updating material status.</div>';
    }
    $stmt->close();
}

// Filter parameters
$filter_subject = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$filter_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$filter_status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query with filters
$where_clauses = [];
$params = [];
$types = '';

if ($filter_subject > 0) {
    $where_clauses[] = "lm.subject_id = ?";
    $params[] = $filter_subject;
    $types .= 'i';
}

if ($filter_type && in_array($filter_type, ['text', 'video', 'audio', 'document'])) {
    $where_clauses[] = "lm.material_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if ($filter_status && in_array($filter_status, ['active', 'inactive'])) {
    $where_clauses[] = "lm.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($search) {
    $where_clauses[] = "(lm.title LIKE ? OR lm.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get all materials
$query = "SELECT lm.*, s.subject_name, u.full_name as uploader_name 
    FROM learning_materials lm 
    JOIN subjects s ON lm.subject_id = s.subject_id 
    JOIN users u ON lm.uploaded_by = u.user_id
    $where_sql
    ORDER BY lm.uploaded_at DESC";

$materials = [];
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

while ($row = $result->fetch_assoc()) {
    $materials[] = $row;
}

// Get all subjects for dropdown
$subjects = [];
$result = $conn->query("SELECT subject_id, subject_name FROM subjects WHERE status = 'active' ORDER BY subject_name");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// Get material statistics
$stats = [];
$result = $conn->query("SELECT material_type, COUNT(*) as count FROM learning_materials GROUP BY material_type");
while ($row = $result->fetch_assoc()) {
    $stats[$row['material_type']] = $row['count'];
}
$total_materials = array_sum($stats);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Materials - MyLearn</title>
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
                        <small class="text-muted">Admin Panel</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/users.php">
                                <i class="bi bi-people-fill"></i> All Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/subjects.php">
                                <i class="bi bi-book"></i> Subjects
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/admin/materials.php">
                                <i class="bi bi-file-earmark-text"></i> Materials
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/live_classes.php">
                                <i class="bi bi-camera-video"></i> Live Classes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/teachers.php">
                                <i class="bi bi-person-badge"></i> Teachers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/students.php">
                                <i class="bi bi-mortarboard"></i> Students
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
                <div class="dashboard-header d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2><i class="bi bi-file-earmark-text me-2"></i>Learning Materials</h2>
                        <p class="text-muted mb-0">Upload and manage all learning materials</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                        <i class="bi bi-plus-lg"></i> Upload Material
                    </button>
                </div>

                <?php echo $message; ?>

                <!-- Material Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100 border-left-primary">
                            <div class="card-body text-center">
                                <i class="bi bi-collection fs-1 text-primary"></i>
                                <h3 class="mt-2"><?php echo $total_materials; ?></h3>
                                <p class="text-muted mb-0">Total Materials</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-play-circle fs-1 text-danger"></i>
                                <h3 class="mt-2"><?php echo $stats['video'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Videos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-music-note-beamed fs-1 text-success"></i>
                                <h3 class="mt-2"><?php echo $stats['audio'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Audio</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-file-pdf fs-1 text-warning"></i>
                                <h3 class="mt-2"><?php echo $stats['document'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Documents</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" placeholder="Title or description..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Subject</label>
                                <select class="form-select" name="subject">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>" <?php echo $filter_subject == $subject['subject_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type">
                                    <option value="">All Types</option>
                                    <option value="text" <?php echo $filter_type === 'text' ? 'selected' : ''; ?>>Text</option>
                                    <option value="video" <?php echo $filter_type === 'video' ? 'selected' : ''; ?>>Video</option>
                                    <option value="audio" <?php echo $filter_type === 'audio' ? 'selected' : ''; ?>>Audio</option>
                                    <option value="document" <?php echo $filter_type === 'document' ? 'selected' : ''; ?>>Document</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Materials Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-table me-2"></i>Materials List (<?php echo count($materials); ?> found)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Subject</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Uploaded By</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials as $material): ?>
                                    <tr class="<?php echo $material['status'] === 'inactive' ? 'table-secondary' : ''; ?>">
                                        <td><?php echo $material['material_id']; ?></td>
                                        <td><?php echo htmlspecialchars($material['subject_name']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($material['title']); ?></strong>
                                            <?php if ($material['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($material['description'], 0, 50)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $type_colors = ['text' => 'secondary', 'video' => 'danger', 'audio' => 'success', 'document' => 'warning'];
                                            $type_icons = ['text' => 'file-text', 'video' => 'play-circle', 'audio' => 'music-note-beamed', 'document' => 'file-pdf'];
                                            ?>
                                            <span class="badge bg-<?php echo $type_colors[$material['material_type']]; ?>">
                                                <i class="bi bi-<?php echo $type_icons[$material['material_type']]; ?>"></i>
                                                <?php echo ucfirst($material['material_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($material['uploader_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($material['uploaded_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $material['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($material['status']); ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <?php if ($material['file_path']): ?>
                                            <a href="<?php echo htmlspecialchars($material['file_path']); ?>" class="btn btn-sm btn-success" target="_blank" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-sm btn-info" onclick="editMaterial(<?php echo htmlspecialchars(json_encode($material)); ?>)" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="material_id" value="<?php echo $material['material_id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $material['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $material['status'] === 'active' ? 'secondary' : 'success'; ?>" 
                                                        title="<?php echo $material['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="bi bi-<?php echo $material['status'] === 'active' ? 'x-circle' : 'check-circle'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this material?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="material_id" value="<?php echo $material['material_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($materials)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="bi bi-inbox fs-1 text-muted"></i>
                                            <p class="text-muted mb-0">No materials found</p>
                                        </td>
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
                        <h5 class="modal-title"><i class="bi bi-cloud-upload me-2"></i>Upload Learning Material</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Material Type</label>
                                <select class="form-select" name="material_type" id="material_type" required onchange="toggleContentField()">
                                    <option value="">Select Type</option>
                                    <option value="text">Text</option>
                                    <option value="video">Video</option>
                                    <option value="audio">Audio</option>
                                    <option value="document">Document</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3" id="text_content_field" style="display:none;">
                            <label class="form-label">Text Content</label>
                            <textarea class="form-control" name="content" rows="5"></textarea>
                        </div>
                        <div class="mb-3" id="file_upload_field" style="display:none;">
                            <label class="form-label">Upload File</label>
                            <input type="file" class="form-control" name="file" accept="video/*,audio/*,.pdf,.doc,.docx,.ppt,.pptx">
                            <small class="text-muted">Max 50MB. Accepted: Videos (mp4, avi, mov, webm), Audio (mp3, wav, ogg), Documents (pdf, doc, docx, ppt, pptx)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Upload Material</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Material Modal -->
    <div class="modal fade" id="editMaterialModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Material</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="material_id" id="edit_material_id">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Material</button>
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
        
        function editMaterial(material) {
            document.getElementById('edit_material_id').value = material.material_id;
            document.getElementById('edit_title').value = material.title;
            document.getElementById('edit_description').value = material.description;
            document.getElementById('edit_status').value = material.status;
            
            var modal = new bootstrap.Modal(document.getElementById('editMaterialModal'));
            modal.show();
        }
    </script>
    <script src="/assets/js/mobile-nav.js"></script>
</body>
</html>
