<?php
declare(strict_types=1);

// Create and list assignments for an owned course.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('instructor');
$courseId = positive_id($_GET['course_id'] ?? $_POST['course_id'] ?? null);

// Load the instructor's courses and validate the selected course.
$courseStatement = database()->prepare(
    'SELECT id, course_code, title
     FROM courses
     WHERE instructor_id = :id
     ORDER BY course_code'
);
$courseStatement->execute(['id' => $user['id']]);
$courses = $courseStatement->fetchAll();
$selectedCourse = $courseId ? owned_course($courseId, (int) $user['id']) : null;

$errors = [];
$title = '';
$instructions = '';
$deadline = '';
$maxGrade = '100.00';

if (is_post()) {
    // Validate the new assignment details.
    verify_csrf();
    if (!$courseId) {
        $errors['course_id'] = 'Select a course.';
    }

    $title = clean_text($_POST['title'] ?? '');
    $instructions = clean_text($_POST['instructions'] ?? '');
    $deadline = clean_text($_POST['deadline'] ?? '');
    $maxGrade = clean_text($_POST['max_grade'] ?? '');

    if (mb_strlen($title) < 2 || mb_strlen($title) > 180) {
        $errors['title'] = 'Enter a title between 2 and 180 characters.';
    }
    if (mb_strlen($instructions) < 5 || mb_strlen($instructions) > 10000) {
        $errors['instructions'] = 'Enter instructions between 5 and 10,000 characters.';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $deadline);
    if (!$date) {
        $errors['deadline'] = 'Choose a valid deadline.';
    }

    $numericGrade = filter_var($maxGrade, FILTER_VALIDATE_FLOAT);
    if ($numericGrade === false || $numericGrade <= 0 || $numericGrade > 9999.99) {
        $errors['max_grade'] = 'Enter a maximum grade between 0.01 and 9999.99.';
    }

    if ($errors === []) {
        // Save the assignment for the selected owned course.
        $insert = database()->prepare(
            'INSERT INTO assignments
                (course_id, created_by, title, instructions, deadline, max_grade)
             VALUES
                (:course, :creator, :title, :instructions, :deadline, :grade)'
        );
        $insert->execute([
            'course' => $courseId,
            'creator' => $user['id'],
            'title' => $title,
            'instructions' => $instructions,
            'deadline' => $date->format('Y-m-d H:i:s'),
            'grade' => $numericGrade,
        ]);
        flash('success', 'Assignment created.');
        redirect('instructor/assignments.php?course_id=' . $courseId);
    }
}

// Load assignments and their latest-submission counts.
$assignments = [];
if ($selectedCourse) {
    $assignmentStatement = database()->prepare(
        'SELECT a.*,
            (
                SELECT COUNT(DISTINCT student_id)
                FROM submissions
                WHERE assignment_id = a.id AND is_latest = 1
            ) AS submission_count
         FROM assignments a
         WHERE course_id = :course
         ORDER BY deadline'
    );
    $assignmentStatement->execute(['course' => $courseId]);
    $assignments = $assignmentStatement->fetchAll();
}

$pageTitle = 'Assignments';
$activePage = 'instructor-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Assessment</p>
                <h1>Assignments.</h1>
            </div>
            <?php if ($selectedCourse): ?>
                <a
                    class="btn btn-outline-ink"
                    href="<?= e(url('instructor/course.php?id=' . $courseId)) ?>"
                >Back to course</a>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="content-panel">
                    <h2 class="form-title">Create assignment</h2>
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
                                class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                id="title"
                                name="title"
                                value="<?= e($title) ?>"
                                required
                            >
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="instructions">Instructions</label>
                            <textarea
                                class="form-control <?= isset($errors['instructions']) ? 'is-invalid' : '' ?>"
                                id="instructions"
                                name="instructions"
                                required
                            ><?= e($instructions) ?></textarea>
                            <?php if (isset($errors['instructions'])): ?>
                                <div class="invalid-feedback"><?= e($errors['instructions']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="deadline">Deadline</label>
                                <input
                                    class="form-control <?= isset($errors['deadline']) ? 'is-invalid' : '' ?>"
                                    type="datetime-local"
                                    id="deadline"
                                    name="deadline"
                                    value="<?= e($deadline) ?>"
                                    required
                                >
                                <?php if (isset($errors['deadline'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['deadline']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="max_grade">Maximum grade</label>
                                <input
                                    class="form-control"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    id="max_grade"
                                    name="max_grade"
                                    value="<?= e($maxGrade) ?>"
                                    required
                                >
                            </div>
                        </div>

                        <button class="btn btn-ink w-100 mt-4" type="submit">Create assignment</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="content-panel">
                    <div class="panel-heading">
                        <h2><?= e($selectedCourse ? $selectedCourse['course_code'] . ' assignments' : 'Select a course') ?></h2>
                        <span class="count-pill"><?= count($assignments) ?></span>
                    </div>

                    <?php if ($assignments === []): ?>
                        <div class="empty-state">
                            <p>No assignments to show.</p>
                        </div>
                    <?php else: ?>
                        <div class="assignment-list">
                            <?php foreach ($assignments as $assignment): ?>
                                <div class="assignment-admin">
                                    <div>
                                        <strong><?= e($assignment['title']) ?></strong>
                                        <small>
                                            Due <?= e(format_datetime($assignment['deadline'])) ?>
                                            &middot; <?= $assignment['submission_count'] ?> submitted
                                        </small>
                                    </div>
                                    <a
                                        class="btn btn-sm btn-outline-ink"
                                        href="<?= e(url('instructor/edit_assignment.php?id=' . $assignment['id'])) ?>"
                                    >Edit</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
