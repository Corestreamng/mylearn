# MyLearn - Learning Management System

A comprehensive LMS where admins create subjects, assign teachers, and track all activity. Teachers get a dashboard to upload learning materials (text, audio, video) and host live classes. Parents enroll their children, selecting subscription durations and making payments. Students then access all assigned content and live sessions through their dashboard.

## Features

### Admin Dashboard
- Create and manage subjects
- Assign teachers to subjects
- Track all system activity
- View and manage teachers, parents, and students
- Monitor subscriptions and payments

### Teacher Dashboard
- Upload learning materials (text, audio, video, documents)
- Schedule and host live classes
- View assigned subjects
- Track upcoming classes

### Parent Dashboard
- Register and add children
- Enroll children in subjects
- Select subscription duration (1, 2, 3, or 6 months)
- Process payments
- View all subscriptions

### Student Dashboard
- Access all enrolled subjects
- View and consume learning materials (text, video, audio)
- Join live classes
- Track upcoming sessions

## Tech Stack

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5
- **Backend**: PHP (vanilla, no frameworks)
- **Database**: MySQL

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PHP mysqli extension enabled

### Setup Steps

1. Clone the repository:
```bash
git clone https://github.com/Corestreamng/mylearn.git
cd mylearn
```

2. **Check System Requirements (Recommended for cPanel/Shared Hosting):**
   - Upload `system_check.php` to your server
   - Access it via browser: `http://yourdomain.com/system_check.php`
   - Follow the instructions to fix any issues
   - **Delete the file after checking for security**

3. Create the database:
```bash
mysql -u root -p < config/init_db.sql
```

4. Configure database connection:
Edit `config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'mylearn_lms');
```

5. Set up file permissions:
```bash
chmod -R 755 assets/uploads/
```

6. Access the application:
Open your browser and navigate to `http://localhost/mylearn`

## Default Login Credentials

**Admin Account:**
- Email: `admin@mylearn.com`
- Password: `admin123`

**Note:** If the default login doesn't work, run `reset_admin_password.php` (upload it to your server, access via browser, then delete it). See INSTALL.md for details.

## Usage

### For Admins:
1. Login with admin credentials
2. Create subjects from the Subjects page
3. Add teachers from the Teachers page
4. Assign teachers to subjects

### For Teachers:
1. Login with your credentials (created by admin)
2. Upload learning materials to your assigned subjects
3. Schedule live classes with video conferencing links

### For Parents:
1. Register a new parent account
2. Add your children from the Children page
3. Enroll children in subjects
4. Select subscription duration and complete payment

### For Students:
1. Login with credentials (created by parent)
2. Access learning materials from enrolled subjects
3. Join scheduled live classes
4. View all available content

## File Structure

```
mylearn/
├── admin/              # Admin dashboard and functionality
├── teacher/            # Teacher dashboard and functionality
├── parent/             # Parent dashboard and functionality
├── student/            # Student dashboard and functionality
├── assets/
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   └── uploads/       # Uploaded materials
├── config/            # Database configuration
├── includes/          # Shared PHP includes
└── index.php          # Login page
```

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt algorithm
- **SQL Injection Protection**: All queries use prepared statements with parameter binding
- **XSS Protection**: Input sanitization with `htmlspecialchars()` with ENT_QUOTES and UTF-8 encoding
- **Session Security**: Session regeneration to prevent session fixation attacks
- **CSRF Protection**: Helper functions for token generation and verification
- **File Upload Security**: 
  - Type validation with allowed extension whitelist
  - Size limits (50MB maximum)
  - Unique filename generation to prevent overwrites
- **Integer Validation**: For dynamic IN clauses, IDs are cast with `array_map('intval')` to ensure integer-only values
- **Security Headers**: X-Frame-Options, X-XSS-Protection, and Content-Type-Options via .htaccess

## Subscription Pricing

- 1 Month: $50
- 2 Months: $90
- 3 Months: $120
- 6 Months: $220

## Support

For issues or questions, please open an issue on GitHub.

## License

This project is open source and available under the MIT License.
