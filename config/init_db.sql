-- Create Database
CREATE DATABASE IF NOT EXISTS mylearn_lms;
USE mylearn_lms;

-- Users table (for all user types)
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'teacher', 'parent', 'student') NOT NULL,
    parent_id INT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (parent_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Subjects table
CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(255) NOT NULL,
    description TEXT,
    price_per_month DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Price in Naira per month',
    teacher_id INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Learning materials table
CREATE TABLE IF NOT EXISTS learning_materials (
    material_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    material_type ENUM('text', 'video', 'audio', 'document') NOT NULL,
    file_path VARCHAR(500),
    content TEXT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

-- Live classes table
CREATE TABLE IF NOT EXISTS live_classes (
    class_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    class_url VARCHAR(500),
    scheduled_at DATETIME NOT NULL,
    duration INT DEFAULT 60,
    status ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id)
);

-- Subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
    subscription_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    parent_id INT NOT NULL,
    subject_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    duration_months INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_reference VARCHAR(255) NULL,
    payment_date DATETIME,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(user_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

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

-- Create indexes for faster queries
CREATE INDEX idx_progress_student ON learning_progress(student_id);
CREATE INDEX idx_progress_material ON learning_progress(material_id);
CREATE INDEX idx_queue_status ON notification_queue(status);
CREATE INDEX idx_subscriptions_end_date ON subscriptions(end_date);
CREATE INDEX idx_users_last_login ON users(last_login);

-- Insert default admin user (password: admin123)
-- Note: If you get duplicate entry error, the admin already exists. Skip this or delete and re-run.
INSERT INTO users (email, password, full_name, user_type) 
VALUES ('admin@mylearn.com', '$2y$10$XcbJ/p4qPFijy./isgOkDOmq2liNzfZ4Eyn6TqCieRlaE5ciPmCkK', 'System Admin', 'admin')
ON DUPLICATE KEY UPDATE password = '$2y$10$XcbJ/p4qPFijy./isgOkDOmq2liNzfZ4Eyn6TqCieRlaE5ciPmCkK';

