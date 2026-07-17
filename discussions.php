<?php
declare(strict_types=1);

// Let authorised course members create threads and replies.
require_once __DIR__ . '/includes/auth.php';

$user = require_login();
$courseId = positive_id($_GET['course_id'] ?? $_POST['course_id'] ?? null)
    ?? not_found('Invalid course ID.');
require_course_access($courseId, $user);

// Load the course after the access check succeeds.
$courseStatement = database()->prepare(
    'SELECT c.*, u.full_name AS instructor_name
     FROM courses c
     JOIN users u ON u.id = c.instructor_id
     WHERE c.id = :course'
);
$courseStatement->execute(['course' => $courseId]);
$course = $courseStatement->fetch() ?: not_found('Course not found.');
$errors = [];

if (is_post()) {
    // Validate the shared discussion message.
    verify_csrf();
    $action = clean_text($_POST['action'] ?? '');
    $content = clean_text($_POST['content'] ?? '');

    if (mb_strlen($content) < 2 || mb_strlen($content) > 10000) {
        $errors['content'] = 'Enter 2-10,000 characters.';
    }

    if ($action === 'thread') {
        // Create a new course discussion thread.
        $title = clean_text($_POST['title'] ?? '');
        if (mb_strlen($title) < 2 || mb_strlen($title) > 180) {
            $errors['title'] = 'Enter a thread title.';
        }

        if ($errors === []) {
            $insert = database()->prepare(
                'INSERT INTO discussion_threads (course_id, author_id, title, content)
                 VALUES (:course, :author, :title, :content)'
            );
            $insert->execute([
                'course' => $courseId,
                'author' => $user['id'],
                'title' => $title,
                'content' => $content,
            ]);
            $threadId = (int) database()->lastInsertId();
            flash('success', 'Discussion thread posted.');
            redirect('discussions.php?course_id=' . $courseId . '#thread-' . $threadId);
        }
    } elseif ($action === 'reply') {
        // Confirm the thread belongs to this course before replying.
        $threadId = positive_id($_POST['thread_id'] ?? null);
        if (!$threadId) {
            $errors['thread_id'] = 'Invalid thread.';
        }

        if ($errors === []) {
            $threadCheck = database()->prepare(
                'SELECT COUNT(*)
                 FROM discussion_threads
                 WHERE id = :thread AND course_id = :course'
            );
            $threadCheck->execute([
                'thread' => $threadId,
                'course' => $courseId,
            ]);

            if (!(int) $threadCheck->fetchColumn()) {
                not_found('Thread not found.');
            }

            $insert = database()->prepare(
                'INSERT INTO discussion_replies (thread_id, author_id, content)
                 VALUES (:thread, :author, :content)'
            );
            $insert->execute([
                'thread' => $threadId,
                'author' => $user['id'],
                'content' => $content,
            ]);
            flash('success', 'Reply posted.');
            redirect('discussions.php?course_id=' . $courseId . '#thread-' . $threadId);
        }
    }
}

// Load all threads and their replies for display.
$threadStatement = database()->prepare(
    'SELECT t.*, u.full_name AS author_name, u.role AS author_role
     FROM discussion_threads t
     JOIN users u ON u.id = t.author_id
     WHERE t.course_id = :course
     ORDER BY t.created_at DESC'
);
$threadStatement->execute(['course' => $courseId]);
$threads = $threadStatement->fetchAll();

$replyStatement = database()->prepare(
    'SELECT r.*, u.full_name AS author_name, u.role AS author_role
     FROM discussion_replies r
     JOIN users u ON u.id = r.author_id
     WHERE r.thread_id = :thread
     ORDER BY r.created_at'
);
foreach ($threads as &$thread) {
    $replyStatement->execute(['thread' => $thread['id']]);
    $thread['replies'] = $replyStatement->fetchAll();
}
unset($thread);

$coursePath = $user['role'] === 'student' ? 'student' : 'instructor';
$pageTitle = 'Course discussions';
$activePage = $user['role'] === 'student' ? 'my-courses' : 'instructor-courses';
require __DIR__ . '/includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow"><?= e($course['course_code']) ?></p>
                <h1>Course discussions.</h1>
            </div>
            <a
                class="btn btn-outline-ink"
                href="<?= e(url($coursePath . '/course.php?id=' . $courseId)) ?>"
            >Back to course</a>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="content-panel sticky-lg-top app-sticky">
                    <h2 class="form-title">Start a thread</h2>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="course_id" value="<?= $courseId ?>">
                        <input type="hidden" name="action" value="thread">

                        <div class="mb-3">
                            <label class="form-label" for="title">Thread title</label>
                            <input class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="content">Message</label>
                            <textarea class="form-control" id="content" name="content" required></textarea>
                        </div>

                        <?php if ($errors !== []): ?>
                            <div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div>
                        <?php endif; ?>

                        <button class="btn btn-ink w-100" type="submit">Post thread</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <?php if ($threads === []): ?>
                    <div class="content-panel empty-state">
                        <h3>No discussions yet</h3>
                        <p>Start the first course conversation.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($threads as $thread): ?>
                        <article class="discussion-thread" id="thread-<?= $thread['id'] ?>">
                            <header>
                                <span class="role-chip"><?= e($thread['author_role']) ?></span>
                                <h2><?= e($thread['title']) ?></h2>
                                <small>
                                    <?= e($thread['author_name']) ?>
                                    &middot;
                                    <?= e(format_datetime($thread['created_at'])) ?>
                                </small>
                            </header>

                            <p><?= nl2br(e($thread['content'])) ?></p>

                            <div class="reply-list">
                                <?php foreach ($thread['replies'] as $reply): ?>
                                    <div class="reply">
                                        <span class="role-chip"><?= e($reply['author_role']) ?></span>
                                        <strong><?= e($reply['author_name']) ?></strong>
                                        <small><?= e(format_datetime($reply['created_at'])) ?></small>
                                        <p><?= nl2br(e($reply['content'])) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <form method="post" class="reply-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="course_id" value="<?= $courseId ?>">
                                <input type="hidden" name="action" value="reply">
                                <input type="hidden" name="thread_id" value="<?= $thread['id'] ?>">
                                <label class="form-label" for="reply-<?= $thread['id'] ?>">Reply</label>
                                <div class="d-flex gap-2">
                                    <textarea
                                        class="form-control"
                                        id="reply-<?= $thread['id'] ?>"
                                        name="content"
                                        rows="2"
                                        required
                                    ></textarea>
                                    <button class="btn btn-sun" type="submit">Reply</button>
                                </div>
                            </form>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
