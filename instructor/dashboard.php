<?php
declare(strict_types=1);

// Show a summary of the instructor's teaching activity.

require_once __DIR__ . '/../includes/auth.php';
$user = require_role('instructor');

$courseStatement = database()->prepare('SELECT COUNT(*) FROM courses WHERE instructor_id = :instructor_id');
$courseStatement->execute(['instructor_id' => $user['id']]);
$courseCount = (int) $courseStatement->fetchColumn();

$studentStatement = database()->prepare(
    'SELECT COUNT(DISTINCT e.student_id) FROM enrolments e
     JOIN courses c ON c.id = e.course_id
     WHERE c.instructor_id = :instructor_id AND e.status = :status'
);
$studentStatement->execute(['instructor_id' => $user['id'], 'status' => 'active']);
$studentCount = (int) $studentStatement->fetchColumn();

$submissionStatement = database()->prepare(
    'SELECT COUNT(*) FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     JOIN courses c ON c.id = a.course_id
     LEFT JOIN grades g ON g.submission_id = s.id
     WHERE c.instructor_id = :instructor_id AND s.is_latest = 1 AND g.id IS NULL'
);
$submissionStatement->execute(['instructor_id' => $user['id']]);
$pendingGrades = (int) $submissionStatement->fetchColumn();

$recentCoursesStatement = database()->prepare(
    'SELECT c.id, c.course_code, c.title, COUNT(DISTINCT e.student_id) AS student_count
     FROM courses c
     LEFT JOIN enrolments e ON e.course_id = c.id AND e.status = :status
     WHERE c.instructor_id = :instructor_id
     GROUP BY c.id, c.course_code, c.title
     ORDER BY c.created_at DESC LIMIT 4'
);
$recentCoursesStatement->execute(['status' => 'active', 'instructor_id' => $user['id']]);
$recentCourses = $recentCoursesStatement->fetchAll();

$pageTitle = 'Instructor dashboard';
$activePage = 'instructor-dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div><p class="eyebrow">Instructor workspace</p><h1>Welcome back, <?= e(explode(' ', $user['full_name'])[0]) ?>.</h1></div>
            <a class="btn btn-sun" href="<?= e(url('instructor/courses.php#create-course')) ?>">Create a course</a>
        </div>
        <div class="row g-3 stat-grid">
            <div class="col-md-4"><div class="stat-card"><span>Owned courses</span><strong><?= $courseCount ?></strong></div></div>
            <div class="col-md-4"><div class="stat-card stat-card-sun"><span>Active students</span><strong><?= $studentCount ?></strong></div></div>
            <div class="col-md-4"><div class="stat-card"><span>Awaiting grades</span><strong><?= $pendingGrades ?></strong></div></div>
        </div>
        <div class="content-panel mt-4">
            <div class="panel-heading"><div><p class="eyebrow mb-2">Teaching now</p><h2>Recent courses</h2></div><a href="<?= e(url('instructor/courses.php')) ?>">Manage courses</a></div>
            <?php if ($recentCourses === []): ?>
                <div class="empty-state"><h3>Create your first course</h3><p>Add a course code, title, and description to begin.</p></div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($recentCourses as $course): ?>
                        <div class="col-md-6"><a class="course-row" href="<?= e(url('instructor/course.php?id=' . $course['id'])) ?>"><span><?= e($course['course_code']) ?></span><strong><?= e($course['title']) ?></strong><small><?= (int) $course['student_count'] ?> enrolled students</small></a></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
