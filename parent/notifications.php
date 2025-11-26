<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('parent');

$conn = getDBConnection();
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_material = isset($_POST['new_material']) ? 1 : 0;
    $live_class = isset($_POST['live_class']) ? 1 : 0;
    $subscription_reminder = isset($_POST['subscription_reminder']) ? 1 : 0;
    $inactivity_reminder = isset($_POST['inactivity_reminder']) ? 1 : 0;
    $payment_confirmation = isset($_POST['payment_confirmation']) ? 1 : 0;
    $class_starting_soon = isset($_POST['class_starting_soon']) ? 1 : 0;
    $weekly_progress = isset($_POST['weekly_progress']) ? 1 : 0;
    
    // Check if preferences exist
    $stmt = $conn->prepare("SELECT pref_id FROM notification_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->fetch_assoc();
    $stmt->close();
    
    if ($exists) {
        // Update existing preferences
        $stmt = $conn->prepare("UPDATE notification_preferences SET 
            new_material = ?, live_class = ?, subscription_reminder = ?, 
            inactivity_reminder = ?, payment_confirmation = ?, class_starting_soon = ?, 
            weekly_progress = ? WHERE user_id = ?");
        $stmt->bind_param("iiiiiiii", $new_material, $live_class, $subscription_reminder, 
            $inactivity_reminder, $payment_confirmation, $class_starting_soon, 
            $weekly_progress, $_SESSION['user_id']);
    } else {
        // Insert new preferences
        $stmt = $conn->prepare("INSERT INTO notification_preferences 
            (user_id, new_material, live_class, subscription_reminder, inactivity_reminder, 
             payment_confirmation, class_starting_soon, weekly_progress) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiiiii", $_SESSION['user_id'], $new_material, $live_class, 
            $subscription_reminder, $inactivity_reminder, $payment_confirmation, 
            $class_starting_soon, $weekly_progress);
    }
    
    if ($stmt->execute()) {
        $message = 'Notification preferences saved successfully!';
        $message_type = 'success';
        logActivity($_SESSION['user_id'], 'Updated Notification Settings', 'Parent updated email notification preferences');
    } else {
        $message = 'Error saving preferences. Please try again.';
        $message_type = 'danger';
    }
    $stmt->close();
}

// Get current preferences
$prefs = [
    'new_material' => 1,
    'live_class' => 1,
    'subscription_reminder' => 1,
    'inactivity_reminder' => 1,
    'payment_confirmation' => 1,
    'class_starting_soon' => 1,
    'weekly_progress' => 1
];

$stmt = $conn->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $prefs = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Settings - MyLearn</title>
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
                            <a class="nav-link" href="/parent/progress.php">
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
                            <a class="nav-link active" href="/parent/notifications.php">
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
                    <h2><i class="bi bi-bell"></i> Notification Settings</h2>
                    <p class="text-muted">Manage your email notification preferences</p>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-envelope"></i> Email Notifications</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <p class="text-muted mb-4">
                                        Choose which email notifications you'd like to receive. These settings apply to you and your children's accounts.
                                    </p>
                                    
                                    <div class="mb-4">
                                        <h6 class="text-primary"><i class="bi bi-book"></i> Learning Updates</h6>
                                        <hr>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="new_material" name="new_material" <?php echo $prefs['new_material'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="new_material">
                                                <strong>New Learning Materials</strong>
                                                <br><small class="text-muted">Get notified when teachers upload new materials (videos, documents, audio)</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="live_class" name="live_class" <?php echo $prefs['live_class'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="live_class">
                                                <strong>Live Class Scheduled</strong>
                                                <br><small class="text-muted">Get notified when a new live class is scheduled</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="class_starting_soon" name="class_starting_soon" <?php echo $prefs['class_starting_soon'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="class_starting_soon">
                                                <strong>Class Starting Soon</strong>
                                                <br><small class="text-muted">Get a reminder 30 minutes before a live class starts</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="text-success"><i class="bi bi-graph-up"></i> Progress & Activity</h6>
                                        <hr>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="weekly_progress" name="weekly_progress" <?php echo $prefs['weekly_progress'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="weekly_progress">
                                                <strong>Weekly Progress Report</strong>
                                                <br><small class="text-muted">Receive a weekly summary of your children's learning progress</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="inactivity_reminder" name="inactivity_reminder" <?php echo $prefs['inactivity_reminder'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="inactivity_reminder">
                                                <strong>Inactivity Reminder</strong>
                                                <br><small class="text-muted">Get notified if your child hasn't logged in for 3+ days</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="text-warning"><i class="bi bi-credit-card"></i> Subscriptions & Payments</h6>
                                        <hr>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="subscription_reminder" name="subscription_reminder" <?php echo $prefs['subscription_reminder'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="subscription_reminder">
                                                <strong>Subscription Expiry Reminder</strong>
                                                <br><small class="text-muted">Get reminders 7 days, 3 days, and 1 day before subscription expires</small>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="payment_confirmation" name="payment_confirmation" <?php echo $prefs['payment_confirmation'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="payment_confirmation">
                                                <strong>Payment Confirmation</strong>
                                                <br><small class="text-muted">Receive confirmation email after successful payment</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg"></i> Save Preferences
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bi bi-info-circle"></i> About Notifications</h6>
                            </div>
                            <div class="card-body">
                                <p class="small">
                                    <i class="bi bi-envelope-fill text-primary"></i>
                                    Emails are sent to: <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong>
                                </p>
                                <hr>
                                <p class="small text-muted">
                                    <i class="bi bi-lightbulb"></i>
                                    <strong>Tip:</strong> Keep subscription reminders enabled to avoid any interruption in your child's learning.
                                </p>
                                <p class="small text-muted">
                                    <i class="bi bi-shield-check"></i>
                                    We respect your privacy. Your email is only used for the notifications you enable.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
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
