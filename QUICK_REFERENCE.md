# MyLearn LMS - Quick Reference Card

## Default Login Credentials
**Admin:**
- URL: `http://yourdomain.com/index.php`
- Email: `admin@mylearn.com`
- Password: `admin123`
- **⚠️ Change immediately after first login**

**If default login doesn't work:** Use `reset_admin_password.php` (see Troubleshooting section)

## Quick Start (5 Minutes)

### Step 1: Setup Database
```bash
mysql -u root -p < config/init_db.sql
```

### Step 2: Configure Connection
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
define('DB_NAME', 'mylearn_lms');
```

### Step 3: Set Permissions
```bash
chmod -R 755 assets/uploads/
```

### Step 4: Access System
Visit: `http://yourdomain.com/index.php`

## Common Tasks

### Adding a Teacher (Admin)
1. Login as admin
2. Navigate to **Admin > Teachers**
3. Click **Add Teacher**
4. Enter: Name, Email, Password
5. Click **Add Teacher**

### Creating a Subject (Admin)
1. Navigate to **Admin > Subjects**
2. Click **Add Subject**
3. Enter: Name, Description
4. (Optional) Assign Teacher
5. Click **Add Subject**

### Uploading Content (Teacher)
1. Login as teacher
2. Navigate to **Teacher > Learning Materials**
3. Click **Upload Material**
4. Select Subject and Type
5. Upload file or enter text
6. Click **Upload Material**

### Scheduling a Class (Teacher)
1. Navigate to **Teacher > Live Classes**
2. Click **Schedule Class**
3. Enter: Subject, Title, Meeting URL, Date/Time
4. Click **Schedule Class**

### Enrolling a Child (Parent)
1. Login as parent
2. Add child at **Parent > My Children**
3. Navigate to **Parent > Enroll Child**
4. Browse subjects, click **Enroll Child**
5. Select child, duration, and pay

### Accessing Content (Student)
1. Login as student
2. Navigate to **Student > Learning Materials**
3. Filter by subject if needed
4. Click on material to view/play/download

## File Upload Limits

**Maximum Size:** 50MB per file

**Allowed Types:**
- **Videos:** MP4, AVI, MOV, WebM
- **Audio:** MP3, WAV, OGG
- **Documents:** PDF, DOC, DOCX, PPT, PPTX, TXT

## Subscription Pricing

| Duration | Price |
|----------|-------|
| 1 Month  | $50   |
| 2 Months | $90   |
| 3 Months | $120  |
| 6 Months | $220  |

## User Roles

### Admin
- Create subjects
- Manage teachers
- View all data
- Track activity

### Teacher
- Upload materials
- Schedule classes
- View statistics

### Parent
- Add children
- Enroll in subjects
- Manage subscriptions

### Student
- Access materials
- Join live classes
- View content

## Troubleshooting

### Can't Login with Admin Credentials?
**Error:** "Invalid email or password"
**Solution:**
1. Upload `reset_admin_password.php` to server root
2. Access via browser
3. Delete file after use
4. Login with admin@mylearn.com / admin123

### Can't Login? (Other Issues)
- Check credentials
- Verify account status
- Clear browser cookies

### Upload Failed?
- Check file size (< 50MB)
- Verify file type allowed
- Check folder permissions: `chmod 755 assets/uploads/`

### Database Error?
- Verify credentials in `config/database.php`
- Check MySQL is running: `service mysql status`
- Ensure database exists

### Blank Page / mysqli Error (cPanel)?
**Error:** "Class 'mysqli' not found"
**Solution:**
1. Login to cPanel
2. Go to "Select PHP Version" or "MultiPHP Manager"
3. Enable "mysqli" extension
4. Save and restart
5. See INSTALL.md for detailed steps

### Page Not Found?
- Check `.htaccess` exists
- Verify Apache mod_rewrite enabled
- Check file permissions

## Security Checklist

- [ ] Changed default admin password
- [ ] Database user has minimal permissions
- [ ] SSL certificate installed (HTTPS)
- [ ] Regular database backups configured
- [ ] Upload folder permissions: 755
- [ ] Error logging enabled
- [ ] PHP version updated

## Backup Commands

**Database Backup:**
```bash
mysqldump -u root -p mylearn_lms > backup_$(date +%Y%m%d).sql
```

**Files Backup:**
```bash
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz assets/uploads/
```

**Restore Database:**
```bash
mysql -u root -p mylearn_lms < backup_20231222.sql
```

## Support Resources

- **Installation Guide:** `INSTALL.md`
- **User Manual:** `USER_GUIDE.md`
- **Project Summary:** `PROJECT_SUMMARY.md`
- **Main Documentation:** `README.md`

## System Requirements

**Minimum:**
- PHP 7.4+
- MySQL 5.7+
- Apache 2.4+ / Nginx 1.18+
- 50MB+ disk space for uploads

**Recommended:**
- PHP 8.0+
- MySQL 8.0+
- 2GB+ RAM
- SSL certificate
- Regular backups

## Key Features

✅ Role-based authentication
✅ Multi-format content support
✅ Live class integration
✅ Flexible subscriptions
✅ Activity tracking
✅ Responsive design
✅ Secure file uploads
✅ Parent-child linking

## Performance Tips

- Enable PHP OPcache
- Use CDN for Bootstrap/jQuery
- Optimize uploaded videos
- Regular database cleanup
- Monitor upload folder size
- Index frequently queried columns

## Contact & Support

For issues or questions:
- Check documentation first
- Review error logs
- Open GitHub issue
- Contact system administrator

---

**Version:** 1.0  
**Last Updated:** 2024  
**Status:** Production Ready ✅
