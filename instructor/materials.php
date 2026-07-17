<?php
declare(strict_types=1);

// Upload and list materials for an owned course.
require_once __DIR__ . '/../includes/upload.php';

$user = require_role('instructor');
$courseId = positive_id($_GET['course_id'] ?? $_POST['course_id'] ?? null);

// Load the instructor's courses for the selector.
$courseStatement = database()->prepare(
    'SELECT id, course_code, title
     FROM courses
     WHERE instructor_id = :instructor_id
     ORDER BY course_code'
);
$courseStatement->execute(['instructor_id' => $user['id']]);
$courses = $courseStatement->fetchAll();
$selectedCourse = $courseId ? owned_course($courseId, (int) $user['id']) : null;

$errors = [];
$title = '';
$description = '';

if (is_post()) {
    // Validate the material details and uploaded file.
    verify_csrf();
    if (!$courseId) {
        $errors['course_id'] = 'Select a valid course.';
    }

    $title = clean_text($_POST['title'] ?? '');
    $description = clean_text($_POST['description'] ?? '');

    if (mb_strlen($title) < 2 || mb_strlen($title) > 180) {
        $errors['title'] = 'Enter a title between 2 and 180 characters.';
    }
    if (mb_strlen($description) > 3000) {
        $errors['description'] = 'Keep the description below 3,000 characters.';
    }

    $upload = null;
    if ($errors === []) {
        try {
            $upload = store_upload($_FILES['material_file'] ?? [], 'material');
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $errors['material_file'] = $exception->getMessage();
        }
    }

    if ($errors === [] && $upload !== null) {
        // Save the material record or remove the stored file on failure.
        try {
            $insert = database()->prepare(
                'INSERT INTO course_materials
                    (course_id, uploaded_by, title, description, stored_filename,
                     original_filename, mime_type, file_size)
                 VALUES
                    (:course_id, :uploaded_by, :title, :description, :stored,
                     :original, :mime, :size)'
            );
            $insert->execute([
                'course_id' => $courseId,
                'uploaded_by' => $user['id'],
                'title' => $title,
                'description' => $description ?: null,
                'stored' => $upload['stored_filename'],
                'original' => $upload['original_filename'],
                'mime' => $upload['mime_type'],
                'size' => $upload['file_size'],
            ]);
        } catch (Throwable $exception) {
            @unlink($upload['path']);
            throw $exception;
        }

        flash('success', 'Course material uploaded.');
        redirect('instructor/materials.php?course_id=' . $courseId);
    }
}

// Load materials from the selected owned course.
$materials = [];
if ($selectedCourse) {
    $materialStatement = database()->prepare(
        'SELECT *
         FROM course_materials
         WHERE course_id = :course_id
         ORDER BY uploaded_at DESC'
    );
    $materialStatement->execute(['course_id' => $courseId]);
    $materials = $materialStatement->fetchAll();
}

$pageTitle = 'Course materials';
$activePage = 'instructor-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Learning resources</p>
                <h1>Course materials.</h1>
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
                    <h2 class="form-title">Upload material</h2>
                    <form method="post" enctype="multipart/form-data">
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
                            <label class="form-label" for="title">Material title</label>
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
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description"><?= e($description) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="material_file">File</label>
                            <input
                                class="form-control <?= isset($errors['material_file']) ? 'is-invalid' : '' ?>"
                                type="file"
                                id="material_file"
                                name="material_file"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
                                required
                            >
                            <?php if (isset($errors['material_file'])): ?>
                                <div class="invalid-feedback"><?= e($errors['material_file']) ?></div>
                            <?php endif; ?>
                            <div class="form-hint">PDF, Office, text, or ZIP &middot; maximum 10 MB.</div>
                        </div>

                        <button class="btn btn-ink w-100" type="submit">Upload material</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="content-panel">
                    <div class="panel-heading">
                        <h2><?= e($selectedCourse ? $selectedCourse['course_code'] . ' materials' : 'Select a course') ?></h2>
                        <span class="count-pill"><?= count($materials) ?></span>
                    </div>

                    <?php if (!$selectedCourse): ?>
                        <div class="empty-state">
                            <p>Select a course to view its materials.</p>
                        </div>
                    <?php elseif ($materials === []): ?>
                        <div class="empty-state">
                            <p>No materials uploaded yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="resource-list">
                            <?php foreach ($materials as $material): ?>
                                <a href="<?= e(url('download.php?type=material&id=' . $material['id'])) ?>">
                                    <div>
                                        <strong><?= e($material['title']) ?></strong>
                                        <small>
                                            <?= e($material['original_filename']) ?>
                                            &middot;
                                            <?= number_format($material['file_size'] / 1024, 1) ?> KB
                                            &middot;
                                            <?= e(format_datetime($material['uploaded_at'])) ?>
                                        </small>
                                    </div>
                                    <span>Download</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
