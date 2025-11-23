# MyLearn LMS - Installation Guide

## Quick Start Guide

### 1. System Requirements

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Extensions**: mysqli, json, fileinfo

### 2. Database Setup

#### Option A: Using MySQL Command Line

```bash
# Login to MySQL
mysql -u root -p

# Create database and import schema
source config/init_db.sql

# Verify installation
USE mylearn_lms;
SHOW TABLES;
```

#### Option B: Using phpMyAdmin

1. Open phpMyAdmin in your browser
2. Create a new database named `mylearn_lms`
3. Select the database
4. Click "Import" tab
5. Choose file: `config/init_db.sql`
6. Click "Go"

### 3. Configuration

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');      // Your database host
define('DB_USER', 'root');           // Your database username
define('DB_PASS', 'your_password');  // Your database password
define('DB_NAME', 'mylearn_lms');    // Database name
```

### 4. File Permissions

Set proper permissions for upload directories:

```bash
chmod -R 755 assets/uploads/
chmod -R 755 assets/uploads/videos/
chmod -R 755 assets/uploads/audios/
chmod -R 755 assets/uploads/documents/
```

### 5. Web Server Configuration

#### Apache (.htaccess)

Create a `.htaccess` file in the root directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Prevent access to sensitive files
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>
    
    # Protect config directory
    <DirectoryMatch "^/.*/config">
        Require all denied
    </DirectoryMatch>
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Set upload size limits
php_value upload_max_filesize 50M
php_value post_max_size 50M
php_value max_execution_time 300
php_value max_input_time 300
```

#### Nginx

Add to your nginx configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/mylearn;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # Increase upload size
    client_max_body_size 50M;
}
```

### 6. Testing the Installation

1. Open your browser and navigate to the application URL
2. You should see the login page
3. Login with default admin credentials:
   - Email: `admin@mylearn.com`
   - Password: `admin123`

### 7. First Steps After Installation

1. **Change Admin Password**: Login as admin and update the password
2. **Add Teachers**: Navigate to Admin > Teachers and create teacher accounts
3. **Create Subjects**: Go to Admin > Subjects and add subjects
4. **Assign Teachers**: Edit subjects and assign teachers to them

## Common Issues and Solutions

### Database Connection Error

**Error**: "Connection failed"

**Solution**:
- Check database credentials in `config/database.php`
- Verify MySQL service is running: `sudo service mysql status`
- Ensure database user has proper permissions

### Upload Directory Permission Error

**Error**: "Failed to move uploaded file"

**Solution**:
```bash
sudo chown -R www-data:www-data assets/uploads/
sudo chmod -R 755 assets/uploads/
```

### PHP Extension Missing

**Error**: "Call to undefined function mysqli_connect()" or "Class 'mysqli' not found"

**Solution**:

**For Ubuntu/Debian:**
```bash
sudo apt-get install php-mysqli
sudo service apache2 restart
```

**For CentOS/RHEL:**
```bash
sudo yum install php-mysqli
sudo systemctl restart httpd
```

**For cPanel/Shared Hosting:**

If you see the error "Class 'mysqli' not found", the mysqli extension is not enabled on your hosting account. Here's how to fix it:

1. **Using MultiPHP Manager (cPanel):**
   - Login to your cPanel
   - Navigate to "Software" section
   - Click on "MultiPHP Manager" or "Select PHP Version"
   - Select your domain
   - Click on "PHP Extensions" or "Extensions"
   - Find and enable "mysqli" extension
   - Click "Save"
   - Restart your website (some hosts do this automatically)

2. **Using PHP Selector (CloudLinux):**
   - Login to your cPanel
   - Find "Select PHP Version" under Software section
   - Click on "Extensions" tab
   - Check the box next to "mysqli"
   - Click "Save"

3. **Contact Hosting Support:**
   - If you don't have access to PHP extension settings, contact your hosting provider
   - Ask them to enable the "mysqli" extension for your account
   - Most hosting providers can do this within minutes

4. **Verify Installation:**
   After enabling mysqli, create a file called `phpinfo.php` in your root directory:
   ```php
   <?php phpinfo(); ?>
   ```
   Access it via your browser (e.g., `http://yourdomain.com/phpinfo.php`) and search for "mysqli". You should see it listed as enabled. **Delete this file after verification for security.**

### Session Issues

**Error**: "Session not working"

**Solution**:
- Check PHP session directory permissions
- Verify `session.save_path` in `php.ini`

```bash
# Find session path
php -i | grep session.save_path

# Set permissions
sudo chmod 733 /var/lib/php/sessions
```

## Security Recommendations

1. **Change Default Admin Password** immediately after installation
2. **Use HTTPS** in production (configure SSL certificate)
3. **Regular Backups**: Backup database and uploaded files regularly
4. **Update PHP**: Keep PHP and MySQL updated to latest stable versions
5. **File Upload Restrictions**: Validate file types and sizes server-side
6. **Database User**: Create a dedicated MySQL user with limited permissions

## Backup and Restore

### Backup Database

```bash
mysqldump -u root -p mylearn_lms > backup_$(date +%Y%m%d).sql
```

### Backup Uploaded Files

```bash
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz assets/uploads/
```

### Restore Database

```bash
mysql -u root -p mylearn_lms < backup_20231222.sql
```

### Restore Uploaded Files

```bash
tar -xzf uploads_backup_20231222.tar.gz
```

## Support

For additional help:
- Check the main README.md file
- Review the code comments
- Open an issue on GitHub

## Production Deployment Checklist

- [ ] Database credentials configured
- [ ] Admin password changed
- [ ] SSL certificate installed
- [ ] File permissions set correctly
- [ ] Error logging enabled
- [ ] Backup system configured
- [ ] Security headers enabled
- [ ] Upload size limits set
- [ ] PHP configuration optimized
- [ ] Database user has minimal permissions
