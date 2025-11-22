<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('parent');

$conn = getDBConnection();
$message = '';

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);
    $duration_months = intval($_POST['duration_months']);
    $amount = floatval($_POST['amount']);
    
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+$duration_months months"));
    
    $stmt = $conn->prepare("INSERT INTO subscriptions (student_id, parent_id, subject_id, start_date, end_date, duration_months, amount, payment_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', 'active')");
    $stmt->bind_param("iiissid", $student_id, $_SESSION['user_id'], $subject_id, $start_date, $end_date, $duration_months, $amount);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Child enrolled successfully!</div>';
        logActivity($_SESSION['user_id'], 'enroll_child', "Enrolled child in subject ID: $subject_id");
    } else {
        $message = '<div class="alert alert-danger">Error enrolling child.</div>';
    }
    $stmt->close();
}

// Get parent's children
$children = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE parent_id = ? AND user_type = 'student' AND status = 'active'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}
$stmt->close();

// Get available subjects
$subjects = [];
$result = $conn->query("SELECT s.*, u.full_name as teacher_name FROM subjects s 
    LEFT JOIN users u ON s.teacher_id = u.user_id 
    WHERE s.status = 'active'");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// Pre-select student if provided
$selected_student = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Child - MyLearn</title>
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
                            <a class="nav-link active" href="/parent/enroll.php">
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
                <div class="dashboard-header">
                    <h2>Enroll Child</h2>
                    <p class="text-muted">Subscribe your child to subjects</p>
                </div>

                <?php echo $message; ?>

                <?php if (empty($children)): ?>
                    <div class="alert alert-warning">
                        You need to <a href="/parent/children.php">add a child</a> before enrolling them in subjects.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <!-- Available Subjects -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Available Subjects</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($subjects as $subject): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card material-card">
                                                <div class="card-body">
                                                    <h5><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                                    <p class="text-muted"><?php echo htmlspecialchars(substr($subject['description'], 0, 100)); ?>...</p>
                                                    <?php if ($subject['teacher_name']): ?>
                                                    <p><small><strong>Teacher:</strong> <?php echo htmlspecialchars($subject['teacher_name']); ?></small></p>
                                                    <?php endif; ?>
                                                    <button class="btn btn-primary btn-sm" onclick="enrollChild(<?php echo $subject['subject_id']; ?>, '<?php echo htmlspecialchars($subject['subject_name']); ?>')">
                                                        <i class="bi bi-plus-circle"></i> Enroll Child
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($subjects)): ?>
                                        <div class="col-12">
                                            <p class="text-muted">No subjects available at the moment.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enrollment Form -->
                        <div class="col-md-4">
                            <div class="card" id="enrollment-form" style="display:none;">
                                <div class="card-header">
                                    <h5>Enrollment Details</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="subject_id" id="enroll_subject_id">
                                        <div class="mb-3">
                                            <label class="form-label">Subject</label>
                                            <input type="text" class="form-control" id="enroll_subject_name" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Select Child</label>
                                            <select class="form-select" name="student_id" required>
                                                <option value="">Choose...</option>
                                                <?php foreach ($children as $child): ?>
                                                <option value="<?php echo $child['user_id']; ?>" <?php echo $selected_student === $child['user_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($child['full_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Duration</label>
                                            <select class="form-select" name="duration_months" id="duration_months" onchange="calculateAmount()" required>
                                                <option value="1">1 Month - $50</option>
                                                <option value="2">2 Months - $90</option>
                                                <option value="3">3 Months - $120</option>
                                                <option value="6">6 Months - $220</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Amount</label>
                                            <input type="text" class="form-control" id="amount_display" value="$50.00" readonly>
                                            <input type="hidden" name="amount" id="amount" value="50">
                                        </div>
                                        <div class="alert alert-info">
                                            <small><i class="bi bi-info-circle"></i> Payment will be processed and subscription activated immediately.</small>
                                        </div>
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-credit-card"></i> Pay & Enroll
                                        </button>
                                    </form>
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
        function enrollChild(subjectId, subjectName) {
            document.getElementById('enroll_subject_id').value = subjectId;
            document.getElementById('enroll_subject_name').value = subjectName;
            document.getElementById('enrollment-form').style.display = 'block';
            document.getElementById('enrollment-form').scrollIntoView({behavior: 'smooth'});
        }

        function calculateAmount() {
            const duration = parseInt(document.getElementById('duration_months').value);
            let amount = 0;
            
            switch(duration) {
                case 1: amount = 50; break;
                case 2: amount = 90; break;
                case 3: amount = 120; break;
                case 6: amount = 220; break;
            }
            
            document.getElementById('amount').value = amount;
            document.getElementById('amount_display').value = '$' + amount.toFixed(2);
        }
    </script>
</body>
</html>
