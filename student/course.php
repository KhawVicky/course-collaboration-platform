<?php
declare(strict_types=1);

// Show course details and enrolled course content.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$courseId = positive_id($_GET['id'] ?? null) ?? not_found('Invalid course ID.');

// Load one published course and its instructor.
$courseStatement = database()->prepare(
    'SELECT c.*, u.full_name AS instructor_name
     FROM courses c
     JOIN users u ON u.id = c.instructor_id
     WHERE c.id = :course_id AND c.is_published = 1
     LIMIT 1'
);
$courseStatement->execute(['course_id' => $courseId]);
$course = $courseStatement->fetch() ?: not_found('Course not found.');

// Check whether the current student is already enrolled.
$enrolmentStatement = database()->prepare(
    'SELECT id, status, enrolled_at
     FROM enrolments
     WHERE course_id = :course_id AND student_id = :student_id
     LIMIT 1'
);
$enrolmentStatement->execute([
    'course_id' => $courseId,
    'student_id' => $user['id'],
]);
$enrolment = $enrolmentStatement->fetch();
$isEnrolled = $enrolment && $enrolment['status'] === 'active';

if (is_post()) {
    // Create a new enrolment or reactivate an old enrolment.
    verify_csrf();

    try {
        if ($enrolment) {
            $update = database()->prepare(
                'UPDATE enrolments
                 SET status = :status, enrolled_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $update->execute([
                'status' => 'active',
                'id' => $enrolment['id'],
            ]);
        } else {
            $insert = database()->prepare(
                'INSERT INTO enrolments (course_id, student_id)
                 VALUES (:course_id, :student_id)'
            );
            $insert->execute([
                'course_id' => $courseId,
                'student_id' => $user['id'],
            ]);
        }

        flash('success', 'You are now enrolled in ' . $course['course_code'] . '.');
    } catch (PDOException $exception) {
        if ($exception->getCode() !== '23000') {
            throw $exception;
        }
        flash('info', 'You are already enrolled in this course.');
    }

    redirect('student/course.php?id=' . $courseId);
}

$materials = [];
$assignments = [];
$announcements = [];

if ($isEnrolled) {
    // Load protected materials for this enrolled student.
    $materialStatement = database()->prepare(
        'SELECT *
         FROM course_materials
         WHERE course_id = :course_id
         ORDER BY uploaded_at DESC'
    );
    $materialStatement->execute(['course_id' => $courseId]);
    $materials = $materialStatement->fetchAll();

    // Load assignments with this student's attempt totals.
    $assignmentStatement = database()->prepare(
        'SELECT a.*,
            (
                SELECT COUNT(*)
                FROM submissions sub
                WHERE sub.assignment_id = a.id AND sub.student_id = :student_id
            ) AS attempt_count
         FROM assignments a
         WHERE a.course_id = :course_id
         ORDER BY a.deadline'
    );
    $assignmentStatement->execute([
        'student_id' => $user['id'],
        'course_id' => $courseId,
    ]);
    $assignments = $assignmentStatement->fetchAll();

    // Load the five newest course announcements.
    $announcementStatement = database()->prepare(
        'SELECT an.*, u.full_name AS author_name
         FROM announcements an
         JOIN users u ON u.id = an.author_id
         WHERE an.course_id = :course_id
         ORDER BY an.posted_at DESC
         LIMIT 5'
    );
    $announcementStatement->execute(['course_id' => $courseId]);
    $announcements = $announcementStatement->fetchAll();
}

$pageTitle = $course['course_code'];
$activePage = $isEnrolled ? 'my-courses' : 'browse-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="course-hero">
            <span><?= e($course['course_code']) ?></span>
            <h1><?= e($course['title']) ?></h1>
            <p><?= e($course['description'] ?: 'No course description has been added.') ?></p>
            <small>Instructor &middot; <?= e($course['instructor_name']) ?></small>

            <?php if (!$isEnrolled): ?>
                <form method="post" class="mt-4">
                    <?= csrf_field() ?>
                    <button class="btn btn-sun" type="submit">Enrol in this course</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($isEnrolled): ?>
            <div class="action-grid student-actions">
                <a href="#materials"><span>01</span> Materials</a>
                <a href="#assignments"><span>02</span> Assignments</a>
                <a href="#announcements"><span>03</span> Announcements</a>
                <a href="<?= e(url('discussions.php?course_id=' . $courseId)) ?>">
                    <span>04</span> Discussions
                </a>
            </div>

            <div class="content-panel mt-4" id="materials">
                <div class="panel-heading">
                    <h2>Course materials</h2>
                    <span class="count-pill"><?= count($materials) ?></span>
                </div>

                <?php if ($materials === []): ?>
                    <p class="text-muted">No materials uploaded yet.</p>
                <?php else: ?>
                    <div class="resource-list">
                        <?php foreach ($materials as $material): ?>
                            <a href="<?= e(url('download.php?type=material&id=' . $material['id'])) ?>">
                                <div>
                                    <strong><?= e($material['title']) ?></strong>
                                    <small>
                                        <?= e($material['description'] ?: $material['original_filename']) ?>
                                    </small>
                                </div>
                                <span>Download</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="content-panel mt-4" id="assignments">
                <div class="panel-heading">
                    <h2>Assignments</h2>
                    <span class="count-pill"><?= count($assignments) ?></span>
                </div>

                <?php if ($assignments === []): ?>
                    <p class="text-muted">No assignments created yet.</p>
                <?php else: ?>
                    <div class="assignment-list">
                        <?php foreach ($assignments as $assignment): ?>
                            <a href="<?= e(url('student/assignment.php?id=' . $assignment['id'])) ?>">
                                <div>
                                    <strong><?= e($assignment['title']) ?></strong>
                                    <small>
                                        Due <?= e(format_datetime($assignment['deadline'])) ?>
                                        &middot; <?= e($assignment['max_grade']) ?> marks
                                    </small>
                                </div>
                                <span>
                                    <?= (int) $assignment['attempt_count'] > 0
                                        ? (int) $assignment['attempt_count'] . ' attempt(s)'
                                        : 'Not submitted' ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="content-panel mt-4" id="announcements">
                <div class="panel-heading">
                    <h2>Announcements</h2>
                    <a href="<?= e(url('student/announcements.php')) ?>">View all</a>
                </div>

                <?php if ($announcements === []): ?>
                    <p class="text-muted">No announcements yet.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <article class="announcement">
                            <h3><?= e($announcement['title']) ?></h3>
                            <p><?= nl2br(e($announcement['content'])) ?></p>
                            <small>
                                <?= e($announcement['author_name']) ?>
                                &middot; <?= e(format_datetime($announcement['posted_at'])) ?>
                            </small>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="content-panel mt-4">
                <div class="empty-state">
                    <h3>Enrol to access course content</h3>
                    <p>
                        Materials, assignments, announcements, and discussions are available after enrolment.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
