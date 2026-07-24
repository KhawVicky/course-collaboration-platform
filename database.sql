-- Course Collaboration Platform
-- Complete A1 + A2 database schema for MySQL / MariaDB in XAMPP

CREATE DATABASE IF NOT EXISTS course_collaboration
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE course_collaboration;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS discussion_replies;
DROP TABLE IF EXISTS discussion_threads;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS submissions;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS course_materials;
DROP TABLE IF EXISTS enrolments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS student_profiles;
DROP TABLE IF EXISTS memberships;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'instructor') NOT NULL DEFAULT 'student',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE memberships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    membership_status ENUM('Member', 'Non-member') NOT NULL DEFAULT 'Non-member',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_memberships_user (user_id),
    CONSTRAINT fk_memberships_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE student_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    skills TEXT NULL,
    collaboration_mode ENUM('Online', 'Offline') NOT NULL,
    availability TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_profiles_user (user_id),
    CONSTRAINT fk_student_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instructor_id BIGINT UNSIGNED NOT NULL,
    course_code VARCHAR(30) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_courses_code (course_code),
    KEY idx_courses_instructor (instructor_id),
    CONSTRAINT fk_courses_instructor
        FOREIGN KEY (instructor_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE enrolments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active', 'withdrawn') NOT NULL DEFAULT 'active',
    enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrolments_course_student (course_id, student_id),
    KEY idx_enrolments_student (student_id),
    CONSTRAINT fk_enrolments_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_enrolments_student
        FOREIGN KEY (student_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE course_materials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_materials_course (course_id),
    KEY idx_materials_uploader (uploaded_by),
    CONSTRAINT fk_materials_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_materials_uploader
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    instructions TEXT NOT NULL,
    deadline DATETIME NOT NULL,
    max_grade DECIMAL(6,2) NOT NULL DEFAULT 100.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_assignments_course (course_id),
    KEY idx_assignments_deadline (deadline),
    KEY idx_assignments_creator (created_by),
    CONSTRAINT fk_assignments_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assignments_creator
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_assignments_max_grade CHECK (max_grade > 0)
) ENGINE=InnoDB;

CREATE TABLE submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    attempt_number INT UNSIGNED NOT NULL DEFAULT 1,
    stored_filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    student_note TEXT NULL,
    is_latest TINYINT(1) NOT NULL DEFAULT 1,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_submissions_attempt (assignment_id, student_id, attempt_number),
    KEY idx_submissions_latest (assignment_id, student_id, is_latest),
    KEY idx_submissions_student (student_id),
    CONSTRAINT fk_submissions_assignment
        FOREIGN KEY (assignment_id) REFERENCES assignments(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_submissions_student
        FOREIGN KEY (student_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_submissions_attempt CHECK (attempt_number > 0)
) ENGINE=InnoDB;

CREATE TABLE grades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id BIGINT UNSIGNED NOT NULL,
    graded_by BIGINT UNSIGNED NOT NULL,
    grade_value DECIMAL(6,2) NOT NULL,
    feedback TEXT NULL,
    graded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_grades_submission (submission_id),
    KEY idx_grades_grader (graded_by),
    CONSTRAINT fk_grades_submission
        FOREIGN KEY (submission_id) REFERENCES submissions(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_grades_grader
        FOREIGN KEY (graded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_grades_value CHECK (grade_value >= 0)
) ENGINE=InnoDB;

CREATE TABLE announcements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    content TEXT NOT NULL,
    posted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_announcements_course_date (course_id, posted_at),
    KEY idx_announcements_author (author_id),
    CONSTRAINT fk_announcements_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_announcements_author
        FOREIGN KEY (author_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE discussion_threads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_threads_course_date (course_id, created_at),
    KEY idx_threads_author (author_id),
    CONSTRAINT fk_threads_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_threads_author
        FOREIGN KEY (author_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE discussion_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_replies_thread_date (thread_id, created_at),
    KEY idx_replies_author (author_id),
    CONSTRAINT fk_replies_thread
        FOREIGN KEY (thread_id) REFERENCES discussion_threads(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_replies_author
        FOREIGN KEY (author_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Reproducible local test accounts. Change or remove these before any public deployment.
INSERT INTO users (full_name, email, password_hash, role) VALUES
    ('Aisha Student', 'student@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student'),
    ('Daniel Instructor', 'instructor@example.com', '$2y$10$IaZbNUhKyllUICsDYXovUutOM/Vc6ktwPBz/B3jyZBwl98ik4odHS', 'instructor'),
    ('Mina Student', 'student2@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student'),
    ('Nora Instructor', 'instructor2@example.com', '$2y$10$IaZbNUhKyllUICsDYXovUutOM/Vc6ktwPBz/B3jyZBwl98ik4odHS', 'instructor');

-- A2 test memberships are separate from account roles and enrolments.
INSERT INTO memberships (user_id, membership_status)
SELECT id, 'Member' FROM users WHERE email = 'student@example.com' AND role = 'student';

INSERT INTO memberships (user_id, membership_status)
SELECT id, 'Non-member' FROM users WHERE email = 'student2@example.com' AND role = 'student';

-- Collaboration profiles make the seeded student accounts useful immediately.
INSERT INTO student_profiles (user_id, skills, collaboration_mode, availability) VALUES
    (
        (SELECT id FROM users WHERE email = 'student@example.com'),
        'PHP, MySQL, interface design, documentation',
        'Online',
        'Weekdays after 7:00 PM'
    ),
    (
        (SELECT id FROM users WHERE email = 'student2@example.com'),
        'UML, software testing, requirements analysis',
        'Offline',
        'Tuesday and Thursday afternoons'
    );

-- Published demo courses owned by both seeded instructors.
INSERT INTO courses (instructor_id, course_code, title, description, is_published) VALUES
    (
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'CSDM301',
        'Software Development Methodologies',
        'Practical software processes, modelling, verification, testing, and continuous delivery.',
        1
    ),
    (
        (SELECT id FROM users WHERE email = 'instructor2@example.com'),
        'CAI402',
        'Artificial Intelligence and Decision Systems',
        'Decision-making techniques, Markov decision processes, and applied intelligent systems.',
        1
    ),
    (
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'CWEB210',
        'Collaborative Web Application Development',
        'Team-based design and implementation of secure database-driven web applications.',
        1
    );

-- Active enrolments provide course access for both student accounts.
INSERT INTO enrolments (course_id, student_id, status, enrolled_at) VALUES
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'student@example.com'),
        'active', DATE_SUB(NOW(), INTERVAL 70 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'student2@example.com'),
        'active', DATE_SUB(NOW(), INTERVAL 65 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CAI402'),
        (SELECT id FROM users WHERE email = 'student@example.com'),
        'active', DATE_SUB(NOW(), INTERVAL 50 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CWEB210'),
        (SELECT id FROM users WHERE email = 'student@example.com'),
        'active', DATE_SUB(NOW(), INTERVAL 35 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CWEB210'),
        (SELECT id FROM users WHERE email = 'student2@example.com'),
        'active', DATE_SUB(NOW(), INTERVAL 32 DAY)
    );

-- Material rows reference PDF files that are present in uploads/materials.
INSERT INTO course_materials
    (course_id, uploaded_by, title, description, stored_filename,
     original_filename, mime_type, file_size, uploaded_at)
VALUES
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Final Project Assessment Brief',
        'Requirements and deliverables for the course collaboration platform final project.',
        '01bf4e31d31e327bf7a94006565fcbbc0994cdc1.pdf',
        'Final Project Assessment Brief.pdf',
        'application/pdf', 809551, DATE_SUB(NOW(), INTERVAL 28 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Week 5: Verification and Test-driven Development',
        'Lecture material covering verification, validation, and test-driven development.',
        'Week 5 - Software Development Methodologies - V&V and Test-driven development.pdf',
        'Week 5 - Software Development Methodologies - V&V and Test-driven development.pdf',
        'application/pdf', 499255, DATE_SUB(NOW(), INTERVAL 21 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Week 6: Continuous Integration and Delivery',
        'Lecture material covering automated integration, delivery pipelines, and deployment practices.',
        'Week 6 - Software Development Methodologies - CI and Delivery.pdf',
        'Week 6 - Software Development Methodologies - CI and Delivery.pdf',
        'application/pdf', 579348, DATE_SUB(NOW(), INTERVAL 14 DAY)
    );

-- Relative deadlines keep the demo useful whenever a fresh database is imported.
INSERT INTO assignments (course_id, created_by, title, instructions, deadline, max_grade) VALUES
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Software Process Models Report',
        'Compare two software process models and recommend one for a medium-sized development team.',
        DATE_ADD(NOW(), INTERVAL 21 DAY), 100.00
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Advanced UML Portfolio',
        'Submit a portfolio containing advanced UML models and a short design rationale.',
        DATE_ADD(NOW(), INTERVAL 28 DAY), 100.00
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CAI402'),
        (SELECT id FROM users WHERE email = 'instructor2@example.com'),
        'Markov Decision Process Case Study',
        'Explain the states, actions, rewards, and policy for an applied Markov decision process.',
        DATE_ADD(NOW(), INTERVAL 18 DAY), 100.00
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CWEB210'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Collaborative Platform Prototype',
        'Build and demonstrate a secure database-driven collaboration feature as a small team.',
        DATE_ADD(NOW(), INTERVAL 35 DAY), 100.00
    );

-- Submission rows reference PDF files that are present in uploads/submissions.
INSERT INTO submissions
    (assignment_id, student_id, attempt_number, stored_filename, original_filename,
     mime_type, file_size, student_note, is_latest, submitted_at)
VALUES
    (
        (SELECT a.id FROM assignments a JOIN courses c ON c.id = a.course_id
         WHERE c.course_code = 'CSDM301' AND a.title = 'Software Process Models Report'),
        (SELECT id FROM users WHERE email = 'student@example.com'),
        1,
        'Software Development Methodologies - Software Process Models.pdf',
        'Software Development Methodologies - Software Process Models.pdf',
        'application/pdf', 1149684,
        'Initial report for review.', 0, DATE_SUB(NOW(), INTERVAL 10 DAY)
    ),
    (
        (SELECT a.id FROM assignments a JOIN courses c ON c.id = a.course_id
         WHERE c.course_code = 'CSDM301' AND a.title = 'Software Process Models Report'),
        (SELECT id FROM users WHERE email = 'student@example.com'),
        2,
        'Software Development Methodologies - Software Process Models (1).pdf',
        'Software Development Methodologies - Software Process Models - Revised.pdf',
        'application/pdf', 1149684,
        'Revised comparison and recommendation.', 1, DATE_SUB(NOW(), INTERVAL 7 DAY)
    ),
    (
        (SELECT a.id FROM assignments a JOIN courses c ON c.id = a.course_id
         WHERE c.course_code = 'CSDM301' AND a.title = 'Advanced UML Portfolio'),
        (SELECT id FROM users WHERE email = 'student2@example.com'),
        1,
        'Week 3 - Software Development Methodologies - Advanced UML - PART 1.pdf',
        'Advanced UML Portfolio - Part 1.pdf',
        'application/pdf', 1118060,
        'First portfolio draft.', 0, DATE_SUB(NOW(), INTERVAL 8 DAY)
    ),
    (
        (SELECT a.id FROM assignments a JOIN courses c ON c.id = a.course_id
         WHERE c.course_code = 'CSDM301' AND a.title = 'Advanced UML Portfolio'),
        (SELECT id FROM users WHERE email = 'student2@example.com'),
        2,
        'Week 4 - Software Development Methodologies - Advanced UML - PART 2.pdf',
        'Advanced UML Portfolio - Part 2.pdf',
        'application/pdf', 1417299,
        'Updated portfolio with behavioural models.', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)
    ),
    (
        (SELECT a.id FROM assignments a JOIN courses c ON c.id = a.course_id
         WHERE c.course_code = 'CAI402' AND a.title = 'Markov Decision Process Case Study'),
        (SELECT id FROM users WHERE email = 'student@example.com'),
        1,
        'Topic4_MarkovDecisionProcess_June2026.pdf',
        'Markov Decision Process Case Study.pdf',
        'application/pdf', 1050272,
        'Case study with policy evaluation examples.', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)
    );

-- Grades are attached only to the latest attempts.
INSERT INTO grades (submission_id, graded_by, grade_value, feedback, graded_at) VALUES
    (
        (SELECT s.id FROM submissions s
         JOIN assignments a ON a.id = s.assignment_id
         JOIN courses c ON c.id = a.course_id
         JOIN users u ON u.id = s.student_id
         WHERE c.course_code = 'CSDM301'
           AND a.title = 'Software Process Models Report'
           AND u.email = 'student@example.com'
           AND s.is_latest = 1),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        88.00,
        'Clear comparison with a well-supported recommendation. Improve the risk discussion.',
        DATE_SUB(NOW(), INTERVAL 4 DAY)
    ),
    (
        (SELECT s.id FROM submissions s
         JOIN assignments a ON a.id = s.assignment_id
         JOIN courses c ON c.id = a.course_id
         JOIN users u ON u.id = s.student_id
         WHERE c.course_code = 'CSDM301'
           AND a.title = 'Advanced UML Portfolio'
           AND u.email = 'student2@example.com'
           AND s.is_latest = 1),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        82.00,
        'Good model coverage and notation. Add more explanation for the sequence diagram decisions.',
        DATE_SUB(NOW(), INTERVAL 2 DAY)
    );

INSERT INTO announcements (course_id, author_id, title, content, posted_at) VALUES
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Final project brief available',
        'Please review the assessment brief and bring requirement questions to the next class.',
        DATE_SUB(NOW(), INTERVAL 20 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Continuous delivery workshop',
        'The next lab focuses on building a simple continuous integration and delivery pipeline.',
        DATE_SUB(NOW(), INTERVAL 6 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CAI402'),
        (SELECT id FROM users WHERE email = 'instructor2@example.com'),
        'MDP case study consultation',
        'An online consultation will be held this Friday for questions about policies and rewards.',
        DATE_SUB(NOW(), INTERVAL 4 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CWEB210'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Prototype teams confirmed',
        'Check your collaboration profile and contact your teammates before the first sprint.',
        DATE_SUB(NOW(), INTERVAL 3 DAY)
    );

INSERT INTO discussion_threads (course_id, author_id, title, content, created_at) VALUES
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'student@example.com'),
        'Choosing a process model',
        'What factors should we prioritise when choosing between iterative and plan-driven models?',
        DATE_SUB(NOW(), INTERVAL 12 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CSDM301'),
        (SELECT id FROM users WHERE email = 'student2@example.com'),
        'UML portfolio scope',
        'Should the portfolio include both structural and behavioural diagrams?',
        DATE_SUB(NOW(), INTERVAL 7 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CAI402'),
        (SELECT id FROM users WHERE email = 'instructor2@example.com'),
        'Reward design examples',
        'Share an example where a poorly designed reward leads to unintended agent behaviour.',
        DATE_SUB(NOW(), INTERVAL 5 DAY)
    ),
    (
        (SELECT id FROM courses WHERE course_code = 'CWEB210'),
        (SELECT id FROM users WHERE email = 'student2@example.com'),
        'Team availability',
        'Please share your preferred meeting time for the prototype sprint.',
        DATE_SUB(NOW(), INTERVAL 2 DAY)
    );

INSERT INTO discussion_replies (thread_id, author_id, content, created_at) VALUES
    (
        (SELECT dt.id FROM discussion_threads dt JOIN courses c ON c.id = dt.course_id
         WHERE c.course_code = 'CSDM301' AND dt.title = 'Choosing a process model'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Consider requirement stability, delivery frequency, team experience, and stakeholder availability.',
        DATE_SUB(NOW(), INTERVAL 11 DAY)
    ),
    (
        (SELECT dt.id FROM discussion_threads dt JOIN courses c ON c.id = dt.course_id
         WHERE c.course_code = 'CSDM301' AND dt.title = 'Choosing a process model'),
        (SELECT id FROM users WHERE email = 'student2@example.com'),
        'Risk and compliance requirements may also affect how much planning is needed.',
        DATE_SUB(NOW(), INTERVAL 10 DAY)
    ),
    (
        (SELECT dt.id FROM discussion_threads dt JOIN courses c ON c.id = dt.course_id
         WHERE c.course_code = 'CSDM301' AND dt.title = 'UML portfolio scope'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Yes. Include at least one structural model and two behavioural models with rationale.',
        DATE_SUB(NOW(), INTERVAL 6 DAY)
    ),
    (
        (SELECT dt.id FROM discussion_threads dt JOIN courses c ON c.id = dt.course_id
         WHERE c.course_code = 'CAI402' AND dt.title = 'Reward design examples'),
        (SELECT id FROM users WHERE email = 'student@example.com'),
        'A delivery agent rewarded only for speed might ignore safety or damage constraints.',
        DATE_SUB(NOW(), INTERVAL 4 DAY)
    ),
    (
        (SELECT dt.id FROM discussion_threads dt JOIN courses c ON c.id = dt.course_id
         WHERE c.course_code = 'CWEB210' AND dt.title = 'Team availability'),
        (SELECT id FROM users WHERE email = 'student@example.com'),
        'I am available online on weekday evenings after 7:00 PM.',
        DATE_SUB(NOW(), INTERVAL 1 DAY)
    ),
    (
        (SELECT dt.id FROM discussion_threads dt JOIN courses c ON c.id = dt.course_id
         WHERE c.course_code = 'CWEB210' AND dt.title = 'Team availability'),
        (SELECT id FROM users WHERE email = 'instructor@example.com'),
        'Use the profile page to keep your availability visible to the team.',
        DATE_SUB(NOW(), INTERVAL 12 HOUR)
    );


-- Extended demo dataset keeps all main feature lists above ten records.
START TRANSACTION;

-- Add reusable student demo accounts. They use the same Student123! password hash as the main student account.
INSERT IGNORE INTO users (full_name, email, password_hash, role) VALUES
    ('Ethan Lim', 'demo.student01@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student'),
    ('Farah Nordin', 'demo.student02@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student'),
    ('Grace Tan', 'demo.student03@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student'),
    ('Haris Ahmad', 'demo.student04@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student'),
    ('Imani Lee', 'demo.student05@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student'),
    ('Jason Wong', 'demo.student06@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student'),
    ('Kavya Menon', 'demo.student07@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student'),
    ('Lucas Ng', 'demo.student08@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student'),
    ('Mei Chen', 'demo.student09@example.com', '$2y$10$lVJqVumQAMMoGA2g9UeZ5eFOzIQCLmqULh2X8Gcf2dPwLvkWQuWTC', 'student');

-- Give every demo student a membership and collaboration profile.
INSERT INTO memberships (user_id, membership_status)
SELECT id, IF(MOD(id, 2) = 0, 'Member', 'Non-member')
FROM users
WHERE email LIKE 'demo.student%@example.com'
ON DUPLICATE KEY UPDATE membership_status = VALUES(membership_status);

INSERT INTO student_profiles (user_id, skills, collaboration_mode, availability)
SELECT
    id,
    'PHP, MySQL, testing, documentation, teamwork',
    IF(MOD(id, 2) = 0, 'Online', 'Offline'),
    'Weekdays after 6:00 PM and Saturday mornings'
FROM users
WHERE email LIKE 'demo.student%@example.com'
ON DUPLICATE KEY UPDATE
    skills = VALUES(skills),
    collaboration_mode = VALUES(collaboration_mode),
    availability = VALUES(availability);

-- Extend Daniel Instructor's owned-course list beyond ten courses.
INSERT IGNORE INTO courses (instructor_id, course_code, title, description, is_published) VALUES
    ((SELECT id FROM users WHERE email = 'instructor@example.com'), 'CUX201', 'User Experience Foundations', 'Research, prototyping, usability evaluation, and accessible interface design.', 1),
    ((SELECT id FROM users WHERE email = 'instructor@example.com'), 'CDB220', 'Database Systems', 'Relational modelling, SQL, transactions, indexing, and database administration.', 1),
    ((SELECT id FROM users WHERE email = 'instructor@example.com'), 'CSEC240', 'Web Application Security', 'Secure coding, authentication, authorization, validation, and common web threats.', 1),
    ((SELECT id FROM users WHERE email = 'instructor@example.com'), 'CQA260', 'Software Quality Assurance', 'Quality planning, reviews, testing strategy, metrics, and continuous improvement.', 1),
    ((SELECT id FROM users WHERE email = 'instructor@example.com'), 'CDEV280', 'DevOps Fundamentals', 'Version control, automation, continuous integration, delivery, and observability.', 1),
    ((SELECT id FROM users WHERE email = 'instructor@example.com'), 'CREQ300', 'Requirements Engineering', 'Stakeholder analysis, elicitation, specification, validation, and change control.', 1),
    ((SELECT id FROM users WHERE email = 'instructor@example.com'), 'CARCH320', 'Software Architecture', 'Architectural drivers, patterns, quality attributes, and technical decisions.', 1),
    ((SELECT id FROM users WHERE email = 'instructor@example.com'), 'CDATA340', 'Applied Data Analytics', 'Data preparation, exploratory analysis, visualisation, and responsible interpretation.', 1),
    ((SELECT id FROM users WHERE email = 'instructor@example.com'), 'CPRO360', 'Software Project Management', 'Planning, estimation, risk management, communication, and project delivery.', 1);

-- Enrol all demo students in CSDM301 so monitoring contains more than ten students.
INSERT IGNORE INTO enrolments (course_id, student_id, status, enrolled_at)
SELECT c.id, u.id, 'active', TIMESTAMPADD(DAY, -45, NOW())
FROM courses c
JOIN users u ON u.email LIKE 'demo.student%@example.com'
WHERE c.course_code = 'CSDM301';

-- Reuse one existing PDF for additional material records without copying the file.
INSERT INTO course_materials
    (course_id, uploaded_by, title, description, stored_filename,
     original_filename, mime_type, file_size, uploaded_at)
SELECT
    c.id,
    instructor.id,
    demo.title,
    demo.description,
    '01bf4e31d31e327bf7a94006565fcbbc0994cdc1.pdf',
    'Final Project Assessment Brief.pdf',
    'application/pdf',
    809551,
    TIMESTAMPADD(DAY, -demo.days_ago, NOW())
FROM courses c
JOIN users instructor ON instructor.email = 'instructor@example.com'
JOIN (
    SELECT 1 AS sequence_no, 26 AS days_ago, 'Week 1: Development Life Cycles' AS title, 'Overview of predictive, iterative, incremental, and agile life cycles.' AS description
    UNION ALL SELECT 2, 24, 'Week 2: Requirements and Scope', 'Techniques for defining scope and documenting useful requirements.'
    UNION ALL SELECT 3, 22, 'Week 3: Modelling Workshop', 'Worked examples for structural and behavioural software models.'
    UNION ALL SELECT 4, 20, 'Week 4: Architecture Decisions', 'Guidance for documenting architecture drivers and important trade-offs.'
    UNION ALL SELECT 5, 18, 'Week 7: Quality Planning', 'Quality goals, review activities, test levels, and acceptance criteria.'
    UNION ALL SELECT 6, 16, 'Week 8: Risk Management', 'A practical approach to identifying, analysing, and treating project risks.'
    UNION ALL SELECT 7, 14, 'Week 9: Team Collaboration', 'Communication practices for healthy and productive software teams.'
    UNION ALL SELECT 8, 12, 'Week 10: Release Readiness', 'A checklist for deployment preparation, handover, and support planning.'
) demo
WHERE c.course_code = 'CSDM301'
  AND NOT EXISTS (
      SELECT 1 FROM course_materials existing
      WHERE existing.course_id = c.id AND existing.title = demo.title
  );

-- Add nine assessments so CSDM301 contains eleven assignments in a fresh import.
INSERT INTO assignments (course_id, created_by, title, instructions, deadline, max_grade)
SELECT
    c.id,
    instructor.id,
    demo.title,
    demo.instructions,
    TIMESTAMPADD(DAY, demo.due_in_days, NOW()),
    100.00
FROM courses c
JOIN users instructor ON instructor.email = 'instructor@example.com'
JOIN (
    SELECT 10 AS due_in_days, 'Requirements Elicitation Review' AS title, 'Review an elicitation session and recommend three improvements.' AS instructions
    UNION ALL SELECT 13, 'Sprint Planning Exercise', 'Prepare a sprint goal, selected backlog, estimates, and team capacity summary.'
    UNION ALL SELECT 16, 'Risk Register Analysis', 'Create and analyse a risk register for a medium-sized software project.'
    UNION ALL SELECT 19, 'Architecture Decision Record', 'Write an architecture decision record with context, options, and consequences.'
    UNION ALL SELECT 22, 'Test Strategy Proposal', 'Propose a balanced test strategy covering unit, integration, system, and acceptance testing.'
    UNION ALL SELECT 25, 'CI Pipeline Reflection', 'Evaluate a continuous integration pipeline and recommend practical improvements.'
    UNION ALL SELECT 28, 'Code Review Portfolio', 'Submit examples of code review findings and explain the value of each recommendation.'
    UNION ALL SELECT 31, 'Deployment Readiness Report', 'Assess operational, security, data, and support readiness for a planned release.'
    UNION ALL SELECT 34, 'Team Retrospective', 'Reflect on team communication, delivery outcomes, and two actions for the next iteration.'
) demo
WHERE c.course_code = 'CSDM301'
  AND NOT EXISTS (
      SELECT 1 FROM assignments existing
      WHERE existing.course_id = c.id AND existing.title = demo.title
  );

-- Reuse one existing submission PDF for nine different demo submissions.
INSERT IGNORE INTO submissions
    (assignment_id, student_id, attempt_number, stored_filename, original_filename,
     mime_type, file_size, student_note, is_latest, submitted_at)
SELECT
    assignment.id,
    student.id,
    1,
    'Software Development Methodologies - Software Process Models.pdf',
    CONCAT(demo.title, ' - ', student.full_name, '.pdf'),
    'application/pdf',
    1149684,
    'Demo submission prepared for instructor review.',
    1,
    TIMESTAMPADD(DAY, -demo.days_ago, NOW())
FROM courses c
JOIN assignments assignment ON assignment.course_id = c.id
JOIN (
    SELECT 1 AS days_ago, 'Requirements Elicitation Review' AS title, 'demo.student01@example.com' AS student_email
    UNION ALL SELECT 2, 'Sprint Planning Exercise', 'demo.student02@example.com'
    UNION ALL SELECT 3, 'Risk Register Analysis', 'demo.student03@example.com'
    UNION ALL SELECT 4, 'Architecture Decision Record', 'demo.student04@example.com'
    UNION ALL SELECT 5, 'Test Strategy Proposal', 'demo.student05@example.com'
    UNION ALL SELECT 6, 'CI Pipeline Reflection', 'demo.student06@example.com'
    UNION ALL SELECT 7, 'Code Review Portfolio', 'demo.student07@example.com'
    UNION ALL SELECT 8, 'Deployment Readiness Report', 'demo.student08@example.com'
    UNION ALL SELECT 9, 'Team Retrospective', 'demo.student09@example.com'
) demo ON demo.title = assignment.title
JOIN users student ON student.email = demo.student_email
WHERE c.course_code = 'CSDM301';

-- Grade every new latest submission with simple feedback.
INSERT INTO grades (submission_id, graded_by, grade_value, feedback, graded_at)
SELECT
    submission.id,
    instructor.id,
    78.00 + demo.grade_offset,
    demo.feedback,
    TIMESTAMPADD(HOUR, 12, submission.submitted_at)
FROM submissions submission
JOIN assignments assignment ON assignment.id = submission.assignment_id
JOIN courses c ON c.id = assignment.course_id
JOIN users student ON student.id = submission.student_id
JOIN users instructor ON instructor.email = 'instructor@example.com'
JOIN (
    SELECT 'demo.student01@example.com' AS student_email, 'Requirements Elicitation Review' AS title, 1 AS grade_offset, 'Clear observations and practical recommendations.' AS feedback
    UNION ALL SELECT 'demo.student02@example.com', 'Sprint Planning Exercise', 2, 'Well-scoped sprint goal and realistic capacity planning.'
    UNION ALL SELECT 'demo.student03@example.com', 'Risk Register Analysis', 3, 'Good prioritisation with useful mitigation actions.'
    UNION ALL SELECT 'demo.student04@example.com', 'Architecture Decision Record', 4, 'Strong comparison of options and consequences.'
    UNION ALL SELECT 'demo.student05@example.com', 'Test Strategy Proposal', 5, 'Balanced test coverage with clear responsibilities.'
    UNION ALL SELECT 'demo.student06@example.com', 'CI Pipeline Reflection', 6, 'Thoughtful analysis of feedback speed and reliability.'
    UNION ALL SELECT 'demo.student07@example.com', 'Code Review Portfolio', 7, 'Relevant findings supported by clear explanations.'
    UNION ALL SELECT 'demo.student08@example.com', 'Deployment Readiness Report', 8, 'Comprehensive readiness checks and ownership details.'
    UNION ALL SELECT 'demo.student09@example.com', 'Team Retrospective', 9, 'Honest reflection with specific improvement actions.'
) demo ON demo.student_email = student.email AND demo.title = assignment.title
LEFT JOIN grades existing ON existing.submission_id = submission.id
WHERE c.course_code = 'CSDM301'
  AND submission.is_latest = 1
  AND existing.id IS NULL;

-- Add course updates for announcement list testing.
INSERT INTO announcements (course_id, author_id, title, content, posted_at)
SELECT
    c.id,
    instructor.id,
    demo.title,
    demo.content,
    TIMESTAMPADD(DAY, -demo.days_ago, NOW())
FROM courses c
JOIN users instructor ON instructor.email = 'instructor@example.com'
JOIN (
    SELECT 1 AS days_ago, 'Week 1 learning checklist' AS title, 'Complete the introductory reading and confirm your development environment is ready.' AS content
    UNION ALL SELECT 2, 'Requirements workshop preparation', 'Bring one example stakeholder question and one possible acceptance criterion.'
    UNION ALL SELECT 3, 'Modelling lab reminder', 'The modelling lab begins with a short diagram review before the practical exercise.'
    UNION ALL SELECT 4, 'Architecture clinic available', 'Use the consultation slot to discuss architecture drivers and trade-offs.'
    UNION ALL SELECT 5, 'Testing demonstration materials', 'The testing examples and supporting brief are now available in course materials.'
    UNION ALL SELECT 6, 'Risk review activity', 'Update your project risk register before the next tutorial.'
    UNION ALL SELECT 7, 'Code review pairing', 'Pairing groups have been published for the code review activity.'
    UNION ALL SELECT 8, 'Release checklist discussion', 'Post one release-readiness question in the course discussion area.'
    UNION ALL SELECT 9, 'Final consultation schedule', 'The final consultation schedule is available. Please prepare focused questions.'
) demo
WHERE c.course_code = 'CSDM301'
  AND NOT EXISTS (
      SELECT 1 FROM announcements existing
      WHERE existing.course_id = c.id AND existing.title = demo.title
  );

-- Add discussion topics so five-per-page navigation has multiple pages.
INSERT INTO discussion_threads (course_id, author_id, title, content, created_at)
SELECT
    c.id,
    student.id,
    demo.title,
    demo.content,
    TIMESTAMPADD(DAY, -demo.days_ago, NOW())
FROM courses c
JOIN (
    SELECT 1 AS days_ago, 'demo.student01@example.com' AS student_email, 'Best elicitation question' AS title, 'Which open question has helped you discover an important hidden requirement?' AS content
    UNION ALL SELECT 2, 'demo.student02@example.com', 'Sprint goal examples', 'What makes a sprint goal specific enough to guide daily decisions?'
    UNION ALL SELECT 3, 'demo.student03@example.com', 'Risk priority discussion', 'Should probability or impact carry more weight when project information is limited?'
    UNION ALL SELECT 4, 'demo.student04@example.com', 'Architecture trade-offs', 'How do you explain a technical trade-off clearly to a non-technical stakeholder?'
    UNION ALL SELECT 5, 'demo.student05@example.com', 'Testing balance', 'How would you divide effort across unit, integration, and system testing?'
    UNION ALL SELECT 6, 'demo.student06@example.com', 'Fast pipeline feedback', 'Which pipeline stage should run first to give developers useful feedback quickly?'
    UNION ALL SELECT 7, 'demo.student07@example.com', 'Constructive code reviews', 'What wording keeps a code review comment clear and respectful?'
    UNION ALL SELECT 8, 'demo.student08@example.com', 'Release readiness evidence', 'What evidence should a team collect before approving a production release?'
    UNION ALL SELECT 9, 'demo.student09@example.com', 'Retrospective actions', 'How can a team make sure retrospective actions are completed in the next sprint?'
) demo
JOIN users student ON student.email = demo.student_email
WHERE c.course_code = 'CSDM301'
  AND NOT EXISTS (
      SELECT 1 FROM discussion_threads existing
      WHERE existing.course_id = c.id AND existing.title = demo.title
  );

-- Add one useful reply to every new discussion topic.
INSERT INTO discussion_replies (thread_id, author_id, content, created_at)
SELECT
    thread.id,
    instructor.id,
    demo.reply_content,
    TIMESTAMPADD(HOUR, 6, thread.created_at)
FROM discussion_threads thread
JOIN courses c ON c.id = thread.course_id
JOIN users instructor ON instructor.email = 'instructor@example.com'
JOIN (
    SELECT 'Best elicitation question' AS title, 'Ask about the last time the current process failed and what the user needed at that moment.' AS reply_content
    UNION ALL SELECT 'Sprint goal examples', 'A useful sprint goal describes the intended outcome without turning into a list of every task.'
    UNION ALL SELECT 'Risk priority discussion', 'Use both values, then discuss uncertainty and urgency before choosing the response.'
    UNION ALL SELECT 'Architecture trade-offs', 'Connect each option to cost, delivery time, reliability, and future change.'
    UNION ALL SELECT 'Testing balance', 'Base the balance on product risk, change frequency, and the cost of late failure.'
    UNION ALL SELECT 'Fast pipeline feedback', 'Run fast static checks and focused unit tests before slower integration suites.'
    UNION ALL SELECT 'Constructive code reviews', 'Describe the observed issue, its impact, and one possible improvement without judging the author.'
    UNION ALL SELECT 'Release readiness evidence', 'Include passing tests, security review, rollback steps, monitoring, ownership, and stakeholder approval.'
    UNION ALL SELECT 'Retrospective actions', 'Assign one owner and due date, then review the action during the next planning session.'
) demo ON demo.title = thread.title
WHERE c.course_code = 'CSDM301'
  AND NOT EXISTS (
      SELECT 1 FROM discussion_replies existing
      WHERE existing.thread_id = thread.id AND existing.content = demo.reply_content
  );

COMMIT;
