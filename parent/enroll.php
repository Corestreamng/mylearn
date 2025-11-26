<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('parent');

$conn = getDBConnection();
$message = '';

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

// Get parent's email for Paystack
$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$parent_email = $stmt->get_result()->fetch_assoc()['email'];
$stmt->close();

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
                                                    <p class="text-primary"><strong>₦<?php echo number_format($subject['price_per_month'] ?? 0, 2); ?>/month</strong></p>
                                                    <button class="btn btn-primary btn-sm" onclick="enrollChild(<?php echo $subject['subject_id']; ?>, '<?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>', <?php echo $subject['price_per_month'] ?? 0; ?>)">
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
                                    <form id="enrollmentForm" onsubmit="return false;">
                                        <input type="hidden" id="enroll_subject_id">
                                        <input type="hidden" id="enroll_price_per_month">
                                        <div class="mb-3">
                                            <label class="form-label">Subject</label>
                                            <input type="text" class="form-control" id="enroll_subject_name" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Select Child</label>
                                            <select class="form-select" id="student_id" required>
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
                                            <select class="form-select" id="duration_months" onchange="calculateAmount()" required>
                                                <option value="1">1 Month</option>
                                                <option value="2">2 Months</option>
                                                <option value="3">3 Months</option>
                                                <option value="6">6 Months</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Total Amount</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₦</span>
                                                <input type="text" class="form-control" id="amount_display" value="0.00" readonly>
                                            </div>
                                            <input type="hidden" id="amount" value="0">
                                        </div>
                                        <div class="alert alert-info">
                                            <small><i class="bi bi-shield-check"></i> Secure payment powered by Paystack</small>
                                        </div>
                                        <button type="button" onclick="payWithPaystack()" class="btn btn-success w-100">
                                            <i class="bi bi-credit-card"></i> Pay ₦<span id="pay_amount">0.00</span> Now
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
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        let pricePerMonth = 0;
        
        function enrollChild(subjectId, subjectName, price) {
            pricePerMonth = price;
            document.getElementById('enroll_subject_id').value = subjectId;
            document.getElementById('enroll_subject_name').value = subjectName;
            document.getElementById('enroll_price_per_month').value = price;
            document.getElementById('enrollment-form').style.display = 'block';
            document.getElementById('enrollment-form').scrollIntoView({behavior: 'smooth'});
            calculateAmount();
        }

        function calculateAmount() {
            const duration = parseInt(document.getElementById('duration_months').value);
            const amount = pricePerMonth * duration;
            
            document.getElementById('amount').value = amount;
            document.getElementById('amount_display').value = amount.toFixed(2);
            document.getElementById('pay_amount').textContent = amount.toFixed(2);
        }

        function payWithPaystack() {
            const studentId = document.getElementById('student_id').value;
            const subjectId = document.getElementById('enroll_subject_id').value;
            const duration = document.getElementById('duration_months').value;
            const amount = parseFloat(document.getElementById('amount').value);
            
            if (!studentId) {
                alert('Please select a child');
                return;
            }
            
            if (amount <= 0) {
                alert('Invalid amount');
                return;
            }
            
            const handler = PaystackPop.setup({
                key: 'pk_test_6c7ec60c77a8c2db9e05b4a1e53be66ea4513ec2', // Replace with your public key
                email: '<?php echo $parent_email; ?>',
                amount: amount * 100, // Amount in kobo
                currency: 'NGN',
                ref: 'MLN_' + Math.floor((Math.random() * 1000000000) + 1),
                metadata: {
                    custom_fields: [
                        {
                            display_name: "Student ID",
                            variable_name: "student_id",
                            value: studentId
                        },
                        {
                            display_name: "Subject ID",
                            variable_name: "subject_id",
                            value: subjectId
                        },
                        {
                            display_name: "Duration (Months)",
                            variable_name: "duration_months",
                            value: duration
                        }
                    ]
                },
                callback: function(response) {
                    // Verify payment
                    fetch('/parent/verify_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'reference=' + response.reference
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('Payment successful! Child enrolled successfully.');
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('An error occurred. Please contact support with reference: ' + response.reference);
                    });
                },
                onClose: function() {
                    alert('Transaction was cancelled');
                }
            });
            handler.openIframe();
        }
    </script>
    <script src="/assets/js/mobile-nav.js"></script>
</body>
</html>
