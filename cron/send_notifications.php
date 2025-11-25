#!/usr/bin/php
<?php
/**
 * Email Notification Cron Job
 * Run this script periodically (e.g., every hour) via cPanel's Cron Jobs
 * 
 * Example cron entry:
 * 0 * * * * /usr/bin/php /home/youruser/mylearn/cron/send_notifications.php
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/email.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting notification cron job...\n";

try {
    $conn = getDBConnection();
    
    // 1. Send subscription expiry reminders (7 days, 3 days, 1 day)
    sendSubscriptionReminders($conn);
    
    // 2. Send inactivity reminders (students who haven't logged in for 3+ days)
    sendInactivityReminders($conn);
    
    // 3. Process notification queue
    processNotificationQueue($conn);
    
    // 4. Check for live classes starting soon (30 minutes)
    sendUpcomingClassReminders($conn);
    
    $conn->close();
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Notification cron job completed.\n";

/**
 * Send subscription expiry reminders
 */
function sendSubscriptionReminders($conn) {
    echo "  Checking subscription expiry reminders...\n";
    
    $reminder_days = [7, 3, 1];
    
    foreach ($reminder_days as $days) {
        $target_date = date('Y-m-d', strtotime("+{$days} days"));
        
        $query = "SELECT 
                    sub.subscription_id,
                    sub.end_date,
                    p.user_id as parent_id,
                    p.email as parent_email,
                    p.full_name as parent_name,
                    s.full_name as student_name,
                    subj.subject_name
                FROM subscriptions sub
                JOIN users p ON sub.parent_id = p.user_id
                JOIN users s ON sub.student_id = s.user_id
                JOIN subjects subj ON sub.subject_id = subj.subject_id
                WHERE sub.status = 'active'
                AND DATE(sub.end_date) = ?
                AND sub.subscription_id NOT IN (
                    SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.subscription_id')) AS UNSIGNED)
                    FROM notification_queue 
                    WHERE notification_type = 'subscription_reminder_{$days}day'
                    AND status = 'sent'
                )";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $target_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Check if parent has this notification enabled
            if (shouldSendNotificationByUserId($conn, $row['parent_id'], 'subscription_reminder')) {
                $sent = sendSubscriptionReminder(
                    $row['parent_email'],
                    $row['parent_name'],
                    $row['subject_name'],
                    $days,
                    $row['end_date']
                );
                
                if ($sent) {
                    echo "    Sent {$days}-day reminder to {$row['parent_email']} for {$row['subject_name']}\n";
                    logNotificationSent($conn, $row['parent_id'], "subscription_reminder_{$days}day", [
                        'subscription_id' => $row['subscription_id']
                    ]);
                }
            }
        }
        $stmt->close();
    }
}

/**
 * Send inactivity reminders
 */
function sendInactivityReminders($conn) {
    echo "  Checking inactivity reminders...\n";
    
    // Get students who haven't logged in for 3+ days
    $query = "SELECT 
                s.user_id,
                s.email,
                s.full_name,
                s.last_login,
                p.user_id as parent_id,
                p.email as parent_email,
                p.full_name as parent_name,
                DATEDIFF(NOW(), COALESCE(s.last_login, s.created_at)) as days_inactive
            FROM users s
            LEFT JOIN users p ON s.parent_id = p.user_id
            WHERE s.user_type = 'student'
            AND s.status = 'active'
            AND DATEDIFF(NOW(), COALESCE(s.last_login, s.created_at)) >= 3
            AND s.user_id NOT IN (
                SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.student_id')) AS UNSIGNED)
                FROM notification_queue 
                WHERE notification_type = 'inactivity_reminder'
                AND status = 'sent'
                AND DATE(created_at) = CURDATE()
            )";
    
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        // Send to student
        if ($row['email'] && shouldSendNotificationByUserId($conn, $row['user_id'], 'inactivity')) {
            $sent = sendInactivityReminder($row['email'], $row['full_name'], $row['days_inactive']);
            if ($sent) {
                echo "    Sent inactivity reminder to student: {$row['email']}\n";
            }
        }
        
        // Send to parent
        if ($row['parent_email'] && shouldSendNotificationByUserId($conn, $row['parent_id'], 'inactivity')) {
            $sent = sendInactivityReminder($row['parent_email'], $row['parent_name'], $row['days_inactive']);
            if ($sent) {
                echo "    Sent inactivity reminder to parent: {$row['parent_email']}\n";
                logNotificationSent($conn, $row['parent_id'], 'inactivity_reminder', [
                    'student_id' => $row['user_id']
                ]);
            }
        }
    }
}

/**
 * Process pending notification queue
 */
function processNotificationQueue($conn) {
    echo "  Processing notification queue...\n";
    
    $query = "SELECT * FROM notification_queue 
              WHERE status = 'pending' 
              AND attempts < 3 
              ORDER BY created_at ASC 
              LIMIT 50";
    
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $data = json_decode($row['data'], true);
        $sent = false;
        
        // Get user email
        $user_stmt = $conn->prepare("SELECT email, full_name FROM users WHERE user_id = ?");
        $user_stmt->bind_param("i", $row['user_id']);
        $user_stmt->execute();
        $user = $user_stmt->get_result()->fetch_assoc();
        $user_stmt->close();
        
        if (!$user) continue;
        
        switch ($row['notification_type']) {
            case 'new_material':
                $sent = sendMaterialNotification(
                    $user['email'],
                    $user['full_name'],
                    $data['material_title'],
                    $data['subject_name'],
                    $data['material_type']
                );
                break;
                
            case 'live_class_scheduled':
                $sent = sendLiveClassNotification(
                    $user['email'],
                    $user['full_name'],
                    $data['class_title'],
                    $data['subject_name'],
                    $data['scheduled_at'],
                    null,
                    false
                );
                break;
                
            case 'live_class_starting':
                $sent = sendLiveClassNotification(
                    $user['email'],
                    $user['full_name'],
                    $data['class_title'],
                    $data['subject_name'],
                    $data['scheduled_at'],
                    $data['class_url'] ?? null,
                    true
                );
                break;
                
            case 'payment_confirmation':
                $sent = sendPaymentConfirmation(
                    $user['email'],
                    $user['full_name'],
                    $data['subject_name'],
                    $data['amount'],
                    $data['duration'],
                    $data['reference']
                );
                break;
        }
        
        // Update queue status
        if ($sent) {
            $update_stmt = $conn->prepare("UPDATE notification_queue SET status = 'sent', sent_at = NOW() WHERE queue_id = ?");
        } else {
            $update_stmt = $conn->prepare("UPDATE notification_queue SET attempts = attempts + 1, error_message = 'Failed to send' WHERE queue_id = ?");
        }
        $update_stmt->bind_param("i", $row['queue_id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        if ($sent) {
            echo "    Sent {$row['notification_type']} to user ID {$row['user_id']}\n";
        }
    }
}

/**
 * Send reminders for classes starting in 30 minutes
 */
function sendUpcomingClassReminders($conn) {
    echo "  Checking upcoming class reminders...\n";
    
    $thirty_mins_later = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $now = date('Y-m-d H:i:s');
    
    $query = "SELECT 
                lc.*,
                s.subject_name,
                t.full_name as teacher_name
            FROM live_classes lc
            JOIN subjects s ON lc.subject_id = s.subject_id
            JOIN users t ON lc.teacher_id = t.user_id
            WHERE lc.status = 'scheduled'
            AND lc.scheduled_at BETWEEN ? AND ?
            AND lc.class_id NOT IN (
                SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.class_id')) AS UNSIGNED)
                FROM notification_queue 
                WHERE notification_type = 'class_starting_soon'
                AND status = 'sent'
            )";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $now, $thirty_mins_later);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($class = $result->fetch_assoc()) {
        // Get all students subscribed to this subject
        $students_query = "SELECT DISTINCT 
                            s.user_id, s.email, s.full_name,
                            p.user_id as parent_id, p.email as parent_email, p.full_name as parent_name
                        FROM users s
                        JOIN subscriptions sub ON s.user_id = sub.student_id
                        LEFT JOIN users p ON s.parent_id = p.user_id
                        WHERE sub.subject_id = ?
                        AND sub.status = 'active'
                        AND s.status = 'active'";
        
        $students_stmt = $conn->prepare($students_query);
        $students_stmt->bind_param("i", $class['subject_id']);
        $students_stmt->execute();
        $students = $students_stmt->get_result();
        
        while ($student = $students->fetch_assoc()) {
            // Notify student
            if (shouldSendNotificationByUserId($conn, $student['user_id'], 'class_starting_soon')) {
                sendLiveClassNotification(
                    $student['email'],
                    $student['full_name'],
                    $class['title'],
                    $class['subject_name'],
                    $class['scheduled_at'],
                    $class['class_url'],
                    true
                );
                echo "    Sent class reminder to student: {$student['email']}\n";
            }
            
            // Notify parent
            if ($student['parent_email'] && shouldSendNotificationByUserId($conn, $student['parent_id'], 'class_starting_soon')) {
                sendLiveClassNotification(
                    $student['parent_email'],
                    $student['parent_name'],
                    $class['title'],
                    $class['subject_name'],
                    $class['scheduled_at'],
                    $class['class_url'],
                    true
                );
                echo "    Sent class reminder to parent: {$student['parent_email']}\n";
            }
        }
        $students_stmt->close();
        
        // Log that we sent notifications for this class
        logNotificationSent($conn, 0, 'class_starting_soon', ['class_id' => $class['class_id']]);
    }
    $stmt->close();
}

/**
 * Check if user has notification preference enabled
 */
function shouldSendNotificationByUserId($conn, $user_id, $notification_type) {
    $stmt = $conn->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prefs = $result->fetch_assoc();
    $stmt->close();
    
    if (!$prefs) {
        return true; // Default to enabled
    }
    
    $type_map = [
        'subscription_reminder' => 'subscription_reminder',
        'inactivity' => 'inactivity_reminder',
        'new_material' => 'new_material',
        'live_class' => 'live_class',
        'class_starting_soon' => 'class_starting_soon',
        'payment' => 'payment_confirmation'
    ];
    
    $column = $type_map[$notification_type] ?? null;
    if ($column && isset($prefs[$column])) {
        return $prefs[$column] == 1;
    }
    
    return true;
}

/**
 * Log that notification was sent
 */
function logNotificationSent($conn, $user_id, $notification_type, $data) {
    $json_data = json_encode($data);
    $stmt = $conn->prepare("INSERT INTO notification_queue (user_id, notification_type, data, status, sent_at) VALUES (?, ?, ?, 'sent', NOW())");
    $stmt->bind_param("iss", $user_id, $notification_type, $json_data);
    $stmt->execute();
    $stmt->close();
}
?>
