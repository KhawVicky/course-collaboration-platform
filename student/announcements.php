<?php
declare(strict_types=1);

// Show announcements from the student's enrolled courses.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');

// Load announcements only from active enrolments.
$statement = database()->prepare(
    'SELECT an.*, c.course_code, c.title AS course_title, u.full_name AS author_name
     FROM announcements an
     JOIN courses c ON c.id = an.course_id
     JOIN users u ON u.id = an.author_id
     JOIN enrolments e
       ON e.course_id = c.id
      AND e.student_id = :student
      AND e.status = :status
     ORDER BY an.posted_at DESC'
);
$statement->execute([
    'student' => $user['id'],
    'status' => 'active',
]);
$announcements = $statement->fetchAll();

$pageTitle = 'Announcements';
$activePage = 'my-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container narrow-container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Course updates</p>
                <h1>Your announcements.</h1>
            </div>
        </div>

        <div class="content-panel">
            <?php if ($announcements === []): ?>
                <div class="empty-state">
                    <p>No announcements from your enrolled courses.</p>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <article class="announcement">
                        <span class="course-code"><?= e($announcement['course_code']) ?></span>
                        <h2><?= e($announcement['title']) ?></h2>
                        <p><?= nl2br(e($announcement['content'])) ?></p>
                        <small>
                            <?= e($announcement['author_name']) ?>
                            &middot;
                            <?= e(format_datetime($announcement['posted_at'])) ?>
                        </small>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
