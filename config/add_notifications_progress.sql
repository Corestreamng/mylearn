-- Migration: Add notification preferences and learning progress tracking tables
-- Run this SQL file to add new features to your existing database

USE mylearn_lms;

-- Notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    pref_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    new_material TINYINT(1) DEFAULT 1 COMMENT 'Notify when new material is uploaded',
    live_class TINYINT(1) DEFAULT 1 COMMENT 'Notify when live class is scheduled/started',
    subscription_reminder TINYINT(1) DEFAULT 1 COMMENT 'Notify before subscription expires',
    inactivity_reminder TINYINT(1) DEFAULT 1 COMMENT 'Notify after days of inactivity',
    payment_confirmation TINYINT(1) DEFAULT 1 COMMENT 'Notify on successful payment',
    class_starting_soon TINYINT(1) DEFAULT 1 COMMENT 'Notify 30 mins before class',
    weekly_progress TINYINT(1) DEFAULT 1 COMMENT 'Weekly progress summary',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Learning progress tracking table
CREATE TABLE IF NOT EXISTS learning_progress (
    progress_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    material_id INT NOT NULL,
    completed TINYINT(1) DEFAULT 0 COMMENT '1 if completed',
    time_spent INT DEFAULT 0 COMMENT 'Time spent in seconds',
    last_accessed DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_material (student_id, material_id),
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES learning_materials(material_id) ON DELETE CASCADE
);

-- Notification queue for scheduled emails (cron job processing)
CREATE TABLE IF NOT EXISTS notification_queue (
    queue_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    data JSON COMMENT 'JSON data for the notification',
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    error_message TEXT NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Add last_login column to users table for inactivity tracking
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME DEFAULT NULL;

-- Create index for faster queries
CREATE INDEX IF NOT EXISTS idx_progress_student ON learning_progress(student_id);
CREATE INDEX IF NOT EXISTS idx_progress_material ON learning_progress(material_id);
CREATE INDEX IF NOT EXISTS idx_queue_status ON notification_queue(status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_end_date ON subscriptions(end_date);
