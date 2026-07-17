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
