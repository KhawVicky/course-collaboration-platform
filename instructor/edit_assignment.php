<?php
declare(strict_types=1);

// Edit an assignment only when its course belongs to this instructor.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('instructor');
$id = positive_id($_GET['id'] ?? null) ?? not_found('Invalid assignment ID.');

// Load the assignment with an ownership check.
$assignmentStatement = database()->prepare(
    'SELECT a.*, c.course_code, c.instructor_id
     FROM assignments a
     JOIN courses c ON c.id = a.course_id
     WHERE a.id = :id AND c.instructor_id = :owner
     LIMIT 1'
);
$assignmentStatement->execute([
    'id' => $id,
    'owner' => $user['id'],
]);
$assignment = $assignmentStatement->fetch()
    ?: not_found('Assignment not found or not owned.');
$errors = [];
$deadlineDate = date('Y-m-d', strtotime($assignment['deadline']));
$deadlineTime = date('H:i', strtotime($assignment['deadline']));

if (is_post()) {
    // Validate all editable assignment fields.
    verify_csrf();
    $title = clean_text($_POST['title'] ?? '');
    $instructions = clean_text($_POST['instructions'] ?? '');
    $deadlineDate = clean_text($_POST['deadline_date'] ?? '');
    $deadlineTime = clean_text($_POST['deadline_time'] ?? '');
    $deadline = $deadlineDate . 'T' . $deadlineTime;
    $maxGrade = filter_var($_POST['max_grade'] ?? null, FILTER_VALIDATE_FLOAT);

    if (mb_strlen($title) < 2 || mb_strlen($title) > 180) {
        $errors['title'] = 'Enter a valid title.';
    }
    if (mb_strlen($instructions) < 5 || mb_strlen($instructions) > 10000) {
        $errors['instructions'] = 'Enter valid instructions.';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $deadline);
    if (!$date) {
        $errors['deadline'] = 'Choose a valid deadline.';
    }
    if ($maxGrade === false || $maxGrade <= 0 || $maxGrade > 9999.99) {
        $errors['max_grade'] = 'Enter a valid maximum grade.';
    }

    if ($errors === []) {
        // Save the updated assignment details.
        $update = database()->prepare(
            'UPDATE assignments
             SET title = :title,
                 instructions = :instructions,
                 deadline = :deadline,
                 max_grade = :grade
             WHERE id = :id'
        );
        $update->execute([
            'title' => $title,
            'instructions' => $instructions,
            'deadline' => $date->format('Y-m-d H:i:s'),
            'grade' => $maxGrade,
            'id' => $id,
        ]);
        flash('success', 'Assignment updated.');
        redirect('instructor/assignments.php?course_id=' . $assignment['course_id']);
    }

    // Keep submitted values visible after validation errors.
    $assignment = array_merge($assignment, [
        'title' => $title,
        'instructions' => $instructions,
        'deadline' => $date ? $date->format('Y-m-d H:i:s') : $assignment['deadline'],
        'max_grade' => $maxGrade,
    ]);
}

$courseBreadcrumb = [
    'label' => $assignment['course_code'],
    'url' => url('instructor/course.php?id=' . $assignment['course_id']),
];
$pageTitle = 'Edit assignment';
$activePage = 'instructor-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container narrow-container">
        <div class="page-heading">
            <div>
                <p class="eyebrow"><?= e($assignment['course_code']) ?></p>
                <h1>Edit assignment.</h1>
            </div>
        </div>

        <div class="content-panel">
            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="title">Title</label>
                    <input
                        class="form-control"
                        id="title"
                        name="title"
                        value="<?= e($assignment['title']) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label" for="instructions">Instructions</label>
                    <textarea
                        class="form-control"
                        id="instructions"
                        name="instructions"
                        required
                    ><?= e($assignment['instructions']) ?></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="deadline_date">Deadline date</label>
                        <input
                            class="form-control"
                            type="date"
                            id="deadline_date"
                            name="deadline_date"
                            value="<?= e($deadlineDate) ?>"
                            required
                        >
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="deadline_time">Deadline time</label>
                        <input
                            class="form-control"
                            type="time"
                            id="deadline_time"
                            name="deadline_time"
                            value="<?= e($deadlineTime) ?>"
                            required
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="max_grade">Maximum grade</label>
                        <input
                            class="form-control"
                            type="number"
                            step=".01"
                            min=".01"
                            id="max_grade"
                            name="max_grade"
                            value="<?= e((string) $assignment['max_grade']) ?>"
                            required
                        >
                    </div>
                </div>

                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger mt-3"><?= e(implode(' ', $errors)) ?></div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-ink" type="submit">Save changes</button>
                    <a
                        class="btn btn-outline-ink"
                        href="<?= e(url('instructor/assignments.php?course_id=' . $assignment['course_id'])) ?>"
                    >Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
