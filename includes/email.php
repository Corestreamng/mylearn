<?php
/**
 * Email Helper Functions for MyLearn LMS
 * Uses PHP's built-in mail() function - compatible with cPanel
 */

// Email configuration
define('MAIL_FROM_NAME', 'MyLearn LMS');
define('MAIL_FROM_EMAIL', 'noreply@mylearn.com'); // Update with your domain

/**
 * Send an email using PHP's mail function
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body HTML email body
 * @param string $from_name Sender name
 * @param string $from_email Sender email
 * @return bool
 */
function sendEmail($to, $subject, $body, $from_name = MAIL_FROM_NAME, $from_email = MAIL_FROM_EMAIL) {
    // Headers for HTML email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Wrap body in HTML template
    $html_body = getEmailTemplate($subject, $body);
    
    return mail($to, $subject, $html_body, $headers);
}

/**
 * Get the standard email template
 */
function getEmailTemplate($subject, $content) {
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($subject) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 28px;">MyLearn LMS</h1>
            </td>
        </tr>
        <!-- Content -->
        <tr>
            <td style="padding: 30px;">
                ' . $content . '
            </td>
        </tr>
        <!-- Footer -->
        <tr>
            <td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;">
                <p style="margin: 0; color: #6c757d; font-size: 14px;">
                    &copy; ' . date('Y') . ' MyLearn LMS. All rights reserved.
                </p>
                <p style="margin: 10px 0 0 0; color: #6c757d; font-size: 12px;">
                    This is an automated message. Please do not reply directly to this email.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Send welcome email to new user
 */
function sendWelcomeEmail($email, $name, $user_type) {
    $subject = "Welcome to MyLearn LMS!";
    
    $body = '
    <h2 style="color: #333; margin-bottom: 20px;">Welcome, ' . htmlspecialchars($name) . '!</h2>
    <p style="color: #555; line-height: 1.6;">
        Thank you for joining MyLearn LMS. Your account has been successfully created as a <strong>' . ucfirst($user_type) . '</strong>.
    </p>
    <p style="color: #555; line-height: 1.6;">
        You can now log in to your dashboard and start exploring our learning platform.
    </p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="' . getSiteURL() . '/login.php" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: bold;">
            Login Now
        </a>
    </div>
    <p style="color: #555; line-height: 1.6;">
        If you have any questions, please don\'t hesitate to contact us.
    </p>';
    
    return sendEmail($email, $subject, $body);
}

/**
 * Send subscription due reminder
 */
function sendSubscriptionReminder($email, $name, $subject_name, $days_left, $end_date) {
    $subject = "Subscription Reminder: {$subject_name} expires in {$days_left} day(s)";
    
    $urgency_color = $days_left <= 1 ? '#dc3545' : ($days_left <= 3 ? '#ffc107' : '#28a745');
    
    $body = '
    <h2 style="color: #333; margin-bottom: 20px;">Subscription Expiring Soon</h2>
    <p style="color: #555; line-height: 1.6;">
        Dear ' . htmlspecialchars($name) . ',
    </p>
    <p style="color: #555; line-height: 1.6;">
        This is a reminder that your child\'s subscription to <strong>' . htmlspecialchars($subject_name) . '</strong> will expire in:
    </p>
    <div style="text-align: center; margin: 25px 0;">
        <span style="display: inline-block; padding: 15px 30px; background-color: ' . $urgency_color . '; color: #ffffff; font-size: 24px; font-weight: bold; border-radius: 10px;">
            ' . $days_left . ' Day(s)
        </span>
    </div>
    <p style="color: #555; line-height: 1.6;">
        <strong>Expiry Date:</strong> ' . date('F j, Y', strtotime($end_date)) . '
    </p>
    <p style="color: #555; line-height: 1.6;">
        To ensure uninterrupted access to learning materials, please renew the subscription before it expires.
    </p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="' . getSiteURL() . '/parent/enroll.php" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: bold;">
            Renew Now
        </a>
    </div>';
    
    return sendEmail($email, $subject, $body);
}

/**
 * Send live class notification
 */
function sendLiveClassNotification($email, $name, $class_title, $subject_name, $scheduled_at, $class_url = null, $is_starting = false) {
    $subject = $is_starting 
        ? "Live Class Starting Now: {$class_title}"
        : "New Live Class Scheduled: {$class_title}";
    
    $body = '
    <h2 style="color: #333; margin-bottom: 20px;">' . ($is_starting ? '🔴 Live Class Starting Now!' : '📅 New Live Class Scheduled') . '</h2>
    <p style="color: #555; line-height: 1.6;">
        Dear ' . htmlspecialchars($name) . ',
    </p>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
        <p style="margin: 5px 0; color: #555;"><strong>Class:</strong> ' . htmlspecialchars($class_title) . '</p>
        <p style="margin: 5px 0; color: #555;"><strong>Subject:</strong> ' . htmlspecialchars($subject_name) . '</p>
        <p style="margin: 5px 0; color: #555;"><strong>Scheduled At:</strong> ' . date('F j, Y \a\t g:i A', strtotime($scheduled_at)) . '</p>
    </div>';
    
    if ($is_starting && $class_url) {
        $body .= '
    <div style="text-align: center; margin: 30px 0;">
        <a href="' . htmlspecialchars($class_url) . '" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: bold;">
            Join Class Now
        </a>
    </div>';
    }
    
    $body .= '
    <p style="color: #555; line-height: 1.6;">
        Please ensure you are ready before the class starts.
    </p>';
    
    return sendEmail($email, $subject, $body);
}

/**
 * Send material upload notification
 */
function sendMaterialNotification($email, $name, $material_title, $subject_name, $material_type) {
    $subject = "New Learning Material: {$material_title}";
    
    $type_icons = [
        'video' => '🎬',
        'audio' => '🎵',
        'document' => '📄',
        'text' => '📝'
    ];
    
    $icon = $type_icons[$material_type] ?? '📚';
    
    $body = '
    <h2 style="color: #333; margin-bottom: 20px;">' . $icon . ' New Learning Material Available</h2>
    <p style="color: #555; line-height: 1.6;">
        Dear ' . htmlspecialchars($name) . ',
    </p>
    <p style="color: #555; line-height: 1.6;">
        A new learning material has been uploaded for your enrolled subject.
    </p>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
        <p style="margin: 5px 0; color: #555;"><strong>Title:</strong> ' . htmlspecialchars($material_title) . '</p>
        <p style="margin: 5px 0; color: #555;"><strong>Subject:</strong> ' . htmlspecialchars($subject_name) . '</p>
        <p style="margin: 5px 0; color: #555;"><strong>Type:</strong> ' . ucfirst($material_type) . '</p>
    </div>
    <div style="text-align: center; margin: 30px 0;">
        <a href="' . getSiteURL() . '/student/materials.php" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: bold;">
            View Material
        </a>
    </div>';
    
    return sendEmail($email, $subject, $body);
}

/**
 * Send inactivity reminder
 */
function sendInactivityReminder($email, $name, $days_inactive) {
    $subject = "We Miss You! It's been {$days_inactive} days";
    
    $body = '
    <h2 style="color: #333; margin-bottom: 20px;">👋 We Miss You!</h2>
    <p style="color: #555; line-height: 1.6;">
        Dear ' . htmlspecialchars($name) . ',
    </p>
    <p style="color: #555; line-height: 1.6;">
        We noticed you haven\'t logged in for <strong>' . $days_inactive . ' days</strong>. Your learning journey is important to us!
    </p>
    <p style="color: #555; line-height: 1.6;">
        Don\'t miss out on:
    </p>
    <ul style="color: #555; line-height: 1.8;">
        <li>New learning materials from your teachers</li>
        <li>Upcoming live classes</li>
        <li>Your progress tracking updates</li>
    </ul>
    <div style="text-align: center; margin: 30px 0;">
        <a href="' . getSiteURL() . '/login.php" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: bold;">
            Continue Learning
        </a>
    </div>';
    
    return sendEmail($email, $subject, $body);
}

/**
 * Send payment confirmation
 */
function sendPaymentConfirmation($email, $name, $subject_name, $amount, $duration, $reference) {
    $subject = "Payment Confirmed: {$subject_name}";
    
    $body = '
    <h2 style="color: #333; margin-bottom: 20px;">✅ Payment Successful!</h2>
    <p style="color: #555; line-height: 1.6;">
        Dear ' . htmlspecialchars($name) . ',
    </p>
    <p style="color: #555; line-height: 1.6;">
        Your payment has been successfully processed. Here are the details:
    </p>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
        <p style="margin: 5px 0; color: #555;"><strong>Subject:</strong> ' . htmlspecialchars($subject_name) . '</p>
        <p style="margin: 5px 0; color: #555;"><strong>Duration:</strong> ' . $duration . ' month(s)</p>
        <p style="margin: 5px 0; color: #555;"><strong>Amount:</strong> ₦' . number_format($amount, 2) . '</p>
        <p style="margin: 5px 0; color: #555;"><strong>Reference:</strong> ' . htmlspecialchars($reference) . '</p>
    </div>
    <p style="color: #555; line-height: 1.6;">
        Your child can now access all learning materials for this subject.
    </p>';
    
    return sendEmail($email, $subject, $body);
}

/**
 * Get site URL
 */
function getSiteURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

/**
 * Check if user has notification preference enabled
 */
function shouldSendNotification($user_id, $notification_type) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prefs = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if (!$prefs) {
        // Default to enabled if no preferences set
        return true;
    }
    
    switch ($notification_type) {
        case 'new_material':
            return $prefs['new_material'] == 1;
        case 'live_class':
            return $prefs['live_class'] == 1;
        case 'subscription_reminder':
            return $prefs['subscription_reminder'] == 1;
        case 'inactivity':
            return $prefs['inactivity_reminder'] == 1;
        case 'payment':
            return $prefs['payment_confirmation'] == 1;
        default:
            return true;
    }
}

/**
 * Queue a notification for sending (for cron jobs)
 */
function queueNotification($user_id, $notification_type, $data) {
    $conn = getDBConnection();
    
    $json_data = json_encode($data);
    $stmt = $conn->prepare("INSERT INTO notification_queue (user_id, notification_type, data, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $notification_type, $json_data);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}
?>
