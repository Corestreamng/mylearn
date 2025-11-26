# Mobile Navigation, Email Notifications & Learning Progress

This document describes the new features added to MyLearn LMS:
- Mobile-responsive navigation
- Email notification system
- Learning progress tracking

## 1. Mobile Navigation

### Features
- **Hamburger Menu**: A floating button (☰) appears on screens smaller than 768px
- **Slide-out Sidebar**: Navigation slides in from the left when tapped
- **Overlay Background**: Dark overlay prevents interaction with page content
- **Auto-close**: Sidebar closes when clicking a link or the overlay

### Implementation
All dashboard pages now include:
- Mobile toggle button (`.mobile-nav-toggle`)
- Sidebar overlay (`.sidebar-overlay`)
- Sidebar with `id="sidebar"` for JavaScript targeting
- Shared JavaScript file: `/assets/js/mobile-nav.js`

### Testing
1. Open any dashboard page on a mobile device or resize browser below 768px
2. Tap the hamburger menu icon
3. Navigate using the slide-out menu
4. Tap overlay or link to close

---

## 2. Email Notification System

### Features
Email notifications are sent for:
- **New User Registration**: Welcome email sent to new parents
- **Student Inactivity**: Alert when student hasn't logged in for 3+ days
- **Live Class Scheduled**: Notification when teacher schedules a class
- **Live Class Starting**: Reminder 30 minutes before class starts
- **New Material Uploaded**: Alert when teacher uploads content
- **Subscription Expiry**: Reminders at 7, 3, and 1 day before expiration
- **Payment Confirmation**: Receipt after successful payment

### Technical Setup (cPanel)

#### Using PHP mail() Function
The system uses PHP's built-in `mail()` function, which works with cPanel's email settings.

1. **Configure Email Settings**
   Edit `/includes/email.php`:
   ```php
   define('MAIL_FROM_NAME', 'MyLearn LMS');
   define('MAIL_FROM_EMAIL', 'noreply@yourdomain.com');
   ```

2. **Set Up Cron Job**
   In cPanel → Cron Jobs, add:
   ```
   0 * * * * /usr/bin/php /home/youruser/public_html/cron/send_notifications.php
   ```
   This runs every hour to:
   - Send subscription expiry reminders
   - Send inactivity reminders
   - Process notification queue
   - Send upcoming class reminders

### Parent Notification Preferences

Parents can customize their notifications at `/parent/notifications.php`:
- New Learning Materials (on/off)
- Live Class Scheduled (on/off)
- Class Starting Soon (on/off)
- Weekly Progress Report (on/off)
- Inactivity Reminder (on/off)
- Subscription Expiry Reminder (on/off)
- Payment Confirmation (on/off)

### Database Tables Added

```sql
-- Notification preferences
CREATE TABLE notification_preferences (
    pref_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    new_material TINYINT(1) DEFAULT 1,
    live_class TINYINT(1) DEFAULT 1,
    subscription_reminder TINYINT(1) DEFAULT 1,
    inactivity_reminder TINYINT(1) DEFAULT 1,
    payment_confirmation TINYINT(1) DEFAULT 1,
    class_starting_soon TINYINT(1) DEFAULT 1,
    weekly_progress TINYINT(1) DEFAULT 1,
    ...
);

-- Notification queue for cron processing
CREATE TABLE notification_queue (
    queue_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type VARCHAR(50),
    data JSON,
    status ENUM('pending', 'sent', 'failed'),
    ...
);
```

### Migration for Existing Installations

Run this SQL file:
```bash
mysql -u username -p database_name < config/add_notifications_progress.sql
```

---

## 3. Learning Progress Tracking

### Features

#### For Students (`/student/progress.php`)
- **Overall Progress Circle**: Visual percentage of completed materials
- **Achievements/Badges**:
  - "Getting Started" (25% complete)
  - "Halfway There" (50% complete)
  - "Almost Done" (75% complete)
  - "Champion" (100% complete)
- **Subject-wise Progress**: Breakdown by each enrolled subject
- **Material Type Stats**: Progress by video/audio/document/text
- **Recent Activity**: List of recently accessed materials

#### For Parents (`/parent/progress.php`)
- **Per-Child Progress**: See each child's overall progress
- **Subject Breakdown**: Progress in each subject for each child
- **Color-coded Status**: 
  - Green (80%+): Excellent
  - Blue (60-79%): Good Progress
  - Yellow (40-59%): Keep Going
  - Red (<40%): Needs Attention
- **Recent Activity**: What materials child accessed recently

### How Progress is Tracked

1. When a student opens a material, a record is created in `learning_progress`
2. Time spent is tracked (in seconds)
3. Student can mark materials as completed
4. Progress percentage = (completed materials / total materials) × 100

### Database Tables Added

```sql
CREATE TABLE learning_progress (
    progress_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    material_id INT NOT NULL,
    completed TINYINT(1) DEFAULT 0,
    time_spent INT DEFAULT 0,
    last_accessed DATETIME,
    completed_at DATETIME,
    UNIQUE KEY unique_student_material (student_id, material_id),
    ...
);
```

---

## New Navigation Links

### Parent Dashboard Sidebar
- Dashboard
- My Children
- **Learning Progress** ← NEW
- Enroll Child
- Subscriptions
- **Notifications** ← NEW
- Logout

### Student Dashboard Sidebar
- Dashboard
- **My Progress** ← NEW
- Learning Materials
- Live Classes
- Logout

---

## Files Added/Modified

### New Files
- `/assets/js/mobile-nav.js` - Mobile navigation JavaScript
- `/assets/css/style.css` - Updated with mobile styles
- `/includes/email.php` - Email helper functions
- `/cron/send_notifications.php` - Cron job for sending emails
- `/parent/notifications.php` - Notification preferences page
- `/parent/progress.php` - Parent learning progress view
- `/student/progress.php` - Student learning progress view
- `/config/add_notifications_progress.sql` - Database migration

### Modified Files
- All dashboard pages (admin, teacher, parent, student) - Mobile nav added
- `/config/init_db.sql` - New tables included
- `/login.php` - Updates last_login timestamp

---

## Troubleshooting

### Mobile Navigation Not Working
1. Clear browser cache
2. Check if `/assets/js/mobile-nav.js` is loading (check Network tab)
3. Verify the sidebar has `id="sidebar"`
4. Check browser console for JavaScript errors

### Emails Not Sending
1. Verify PHP mail() is working on your server
2. Check cPanel email logs: `/home/username/logs/mail.log`
3. Test with: `php -r "mail('test@example.com', 'Test', 'Test body');"`
4. Ensure cron job is running: Check "Cron Jobs" in cPanel

### Progress Not Tracking
1. Run the migration SQL file
2. Check if `learning_progress` table exists
3. Verify student has active subscriptions
4. Check browser console for errors when accessing materials

---

## Subscription Reminder Schedule

| Days Before Expiry | Email Sent |
|-------------------|------------|
| 7 days            | ✅          |
| 3 days            | ✅          |
| 1 day             | ✅          |

Reminders are sent once per subscription per milestone (won't resend if already sent).

---

## Testing Checklist

- [ ] Mobile navigation works on all dashboard pages
- [ ] Sidebar opens/closes correctly
- [ ] Notification settings save properly
- [ ] Welcome email sent on new registration
- [ ] Payment confirmation email sent
- [ ] Progress bars display correctly
- [ ] Achievements unlock at correct percentages
- [ ] Cron job runs without errors
- [ ] Subscription reminders sent at correct times
