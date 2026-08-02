# Course Collaboration Platform

## Fork and run on your computer

### Requirements

- Git
- XAMPP
- A GitHub account

### 1. Fork the repository

1. Open `https://github.com/KhawVicky/course-collaboration-platform`.
2. Click **Fork**.
3. Select your GitHub account.
4. Keep the repository name as `course-collaboration-platform`.
5. Click **Create fork**.

### 2. Clone your fork

Open PowerShell and run the following commands. Replace `YOUR_USERNAME` with your GitHub username.

```powershell
cd C:\xampp\htdocs
git clone https://github.com/YOUR_USERNAME/course-collaboration-platform.git
cd course-collaboration-platform
```

### 3. Start XAMPP

1. Open **XAMPP Control Panel**.
2. Start **Apache**.
3. Start **MySQL**.

### 4. Import the database

1. Open `http://localhost/phpmyadmin`.
2. Click **Import**.
3. Select `database.sql` from the project folder.
4. Click **Go** to import it.

Only `database.sql` is required for a new installation.

For an existing installation, apply `migrations/course_material_priority_access.sql` once after backing up the database.

To load the reusable material access criteria demo rows, run `migrations/material_access_criteria_dataset.sql`. It reuses the PDFs already in `uploads/materials`.

### 5. Open the website

Open this URL in your browser:

```text
http://localhost/course-collaboration-platform/
```

The application connects automatically when XAMPP uses the default MySQL settings:

```text
Host: 127.0.0.1
Port: 3306
Database: course_collaboration
Username: root
Password: empty
```
