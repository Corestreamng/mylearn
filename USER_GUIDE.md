# MyLearn LMS - User Guide

## Table of Contents
1. [Getting Started](#getting-started)
2. [Admin Guide](#admin-guide)
3. [Teacher Guide](#teacher-guide)
4. [Parent Guide](#parent-guide)
5. [Student Guide](#student-guide)

---

## Getting Started

### Accessing the System

Navigate to your MyLearn installation URL in a web browser. You'll see the login page.

### User Roles

The system has four user roles:
- **Admin**: System administrator with full access
- **Teacher**: Can upload materials and schedule classes
- **Parent**: Can register children and manage subscriptions
- **Student**: Can access learning materials and join classes

---

## Admin Guide

### Login
- Email: `admin@mylearn.com`
- Password: `admin123` (change immediately after first login)

### Dashboard Overview

The admin dashboard displays:
- Total number of teachers, students, and parents
- Active subjects and subscriptions
- Learning materials count
- Recent system activities

### Managing Subjects

**To Create a Subject:**
1. Navigate to **Admin > Subjects**
2. Click **Add Subject** button
3. Fill in:
   - Subject Name
   - Description
   - Assign Teacher (optional)
4. Click **Add Subject**

**To Edit a Subject:**
1. Find the subject in the table
2. Click the edit icon (pencil)
3. Update information
4. Click **Update Subject**

**To Delete a Subject:**
1. Find the subject in the table
2. Click the delete icon (trash)
3. Confirm deletion

### Managing Teachers

**To Add a Teacher:**
1. Navigate to **Admin > Teachers**
2. Click **Add Teacher** button
3. Fill in:
   - Full Name
   - Email
   - Password
4. Click **Add Teacher**

**To Edit a Teacher:**
1. Find the teacher in the table
2. Click the edit icon
3. Update name or status
4. Click **Update Teacher**

### Viewing Reports

**Activity Logs:**
- Navigate to **Admin > Activity Logs**
- View all user actions with timestamps
- Filter by user type or date

**Subscriptions:**
- Navigate to **Admin > Subscriptions**
- View all subscription details
- Check payment status

---

## Teacher Guide

### Dashboard Overview

Your dashboard shows:
- Number of subjects assigned to you
- Total materials uploaded
- Upcoming live classes

### Uploading Learning Materials

**To Upload Material:**
1. Navigate to **Teacher > Learning Materials**
2. Click **Upload Material** button
3. Select:
   - Subject (from your assigned subjects)
   - Material Type (Text, Video, Audio, Document)
4. Fill in title and description
5. For Text: Enter content directly
6. For Video/Audio/Document: Upload file
7. Click **Upload Material**

**Supported File Types:**
- **Video**: MP4, AVI, MOV, WebM
- **Audio**: MP3, WAV, OGG
- **Documents**: PDF, DOC, DOCX, PPT, PPTX

**Maximum File Size**: 50MB

### Scheduling Live Classes

**To Schedule a Class:**
1. Navigate to **Teacher > Live Classes**
2. Click **Schedule Class** button
3. Fill in:
   - Subject
   - Class Title
   - Description
   - Class URL (Zoom, Google Meet, etc.)
   - Scheduled Date & Time
   - Duration (in minutes)
4. Click **Schedule Class**

**Managing Class Status:**
- **Start**: Change status to "Ongoing" when class begins
- **End**: Mark class as "Completed" when finished
- **Delete**: Remove cancelled classes

---

## Parent Guide

### Registration

**To Register:**
1. Go to the login page
2. Click **Register as Parent**
3. Fill in:
   - Full Name
   - Email
   - Password
   - Confirm Password
4. Click **Register**

### Dashboard Overview

Your dashboard displays:
- Number of children registered
- Active subscriptions
- Available subjects

### Managing Children

**To Add a Child:**
1. Navigate to **Parent > My Children**
2. Click **Add Child** button
3. Fill in:
   - Child's Full Name
   - Email (for their login)
   - Password
4. Click **Add Child**

### Enrolling Children

**To Enroll a Child in a Subject:**
1. Navigate to **Parent > Enroll Child**
2. Browse available subjects
3. Click **Enroll Child** on desired subject
4. Select:
   - Child
   - Subscription Duration
5. Review amount
6. Click **Pay & Enroll**

**Subscription Options:**
- 1 Month: $50
- 2 Months: $90
- 3 Months: $120
- 6 Months: $220

### Viewing Subscriptions

Navigate to **Parent > Subscriptions** to:
- View all active and expired subscriptions
- Check payment status
- See subscription end dates

---

## Student Guide

### Login

Use the email and password created by your parent to login.

### Dashboard Overview

Your dashboard shows:
- Number of enrolled subjects
- Available learning materials
- Upcoming live classes

### Accessing Learning Materials

**To View Materials:**
1. Navigate to **Student > Learning Materials**
2. Filter by subject (optional)
3. Click on a material card to:
   - **Text**: Read content in a modal
   - **Video**: Play video in a modal
   - **Audio**: Play audio in a modal
   - **Documents**: Download file

### Joining Live Classes

**To Join a Class:**
1. Navigate to **Student > Live Classes**
2. Find the scheduled class
3. When status is "Ongoing":
   - Click **Join Now** button
   - You'll be redirected to the class URL
4. For scheduled classes:
   - Click **Class Link** to see the meeting URL

### Tips for Students

- Check your dashboard daily for new materials
- Review class schedules to avoid missing sessions
- Download important documents for offline access
- Contact your teacher if you have questions about materials

---

## Troubleshooting

### Cannot Login
- Verify your email and password
- Check if your account is active
- Contact admin if issues persist

### Cannot Upload Files (Teachers)
- Check file size (max 50MB)
- Verify file type is supported
- Ensure you have permission to write to uploads directory

### Video/Audio Not Playing (Students)
- Check your browser supports HTML5 media
- Try a different browser
- Ensure stable internet connection

### Payment Issues (Parents)
- In this version, payments are auto-completed
- Contact admin if subscription is not activated

---

## Best Practices

### For Admins
- Regularly review activity logs
- Back up database weekly
- Monitor subscription expirations
- Keep teacher assignments up to date

### For Teachers
- Upload materials regularly
- Schedule classes in advance
- Use clear, descriptive titles
- Test video conferencing links before class

### For Parents
- Keep subscription information up to date
- Monitor your children's enrolled subjects
- Review subscription expiration dates
- Renew subscriptions before they expire

### For Students
- Check for new materials daily
- Attend all scheduled live classes
- Download materials for offline study
- Report any technical issues promptly

---

## Support

For technical support or questions:
- Contact your system administrator
- Check the INSTALL.md file for setup issues
- Review the README.md for general information

---

## System Requirements for Users

### Recommended Browsers
- Google Chrome (latest)
- Mozilla Firefox (latest)
- Microsoft Edge (latest)
- Safari (latest)

### Internet Connection
- Minimum: 2 Mbps for basic features
- Recommended: 5+ Mbps for video streaming and live classes

### Device Compatibility
- Desktop/Laptop: Windows, macOS, Linux
- Tablets: iPad, Android tablets
- Mobile: Responsive design works on smartphones
