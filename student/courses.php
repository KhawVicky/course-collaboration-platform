<?php
declare(strict_types=1);

// Show all published courses and the student's enrolment state.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');

// Load published courses and mark active enrolments.
$courseStatement = database()->prepare(
    'SELECT c.id, c.course_code, c.title, c.description,
            u.full_name AS instructor_name,
            CASE
                WHEN e.id IS NULL OR e.status <> :active_status THEN 0
                ELSE 1
            END AS is_enrolled
     FROM courses c
     JOIN users u ON u.id = c.instructor_id
     LEFT JOIN enrolments e
       ON e.course_id = c.id
      AND e.student_id = :student_id
     WHERE c.is_published = 1
     ORDER BY c.created_at DESC'
);
$courseStatement->execute([
    'active_status' => 'active',
    'student_id' => $user['id'],
]);
$courses = $courseStatement->fetchAll();

$pageTitle = 'Browse courses';
$activePage = 'browse-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Course catalogue</p>
                <h1>Find your next course.</h1>
            </div>
            <a class="btn btn-outline-ink" href="<?= e(url('student/enrolled.php')) ?>">
                View enrolled courses
            </a>
        </div>

        <?php if ($courses === []): ?>
            <div class="content-panel empty-state">
                <h3>No courses available</h3>
                <p>Published courses will appear here.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="catalog-card h-100">
                            <span class="course-code"><?= e($course['course_code']) ?></span>
                            <h2><?= e($course['title']) ?></h2>
                            <p>
                                <?= e(
                                    $course['description']
                                    ?: 'Explore this course and its learning activities.'
                                ) ?>
                            </p>
                            <small>
                                Instructor &middot; <?= e($course['instructor_name']) ?>
                            </small>
                            <a
                                class="btn <?= (int) $course['is_enrolled'] === 1
                                    ? 'btn-outline-ink'
                                    : 'btn-ink' ?> mt-4"
                                href="<?= e(url('student/course.php?id=' . $course['id'])) ?>"
                            >
                                <?= (int) $course['is_enrolled'] === 1
                                    ? 'Open course'
                                    : 'View details' ?>
                            </a>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
