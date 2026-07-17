<?php
declare(strict_types=1);

// Post and list announcements for an owned course.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('instructor');
$courseId = positive_id($_GET['course_id'] ?? $_POST['course_id'] ?? null);

// Load the courses owned by this instructor.
$courseStatement = database()->prepare(
    'SELECT id, course_code, title
     FROM courses
     WHERE instructor_id = :owner
     ORDER BY course_code'
);
$courseStatement->execute(['owner' => $user['id']]);
$courses = $courseStatement->fetchAll();
$selectedCourse = $courseId ? owned_course($courseId, (int) $user['id']) : null;

$errors = [];
$title = '';
$content = '';

if (is_post()) {
    // Validate and save a new announcement.
    verify_csrf();
    $title = clean_text($_POST['title'] ?? '');
    $content = clean_text($_POST['content'] ?? '');

    if (!$courseId) {
        $errors['course_id'] = 'Select a course.';
    }
    if (mb_strlen($title) < 2 || mb_strlen($title) > 180) {
        $errors['title'] = 'Enter a title between 2 and 180 characters.';
    }
    if (mb_strlen($content) < 2 || mb_strlen($content) > 10000) {
        $errors['content'] = 'Enter announcement content below 10,000 characters.';
    }

    if ($errors === []) {
        $insert = database()->prepare(
            'INSERT INTO announcements (course_id, author_id, title, content)
             VALUES (:course, :author, :title, :content)'
        );
        $insert->execute([
            'course' => $courseId,
            'author' => $user['id'],
            'title' => $title,
            'content' => $content,
        ]);
        flash('success', 'Announcement posted.');
        redirect('instructor/announcements.php?course_id=' . $courseId);
    }
}

// Load announcements for the selected owned course.
$announcements = [];
if ($selectedCourse) {
    $announcementStatement = database()->prepare(
        'SELECT an.*, u.full_name AS author_name
         FROM announcements an
         JOIN users u ON u.id = an.author_id
         WHERE course_id = :course
         ORDER BY posted_at DESC'
    );
    $announcementStatement->execute(['course' => $courseId]);
    $announcements = $announcementStatement->fetchAll();
}

$pageTitle = 'Announcements';
$activePage = 'instructor-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Course updates</p>
                <h1>Announcements.</h1>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="content-panel">
                    <h2 class="form-title">Post announcement</h2>
                    <form method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="course_id">Course</label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">Select course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option
                                        value="<?= $course['id'] ?>"
                                        <?= $courseId === (int) $course['id'] ? 'selected' : '' ?>
                                    >
                                        <?= e($course['course_code'] . ' - ' . $course['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="title">Title</label>
                            <input
                                class="form-control"
                                id="title"
                                name="title"
                                value="<?= e($title) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="content">Announcement</label>
                            <textarea class="form-control" id="content" name="content" required><?= e($content) ?></textarea>
                        </div>

                        <?php if ($errors !== []): ?>
                            <div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div>
                        <?php endif; ?>

                        <button class="btn btn-ink w-100" type="submit">Post announcement</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="content-panel">
                    <div class="panel-heading">
                        <h2><?= e($selectedCourse ? $selectedCourse['course_code'] . ' updates' : 'Select a course') ?></h2>
                        <span class="count-pill"><?= count($announcements) ?></span>
                    </div>

                    <?php if ($announcements === []): ?>
                        <div class="empty-state">
                            <p>No announcements to show.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <article class="announcement">
                                <h3><?= e($announcement['title']) ?></h3>
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
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
