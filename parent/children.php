<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('parent');

$conn = getDBConnection();
$message = '';

// Handle adding a child
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message = '<div class="alert alert-danger">Email already exists.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, user_type, parent_id) VALUES (?, ?, ?, 'student', ?)");
        $stmt->bind_param("sssi", $email, $password, $full_name, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Child added successfully!</div>';
            logActivity($_SESSION['user_id'], 'add_child', "Added child: $full_name");
        } else {
            $message = '<div class="alert alert-danger">Error adding child.</div>';
        }
    }
    $stmt->close();
}

// Get parent's children
$children = [];
$stmt = $conn->prepare("SELECT u.*, 
    (SELECT COUNT(*) FROM subscriptions WHERE student_id = u.user_id AND status = 'active') as active_subscriptions
    FROM users u WHERE parent_id = ? AND user_type = 'student'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Children - MyLearn</title>
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
                            <a class="nav-link active" href="/parent/children.php">
                                <i class="bi bi-people"></i> My Children
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
                        <h2>My Children</h2>
                        <p class="text-muted">Manage your children's accounts</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addChildModal">
                        <i class="bi bi-plus-lg"></i> Add Child
                    </button>
                </div>

                <?php echo $message; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Active Subscriptions</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($children as $child): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($child['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($child['email']); ?></td>
                                        <td><?php echo $child['active_subscriptions']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $child['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($child['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($child['created_at'])); ?></td>
                                        <td>
                                            <a href="/parent/enroll.php?student_id=<?php echo $child['user_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-plus-circle"></i> Enroll
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($children)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No children registered yet</td>
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

    <!-- Add Child Modal -->
    <div class="modal fade" id="addChildModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Child</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Child's Full Name</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email (for login)</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                            <small class="text-muted">This will be used for the child's login</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Child</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
