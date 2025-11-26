<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $email = sanitizeInput($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $full_name = sanitizeInput($_POST['full_name']);
            $user_type = sanitizeInput($_POST['user_type']);
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            
            // Validate user type
            if (!in_array($user_type, ['teacher', 'parent', 'student'])) {
                $message = '<div class="alert alert-danger">Invalid user type.</div>';
            } else {
                $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, user_type, parent_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $email, $password, $full_name, $user_type, $parent_id);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">User created successfully!</div>';
                    logActivity($_SESSION['user_id'], 'create_user', "Created $user_type: $full_name");
                } else {
                    $message = '<div class="alert alert-danger">Error creating user. Email may already exist.</div>';
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'edit') {
            $user_id = intval($_POST['user_id']);
            $full_name = sanitizeInput($_POST['full_name']);
            $status = sanitizeInput($_POST['status']);
            $user_type = sanitizeInput($_POST['user_type']);
            
            // Prevent editing admin user type
            $check = $conn->prepare("SELECT user_type FROM users WHERE user_id = ?");
            $check->bind_param("i", $user_id);
            $check->execute();
            $result = $check->get_result();
            $current_user = $result->fetch_assoc();
            $check->close();
            
            if ($current_user['user_type'] === 'admin' && $user_type !== 'admin') {
                $message = '<div class="alert alert-danger">Cannot change admin user type.</div>';
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, status = ?, user_type = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $full_name, $status, $user_type, $user_id);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">User updated successfully!</div>';
                    logActivity($_SESSION['user_id'], 'update_user', "Updated user ID: $user_id");
                } else {
                    $message = '<div class="alert alert-danger">Error updating user.</div>';
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'reset_password') {
            $user_id = intval($_POST['user_id']);
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_password, $user_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Password reset successfully!</div>';
                logActivity($_SESSION['user_id'], 'reset_password', "Reset password for user ID: $user_id");
            } else {
                $message = '<div class="alert alert-danger">Error resetting password.</div>';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'toggle_status') {
            $user_id = intval($_POST['user_id']);
            $new_status = sanitizeInput($_POST['new_status']);
            
            // Prevent deactivating current admin
            if ($user_id === $_SESSION['user_id']) {
                $message = '<div class="alert alert-danger">You cannot deactivate your own account.</div>';
            } else {
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
                $stmt->bind_param("si", $new_status, $user_id);
                
                if ($stmt->execute()) {
                    $action_text = $new_status === 'active' ? 'activated' : 'deactivated';
                    $message = '<div class="alert alert-success">User ' . $action_text . ' successfully!</div>';
                    logActivity($_SESSION['user_id'], 'toggle_user_status', "User ID $user_id $action_text");
                } else {
                    $message = '<div class="alert alert-danger">Error updating user status.</div>';
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $user_id = intval($_POST['user_id']);
            
            // Prevent deleting current admin
            if ($user_id === $_SESSION['user_id']) {
                $message = '<div class="alert alert-danger">You cannot delete your own account.</div>';
            } else {
                // Check if user is admin
                $check = $conn->prepare("SELECT user_type FROM users WHERE user_id = ?");
                $check->bind_param("i", $user_id);
                $check->execute();
                $result = $check->get_result();
                $user = $result->fetch_assoc();
                $check->close();
                
                if ($user['user_type'] === 'admin') {
                    $message = '<div class="alert alert-danger">Cannot delete admin users.</div>';
                } else {
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success">User deleted successfully!</div>';
                        logActivity($_SESSION['user_id'], 'delete_user', "Deleted user ID: $user_id");
                    } else {
                        $message = '<div class="alert alert-danger">Error deleting user. User may have related data.</div>';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Filter parameters
$filter_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$filter_status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query with filters
$where_clauses = [];
$params = [];
$types = '';

if ($filter_type && in_array($filter_type, ['admin', 'teacher', 'parent', 'student'])) {
    $where_clauses[] = "u.user_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if ($filter_status && in_array($filter_status, ['active', 'inactive'])) {
    $where_clauses[] = "u.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($search) {
    $where_clauses[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get all users with their related counts
$query = "SELECT u.*, 
    (SELECT COUNT(*) FROM subjects WHERE teacher_id = u.user_id) as subject_count,
    (SELECT COUNT(*) FROM users WHERE parent_id = u.user_id) as children_count,
    (SELECT COUNT(*) FROM subscriptions WHERE student_id = u.user_id AND status = 'active') as active_subscriptions,
    p.full_name as parent_name
    FROM users u 
    LEFT JOIN users p ON u.parent_id = p.user_id
    $where_sql
    ORDER BY u.created_at DESC";

$users = [];
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Get parents for dropdown (when creating students)
$parents = [];
$result = $conn->query("SELECT user_id, full_name, email FROM users WHERE user_type = 'parent' AND status = 'active' ORDER BY full_name");
while ($row = $result->fetch_assoc()) {
    $parents[] = $row;
}

// Get user counts by type
$counts = [];
$result = $conn->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
while ($row = $result->fetch_assoc()) {
    $counts[$row['user_type']] = $row['count'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Users - MyLearn</title>
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
                            <a class="nav-link active" href="/admin/users.php">
                                <i class="bi bi-people-fill"></i> All Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/subjects.php">
                                <i class="bi bi-book"></i> Subjects
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/materials.php">
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
                        <h2><i class="bi bi-people-fill me-2"></i>All Users Management</h2>
                        <p class="text-muted mb-0">Full CRUD operations for all user types</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-plus-lg"></i> Add New User
                    </button>
                </div>

                <?php echo $message; ?>

                <!-- User Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-person-badge fs-1 text-primary"></i>
                                <h3 class="mt-2"><?php echo $counts['teacher'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Teachers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-person-check fs-1 text-success"></i>
                                <h3 class="mt-2"><?php echo $counts['parent'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Parents</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-mortarboard fs-1 text-info"></i>
                                <h3 class="mt-2"><?php echo $counts['student'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-shield-check fs-1 text-danger"></i>
                                <h3 class="mt-2"><?php echo $counts['admin'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Admins</p>
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
                                <input type="text" class="form-control" name="search" placeholder="Name or email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">User Type</label>
                                <select class="form-select" name="type">
                                    <option value="">All Types</option>
                                    <option value="admin" <?php echo $filter_type === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="teacher" <?php echo $filter_type === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                    <option value="parent" <?php echo $filter_type === 'parent' ? 'selected' : ''; ?>>Parent</option>
                                    <option value="student" <?php echo $filter_type === 'student' ? 'selected' : ''; ?>>Student</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-table me-2"></i>Users List (<?php echo count($users); ?> found)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <th>Info</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr class="<?php echo $user['status'] === 'inactive' ? 'table-secondary' : ''; ?>">
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                            <?php if ($user['user_id'] === $_SESSION['user_id']): ?>
                                            <span class="badge bg-info">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php
                                            $type_colors = ['admin' => 'danger', 'teacher' => 'primary', 'parent' => 'success', 'student' => 'info'];
                                            $type_icons = ['admin' => 'shield-check', 'teacher' => 'person-badge', 'parent' => 'person-check', 'student' => 'mortarboard'];
                                            ?>
                                            <span class="badge bg-<?php echo $type_colors[$user['user_type']]; ?>">
                                                <i class="bi bi-<?php echo $type_icons[$user['user_type']]; ?>"></i>
                                                <?php echo ucfirst($user['user_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['user_type'] === 'teacher'): ?>
                                            <small><i class="bi bi-book"></i> <?php echo $user['subject_count']; ?> subjects</small>
                                            <?php elseif ($user['user_type'] === 'parent'): ?>
                                            <small><i class="bi bi-people"></i> <?php echo $user['children_count']; ?> children</small>
                                            <?php elseif ($user['user_type'] === 'student'): ?>
                                            <small>
                                                <i class="bi bi-credit-card"></i> <?php echo $user['active_subscriptions']; ?> subs
                                                <?php if ($user['parent_name']): ?>
                                                <br><i class="bi bi-person"></i> <?php echo htmlspecialchars($user['parent_name']); ?>
                                                <?php endif; ?>
                                            </small>
                                            <?php else: ?>
                                            <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td class="table-actions">
                                            <!-- Edit Button -->
                                            <button class="btn btn-sm btn-info" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <!-- Reset Password Button -->
                                            <button class="btn btn-sm btn-warning" onclick="resetPassword(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')" title="Reset Password">
                                                <i class="bi bi-key"></i>
                                            </button>
                                            
                                            <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                            <!-- Toggle Status Button -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $user['status'] === 'active' ? 'secondary' : 'success'; ?>" 
                                                        title="<?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>"
                                                        onclick="return confirm('Are you sure you want to <?php echo $user['status'] === 'active' ? 'deactivate' : 'activate'; ?> this user?');">
                                                    <i class="bi bi-<?php echo $user['status'] === 'active' ? 'x-circle' : 'check-circle'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <?php if ($user['user_type'] !== 'admin'): ?>
                                            <!-- Delete Button -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete" 
                                                        onclick="return confirm('Are you sure you want to permanently delete this user? This action cannot be undone.');">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="bi bi-search fs-1 text-muted"></i>
                                            <p class="text-muted mb-0">No users found</p>
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select class="form-select" name="user_type" id="add_user_type" required onchange="toggleParentField()">
                                <option value="">Select Type</option>
                                <option value="teacher">Teacher</option>
                                <option value="parent">Parent</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        <div class="mb-3" id="parent_select_field" style="display:none;">
                            <label class="form-label">Parent (for students)</label>
                            <select class="form-select" name="parent_id" id="add_parent_id">
                                <option value="">Select Parent</option>
                                <?php foreach ($parents as $parent): ?>
                                <option value="<?php echo $parent['user_id']; ?>">
                                    <?php echo htmlspecialchars($parent['full_name']); ?> (<?php echo htmlspecialchars($parent['email']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select class="form-select" name="user_type" id="edit_user_type">
                                <option value="teacher">Teacher</option>
                                <option value="parent">Parent</option>
                                <option value="student">Student</option>
                                <option value="admin">Admin</option>
                            </select>
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
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="reset_user_id">
                        <p>Reset password for: <strong id="reset_user_name"></strong></p>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning"><i class="bi bi-key"></i> Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleParentField() {
            var type = document.getElementById('add_user_type').value;
            var parentField = document.getElementById('parent_select_field');
            parentField.style.display = type === 'student' ? 'block' : 'none';
        }
        
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_user_type').value = user.user_type;
            document.getElementById('edit_status').value = user.status;
            
            // Disable user type change for admins
            if (user.user_type === 'admin') {
                document.getElementById('edit_user_type').disabled = true;
            } else {
                document.getElementById('edit_user_type').disabled = false;
            }
            
            var modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }
        
        function resetPassword(userId, userName) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_user_name').textContent = userName;
            
            var modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            modal.show();
        }
    </script>
    <script src="/assets/js/mobile-nav.js"></script>
</body>
</html>
