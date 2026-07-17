<?php
declare(strict_types=1);

// Show a summary of the student's current learning activity.

require_once __DIR__ . '/../includes/auth.php';
$user = require_role('student');

$enrolmentStatement = database()->prepare('SELECT COUNT(*) FROM enrolments WHERE student_id = :student_id AND status = :status');
$enrolmentStatement->execute(['student_id' => $user['id'], 'status' => 'active']);
$enrolledCount = (int) $enrolmentStatement->fetchColumn();

$assignmentStatement = database()->prepare(
    'SELECT COUNT(*) FROM assignments a
     JOIN enrolments e ON e.course_id = a.course_id AND e.student_id = :student_id AND e.status = :status
     WHERE a.deadline >= NOW()'
);
$assignmentStatement->execute(['student_id' => $user['id'], 'status' => 'active']);
$openAssignments = (int) $assignmentStatement->fetchColumn();

$gradeStatement = database()->prepare(
    'SELECT COUNT(*) FROM grades g
     JOIN submissions s ON s.id = g.submission_id
     WHERE s.student_id = :student_id AND s.is_latest = 1'
);
$gradeStatement->execute(['student_id' => $user['id']]);
$gradeCount = (int) $gradeStatement->fetchColumn();

$recentCoursesStatement = database()->prepare(
    'SELECT c.id, c.course_code, c.title, u.full_name AS instructor_name
     FROM enrolments e
     JOIN courses c ON c.id = e.course_id
     JOIN users u ON u.id = c.instructor_id
     WHERE e.student_id = :student_id AND e.status = :status
     ORDER BY e.enrolled_at DESC LIMIT 4'
);
$recentCoursesStatement->execute(['student_id' => $user['id'], 'status' => 'active']);
$recentCourses = $recentCoursesStatement->fetchAll();

$pageTitle = 'Student dashboard';
$activePage = 'student-dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div><p class="eyebrow">Student workspace</p><h1>Good to see you, <?= e(explode(' ', $user['full_name'])[0]) ?>.</h1></div>
            <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-ink" href="<?= e(url('student/profile.php')) ?>">Collaboration profile</a><a class="btn btn-sun" href="<?= e(url('student/courses.php')) ?>">Browse available courses</a></div>
        </div>
        <div class="row g-3 stat-grid">
            <div class="col-md-4"><div class="stat-card"><span>Enrolled courses</span><strong><?= $enrolledCount ?></strong></div></div>
            <div class="col-md-4"><div class="stat-card stat-card-sun"><span>Open assignments</span><strong><?= $openAssignments ?></strong></div></div>
            <div class="col-md-4"><div class="stat-card"><span>Graded submissions</span><strong><?= $gradeCount ?></strong></div></div>
        </div>
        <div class="content-panel mt-4">
            <div class="panel-heading"><div><p class="eyebrow mb-2">Continue learning</p><h2>Your recent courses</h2></div><a href="<?= e(url('student/enrolled.php')) ?>">View all</a></div>
            <?php if ($recentCourses === []): ?>
                <div class="empty-state"><h3>No enrolled courses yet</h3><p>Browse the catalogue and enrol when you find the right course.</p></div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($recentCourses as $course): ?>
                        <div class="col-md-6"><a class="course-row" href="<?= e(url('student/course.php?id=' . $course['id'])) ?>"><span><?= e($course['course_code']) ?></span><strong><?= e($course['title']) ?></strong><small><?= e($course['instructor_name']) ?></small></a></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
