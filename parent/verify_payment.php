<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('parent');

// Paystack configuration
define('PAYSTACK_SECRET_KEY', 'sk_test_7eda7129845266955b654651cba9ed65a0ca223f');

$conn = getDBConnection();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reference'])) {
    $reference = sanitizeInput($_POST['reference']);
    
    // Verify payment with Paystack
    $url = "https://api.paystack.co/transaction/verify/" . $reference;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY
    ]);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result_data = json_decode($result, true);
        
        if ($result_data['status'] && $result_data['data']['status'] === 'success') {
            // Payment verified successfully
            $amount = $result_data['data']['amount'] / 100; // Paystack returns amount in kobo
            $metadata = $result_data['data']['metadata'];
            
            $student_id = intval($metadata['student_id']);
            $subject_id = intval($metadata['subject_id']);
            $duration_months = intval($metadata['duration_months']);
            
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+$duration_months months"));
            
            // Insert subscription
            $stmt = $conn->prepare("INSERT INTO subscriptions (student_id, parent_id, subject_id, start_date, end_date, duration_months, amount, payment_status, payment_reference, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?, 'active')");
            $stmt->bind_param("iiissids", $student_id, $_SESSION['user_id'], $subject_id, $start_date, $end_date, $duration_months, $amount, $reference);
            
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'enroll_child', "Enrolled child in subject ID: $subject_id via Paystack");
                $response['success'] = true;
                $response['message'] = 'Payment verified and enrollment completed successfully!';
            } else {
                $response['message'] = 'Payment verified but enrollment failed. Please contact support.';
            }
            $stmt->close();
        } else {
            $response['message'] = 'Payment verification failed. Transaction was not successful.';
        }
    } else {
        $response['message'] = 'Unable to verify payment. Please try again or contact support.';
    }
} else {
    $response['message'] = 'Invalid request';
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>
