<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    // Redirect based on user type
    switch ($_SESSION['user_type']) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: /teacher/dashboard.php');
            break;
        case 'parent':
            header('Location: /parent/dashboard.php');
            break;
        case 'student':
            header('Location: /student/dashboard.php');
            break;
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT user_id, email, password, full_name, user_type, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($user['status'] !== 'active') {
            $error = 'Your account has been deactivated.';
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];
            
            logActivity($user['user_id'], 'login', 'User logged in');
            
            // Redirect based on user type
            switch ($user['user_type']) {
                case 'admin':
                    header('Location: /admin/dashboard.php');
                    break;
                case 'teacher':
                    header('Location: /teacher/dashboard.php');
                    break;
                case 'parent':
                    header('Location: /parent/dashboard.php');
                    break;
                case 'student':
                    header('Location: /student/dashboard.php');
                    break;
            }
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Invalid email or password.';
    }
    
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyLearn - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">MyLearn LMS</h2>
                        <h5 class="text-center text-muted mb-4">Login to your account</h5>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="/register.php">Don't have an account? Register as Parent</a>
                        </div>
                        
                        <div class="mt-4 text-center text-muted">
                            <small>Default Admin Login: admin@mylearn.com / admin123</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
