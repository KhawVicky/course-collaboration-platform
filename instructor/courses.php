<?php
declare(strict_types=1);

// Create courses and list courses owned by this instructor.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('instructor');
$errors = [];
$courseCode = '';
$title = '';
$description = '';

if (is_post()) {
    // Validate the new course details.
    verify_csrf();
    $courseCode = strtoupper(clean_text($_POST['course_code'] ?? ''));
    $title = clean_text($_POST['title'] ?? '');
    $description = clean_text($_POST['description'] ?? '');

    if (!preg_match('/^[A-Z0-9][A-Z0-9-]{1,29}$/', $courseCode)) {
        $errors['course_code'] = 'Use 2-30 letters, numbers, or hyphens.';
    }
    if (mb_strlen($title) < 3 || mb_strlen($title) > 180) {
        $errors['title'] = 'Enter a title between 3 and 180 characters.';
    }
    if (mb_strlen($description) > 5000) {
        $errors['description'] = 'Keep the description below 5,000 characters.';
    }

    if ($errors === []) {
        // Save the course and open its detail page.
        try {
            $insert = database()->prepare(
                'INSERT INTO courses
                    (instructor_id, course_code, title, description)
                 VALUES
                    (:instructor_id, :course_code, :title, :description)'
            );
            $insert->execute([
                'instructor_id' => $user['id'],
                'course_code' => $courseCode,
                'title' => $title,
                'description' => $description !== '' ? $description : null,
            ]);
            flash('success', 'Course created successfully.');
            redirect('instructor/course.php?id=' . database()->lastInsertId());
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors['course_code'] = 'This course code is already in use.';
            } else {
                throw $exception;
            }
        }
    }
}

// Load all courses owned by this instructor with enrolment totals.
$courseStatement = database()->prepare(
    'SELECT c.*, COUNT(DISTINCT e.student_id) AS student_count
     FROM courses c
     LEFT JOIN enrolments e
       ON e.course_id = c.id
      AND e.status = :status
     WHERE c.instructor_id = :instructor_id
     GROUP BY c.id
     ORDER BY c.created_at DESC'
);
$courseStatement->execute([
    'status' => 'active',
    'instructor_id' => $user['id'],
]);
$courses = $courseStatement->fetchAll();

$pageTitle = 'My courses';
$activePage = 'instructor-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Course management</p>
                <h1>Your teaching spaces.</h1>
            </div>
            <a class="btn btn-sun" href="#create-course">Create course</a>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="content-panel">
                    <div class="panel-heading">
                        <h2>Owned courses</h2>
                        <span class="count-pill"><?= count($courses) ?> total</span>
                    </div>

                    <?php if ($courses === []): ?>
                        <div class="empty-state">
                            <h3>No courses yet</h3>
                            <p>Create your first course using the form.</p>
                        </div>
                    <?php else: ?>
                        <div class="vstack gap-3">
                            <?php foreach ($courses as $course): ?>
                                <a
                                    class="course-list-item"
                                    href="<?= e(url('instructor/course.php?id=' . $course['id'])) ?>"
                                >
                                    <span class="course-code"><?= e($course['course_code']) ?></span>
                                    <div>
                                        <strong><?= e($course['title']) ?></strong>
                                        <small>
                                            <?= (int) $course['student_count'] ?> enrolled students
                                        </small>
                                    </div>
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5" id="create-course">
                <div class="content-panel sticky-lg-top app-sticky">
                    <p class="eyebrow">New course</p>
                    <h2 class="form-title">Create a teaching space</h2>

                    <form method="post" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label" for="course_code">Course code</label>
                            <input
                                class="form-control <?= isset($errors['course_code']) ? 'is-invalid' : '' ?>"
                                id="course_code"
                                name="course_code"
                                value="<?= e($courseCode) ?>"
                                maxlength="30"
                                required
                            >
                            <?php if (isset($errors['course_code'])): ?>
                                <div class="invalid-feedback"><?= e($errors['course_code']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="title">Course title</label>
                            <input
                                class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                id="title"
                                name="title"
                                value="<?= e($title) ?>"
                                maxlength="180"
                                required
                            >
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="description">Description</label>
                            <textarea
                                class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                id="description"
                                name="description"
                                maxlength="5000"
                            ><?= e($description) ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= e($errors['description']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button class="btn btn-ink w-100" type="submit">Create course</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
