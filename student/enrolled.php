<?php
declare(strict_types=1);
// Show the courses that the current student joined.
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('student');
$statement = database()->prepare(
    'SELECT c.id, c.course_code, c.title, c.description, u.full_name AS instructor_name, e.enrolled_at
     FROM enrolments e JOIN courses c ON c.id = e.course_id JOIN users u ON u.id = c.instructor_id
     WHERE e.student_id = :student_id AND e.status = :status ORDER BY e.enrolled_at DESC'
);
$statement->execute(['student_id' => $user['id'], 'status' => 'active']);
$courses = $statement->fetchAll();
$pageTitle = 'My courses';
$activePage = 'my-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell"><div class="container"><div class="page-heading"><div><p class="eyebrow">My learning</p><h1>Your enrolled courses.</h1></div><a class="btn btn-sun" href="<?= e(url('student/courses.php')) ?>">Browse courses</a></div>
<?php if ($courses === []): ?><div class="content-panel empty-state"><h3>No enrolled courses</h3><p>Browse the catalogue to start learning.</p></div><?php else: ?><div class="row g-4"><?php foreach ($courses as $course): ?><div class="col-md-6"><a class="enrolled-card" href="<?= e(url('student/course.php?id=' . $course['id'])) ?>"><span><?= e($course['course_code']) ?></span><h2><?= e($course['title']) ?></h2><p><?= e($course['description'] ?: 'Open the course to view learning activities.') ?></p><small>With <?= e($course['instructor_name']) ?> · Enrolled <?= e(format_datetime($course['enrolled_at'])) ?></small></a></div><?php endforeach; ?></div><?php endif; ?>
</div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
