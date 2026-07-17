<?php
declare(strict_types=1);

// Add or update a grade and feedback for one submission.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('instructor');
$submissionId = positive_id($_GET['submission_id'] ?? null)
    ?? not_found('Invalid submission ID.');

// Load the submission only when its course belongs to this instructor.
$submissionStatement = database()->prepare(
    'SELECT s.*, u.full_name AS student_name,
            a.title AS assignment_title, a.max_grade, a.course_id,
            c.course_code, g.grade_value, g.feedback
     FROM submissions s
     JOIN users u ON u.id = s.student_id
     JOIN assignments a ON a.id = s.assignment_id
     JOIN courses c ON c.id = a.course_id
     LEFT JOIN grades g ON g.submission_id = s.id
     WHERE s.id = :submission AND c.instructor_id = :owner
     LIMIT 1'
);
$submissionStatement->execute([
    'submission' => $submissionId,
    'owner' => $user['id'],
]);
$submission = $submissionStatement->fetch()
    ?: not_found('Submission not found or not owned.');

$errors = [];
$grade = (string) ($submission['grade_value'] ?? '');
$feedback = (string) ($submission['feedback'] ?? '');

if (is_post()) {
    // Validate the grade against the assignment maximum.
    verify_csrf();
    $grade = clean_text($_POST['grade_value'] ?? '');
    $feedback = clean_text($_POST['feedback'] ?? '');
    $numericGrade = filter_var($grade, FILTER_VALIDATE_FLOAT);

    if (
        $numericGrade === false
        || $numericGrade < 0
        || $numericGrade > (float) $submission['max_grade']
    ) {
        $errors['grade_value'] = 'Enter a grade from 0 to ' . $submission['max_grade'] . '.';
    }
    if (mb_strlen($feedback) > 5000) {
        $errors['feedback'] = 'Keep feedback below 5,000 characters.';
    }

    if ($errors === []) {
        // Insert the first grade or update the existing grade and feedback.
        $upsert = database()->prepare(
            'INSERT INTO grades (submission_id, graded_by, grade_value, feedback)
             VALUES (:submission, :grader, :grade, :feedback)
             ON DUPLICATE KEY UPDATE
                graded_by = VALUES(graded_by),
                grade_value = VALUES(grade_value),
                feedback = VALUES(feedback),
                graded_at = CURRENT_TIMESTAMP'
        );
        $upsert->execute([
            'submission' => $submissionId,
            'grader' => $user['id'],
            'grade' => $numericGrade,
            'feedback' => $feedback ?: null,
        ]);
        flash('success', 'Grade and feedback saved.');
        redirect(
            'instructor/submissions.php?course_id=' . $submission['course_id']
            . '&assignment_id=' . $submission['assignment_id']
        );
    }
}

$pageTitle = 'Grade submission';
$activePage = 'instructor-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container narrow-container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">
                    <?= e($submission['course_code']) ?>
                    &middot; Attempt <?= $submission['attempt_number'] ?>
                </p>
                <h1>Grade <?= e($submission['student_name']) ?>.</h1>
            </div>
            <a
                class="btn btn-outline-ink"
                href="<?= e(url('download.php?type=submission&id=' . $submissionId)) ?>"
            >Download file</a>
        </div>

        <div class="content-panel">
            <h2><?= e($submission['assignment_title']) ?></h2>
            <p class="text-muted">
                <?= e($submission['original_filename']) ?>
                &middot; submitted <?= e(format_datetime($submission['submitted_at'])) ?>
            </p>

            <?php if ($submission['student_note']): ?>
                <div class="feedback-box mb-4">
                    <strong>Student note</strong>
                    <p><?= e($submission['student_note']) ?></p>
                </div>
            <?php endif; ?>

            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="grade_value">
                        Grade (maximum <?= e($submission['max_grade']) ?>)
                    </label>
                    <input
                        class="form-control <?= isset($errors['grade_value']) ? 'is-invalid' : '' ?>"
                        type="number"
                        step=".01"
                        min="0"
                        max="<?= e($submission['max_grade']) ?>"
                        id="grade_value"
                        name="grade_value"
                        value="<?= e($grade) ?>"
                        required
                    >
                    <?php if (isset($errors['grade_value'])): ?>
                        <div class="invalid-feedback"><?= e($errors['grade_value']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="feedback">Feedback</label>
                    <textarea
                        class="form-control <?= isset($errors['feedback']) ? 'is-invalid' : '' ?>"
                        id="feedback"
                        name="feedback"
                        maxlength="5000"
                    ><?= e($feedback) ?></textarea>
                    <?php if (isset($errors['feedback'])): ?>
                        <div class="invalid-feedback"><?= e($errors['feedback']) ?></div>
                    <?php endif; ?>
                </div>

                <button class="btn btn-sun" type="submit">Save grade and feedback</button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
