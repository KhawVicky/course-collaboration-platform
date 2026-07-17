# Course Collaboration Platform - Complete A1 + A2

A plain-PHP course collaboration application for XAMPP using HTML5, CSS3, JavaScript, Bootstrap 5, PDO, and MySQL.

## Completed functions

### Authentication and access

- Student registration with PHP validation and duplicate-email handling
- Student and instructor login using `password_verify()`
- Password storage using `password_hash()`
- PHP sessions, session ID regeneration, and POST-only logout
- CSRF protection for state-changing requests
- Student and instructor role guards
- PDO prepared statements, escaped output, ID validation, ownership checks, and enrolment checks

### Student functions

- Browse available courses, enrol, and view enrolled courses
- View and download protected course materials
- View assignments, submit, resubmit, view attempt history, and see the latest status
- View grades and instructor feedback
- View announcements
- Create discussion threads and post replies
- View and edit their own collaboration profile
- Save skills, Online or Offline collaboration mode, and optional availability
- View membership status without being able to edit role or membership

### Instructor functions

- Create courses and view owned courses
- View enrolled students and their collaboration profiles
- Upload course materials and download protected files
- Create and edit assignments
- View and download student submissions
- Create or update grades and feedback
- Post announcements
- Create discussion threads and post replies
- Monitor basic student participation and submission status

### A2 membership rules

- Membership is separate from role and course enrolment
- `Member`: original submission plus unlimited resubmissions before the deadline
- `Non-member`: original submission plus a maximum of two resubmissions before the deadline
- Attempt 4 is rejected for a non-member
- All late submissions and resubmissions are rejected in PHP
- Direct POST requests cannot bypass the deadline or membership limit
- Failed attempts do not create submission rows and uploaded temporary files are removed
- New students automatically receive `Non-member`
- A missing membership record is treated as `Non-member`
- Instructors do not receive membership records

No priority material access, payment, or subscription feature is included.

## Main folder structure

```text
course-collaboration-platform/
|-- index.php
|-- discussions.php
|-- download.php
|-- database.sql
|-- README.md
|-- migrations/
|   `-- a2_student_profiles_memberships.sql
|-- config/
|   |-- app.php
|   |-- database.php
|   `-- session.php
|-- includes/
|   |-- auth.php
|   |-- upload.php
|   |-- header.php
|   |-- footer.php
|   `-- forbidden.php
|-- auth/
|   |-- register.php
|   |-- login.php
|   `-- logout.php
|-- student/
|   |-- dashboard.php
|   |-- profile.php
|   |-- courses.php
|   |-- enrolled.php
|   |-- course.php
|   |-- assignment.php
|   `-- announcements.php
|-- instructor/
|   |-- dashboard.php
|   |-- courses.php
|   |-- course.php
|   |-- student_profile.php
|   |-- materials.php
|   |-- assignments.php
|   |-- edit_assignment.php
|   |-- submissions.php
|   |-- grade.php
|   |-- announcements.php
|   `-- monitoring.php
|-- assets/
|   |-- css/style.css
|   `-- js/main.js
|-- uploads/
|   |-- .htaccess
|   |-- materials/
|   `-- submissions/
`-- tests/
    |-- a1_smoke.py
    |-- a2_smoke.py
    |-- stage2_smoke.py
    |-- database_assertions.php
    |-- a2_database_assertions.php
    `-- assets/
```

## Test accounts

`database.sql` creates these local accounts:

| Role | Membership | Email | Password |
|---|---|---|---|
| Student | Member | `student@example.com` | `Student123!` |
| Student | Non-member | `student2@example.com` | `Student123!` |
| Instructor | Not applicable | `instructor@example.com` | `Instructor123!` |
| Instructor | Not applicable | `instructor2@example.com` | `Instructor123!` |

These accounts are for local assessment only.

## Database tables

The platform uses the ten A1 tables and two A2 tables:

1. `users`
2. `courses`
3. `enrolments`
4. `course_materials`
5. `assignments`
6. `submissions`
7. `grades`
8. `announcements`
9. `discussion_threads`
10. `discussion_replies`
11. `student_profiles`
12. `memberships`

`student_profiles.user_id` and `memberships.user_id` are unique, so each student can have only one of each record. Submission history uses one row per attempt. `(assignment_id, student_id, attempt_number)` is unique, and `is_latest` identifies the current attempt.

## Main page flow

```text
Homepage
|-- Student registration -> Non-member account
`-- Shared login
    |-- Student dashboard
    |   |-- Profile -> View/edit collaboration details and membership
    |   |-- Browse courses -> Course details -> Enrol
    |   `-- My courses -> Course
    |       |-- Materials -> Protected download
    |       |-- Assignments -> Submit/resubmit -> History -> Grade/feedback
    |       |-- Announcements
    |       `-- Discussions -> Thread/reply
    `-- Instructor dashboard
        `-- My courses -> Create/open course
            |-- Enrolled students -> Collaboration profile
            |-- Materials -> Upload/list/download
            |-- Assignments -> Create/edit
            |-- Submissions -> Download -> Grade/feedback
            |-- Announcements -> Post/list
            |-- Discussions -> Thread/reply
            `-- Monitoring
```

## XAMPP setup

### New installation

1. Copy the project to `C:\xampp\htdocs\course-collaboration-platform`.
2. Start Apache and MySQL in XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`.
4. Choose **Import**, select `database.sql`, and run it.
5. Confirm Apache can write to `uploads/materials` and `uploads/submissions`.
6. Open `http://localhost/course-collaboration-platform/`.

The login page is `http://localhost/course-collaboration-platform/auth/login.php`.

### Upgrade an existing A1 database

1. Back up the `course_collaboration` database in phpMyAdmin.
2. Import `migrations/a2_student_profiles_memberships.sql`.
3. Do not import `database.sql` over live A1 data because it recreates all tables.

Default database settings are host `127.0.0.1`, port `3306`, database `course_collaboration`, user `root`, and an empty password. Optional environment overrides are `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_BASE_URL`, and `SESSION_SAVE_PATH`.

Bootstrap and Google Fonts load from CDNs. The PHP and database functions remain local.

## Manual testing checklist

- [ ] Register a student and confirm the membership is Non-member
- [ ] Log in and log out with student and instructor accounts
- [ ] Confirm student/instructor protected-page access and cross-role denial
- [ ] Save skills, Online mode, Offline mode, and availability
- [ ] Confirm a student cannot edit another profile or change role/membership
- [ ] Enrol a student, then view that profile as the course owner
- [ ] Confirm a different instructor cannot view that profile
- [ ] Create a course and verify it appears only for its owner
- [ ] Enrol and confirm duplicate enrolment is prevented
- [ ] Upload/download a valid material and reject an invalid file
- [ ] Create and edit an assignment
- [ ] As Member, complete at least four attempts before the deadline
- [ ] As Non-member, complete attempts 1, 2, and 3
- [ ] Confirm Non-member attempt 4 shows `Resubmission limit reached`
- [ ] Confirm direct POST cannot bypass the resubmission limit
- [ ] Confirm Member and Non-member late POST requests are rejected
- [ ] Confirm rejected attempts create no database row
- [ ] Grade the latest submission and view the grade/feedback as the student
- [ ] Post announcements, discussion threads, and replies
- [ ] Verify instructor monitoring values
- [ ] Check profile and assignment pages at desktop and 390 px mobile widths

## Automated verification

The browser tests expect a fresh import of `database.sql`. Start the local server from the project root:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8765 -t .
```

Run A2 and its database assertions:

```powershell
python tests\a2_smoke.py
$env:SESSION_SAVE_PATH = [System.IO.Path]::GetTempPath()
C:\xampp\php\php.exe tests\a2_database_assertions.php
```

Re-import `database.sql`, then run the A1 regression:

```powershell
python tests\a1_smoke.py
C:\xampp\php\php.exe tests\database_assertions.php
```

Run syntax checks:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
node --check assets\js\main.js
```

## Remaining limitations

- Membership values are seeded or managed directly by an administrator in MySQL; no student-facing membership management is provided.
- Availability is simple text rather than a calendar or time-slot scheduler.
- There is no email verification, password reset, notification system, payment, subscription, priority material access, pagination, or advanced analytics.
- Test accounts use documented passwords and are not suitable for public deployment.
- Upload directories require correct Apache filesystem permissions.
- CDN assets require internet access unless they are downloaded locally.
- The app targets a single local XAMPP/MySQL installation and has no production deployment automation.
