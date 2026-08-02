<?php
declare(strict_types=1);

// Upload, edit, and list materials for an owned course.
require_once __DIR__ . '/../includes/material_access.php';
require_once __DIR__ . '/../includes/upload.php';

$user = require_role('instructor');
$courseId = positive_id($_GET['course_id'] ?? $_POST['course_id'] ?? null);
$materialId = positive_id($_GET['edit_id'] ?? $_POST['material_id'] ?? null);
$page = positive_id($_GET['page'] ?? $_POST['page'] ?? null) ?? 1;
$materialsPerPage = 6;

// Load the instructor's courses for the selector.
$courseStatement = database()->prepare(
    'SELECT id, course_code, title
     FROM courses
     WHERE instructor_id = :instructor_id
     ORDER BY course_code'
);
$courseStatement->execute(['instructor_id' => $user['id']]);
$courses = $courseStatement->fetchAll();

$editingMaterial = null;
if ($materialId) {
    $editingStatement = database()->prepare(
        'SELECT m.*, c.course_code, c.title AS course_title
         FROM course_materials m
         JOIN courses c ON c.id = m.course_id
         WHERE m.id = :material_id AND c.instructor_id = :instructor_id
         LIMIT 1'
    );
    $editingStatement->execute([
        'material_id' => $materialId,
        'instructor_id' => $user['id'],
    ]);
    $editingMaterial = $editingStatement->fetch() ?: not_found('Material not found or not owned by this instructor.');
    $editingCourseId = (int) $editingMaterial['course_id'];
    if ($courseId !== null && $courseId !== $editingCourseId) {
        not_found('Material not found or not owned by this instructor.');
    }
    $courseId = $editingCourseId;
}

$selectedCourse = $courseId ? owned_course($courseId, (int) $user['id']) : null;

$errors = [];
$title = $editingMaterial['title'] ?? '';
$description = $editingMaterial['description'] ?? '';
$memberAccessAt = $editingMaterial !== null
    ? material_datetime_local_value($editingMaterial['member_access_at'])
    : '';
$publicAccessAt = $editingMaterial !== null
    ? material_datetime_local_value($editingMaterial['public_access_at'])
    : '';

if (is_post()) {
    // Validate the material details, access schedule, and uploaded file.
    verify_csrf();
    if (!$courseId) {
        $errors['course_id'] = 'Select a valid course.';
    }

    $title = clean_text($_POST['title'] ?? '');
    $description = clean_text($_POST['description'] ?? '');
    $memberAccessAt = clean_text($_POST['member_access_at'] ?? '');
    $publicAccessAt = clean_text($_POST['public_access_at'] ?? '');

    if (mb_strlen($title) < 2 || mb_strlen($title) > 180) {
        $errors['title'] = 'Enter a title between 2 and 180 characters.';
    }
    if (mb_strlen($description) > 3000) {
        $errors['description'] = 'Keep the description below 3,000 characters.';
    }

    $memberAccessDate = parse_material_datetime($memberAccessAt);
    $publicAccessDate = parse_material_datetime($publicAccessAt);
    if ($memberAccessDate === null) {
        $errors['member_access_at'] = 'Enter a valid Member Access Time.';
    }
    if ($publicAccessDate === null) {
        $errors['public_access_at'] = 'Enter a valid Public Access Time.';
    } elseif ($memberAccessDate !== null && $publicAccessDate < $memberAccessDate) {
        $errors['public_access_at'] = 'Public Access Time must be the same as or later than Member Access Time.';
    }

    $upload = null;
    $hasNewUpload = isset($_FILES['material_file'])
        && is_array($_FILES['material_file'])
        && (int) ($_FILES['material_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($errors === []) {
        if ($materialId === null || $hasNewUpload) {
            try {
                $upload = store_upload($_FILES['material_file'] ?? [], 'material');
            } catch (InvalidArgumentException|RuntimeException $exception) {
                $errors['material_file'] = $exception->getMessage();
            }
        }
    }

    if ($errors === []) {
        $accessParameters = [
            'member_access_at' => material_datetime_for_database($memberAccessDate),
            'public_access_at' => material_datetime_for_database($publicAccessDate),
        ];

        try {
            if ($materialId !== null) {
                $updateSql = $upload !== null
                    ? 'UPDATE course_materials
                       SET title = :title, description = :description,
                           member_access_at = :member_access_at,
                           public_access_at = :public_access_at,
                           stored_filename = :stored, original_filename = :original,
                           mime_type = :mime, file_size = :size
                       WHERE id = :id AND course_id = :course_id'
                    : 'UPDATE course_materials
                       SET title = :title, description = :description,
                           member_access_at = :member_access_at,
                           public_access_at = :public_access_at
                       WHERE id = :id AND course_id = :course_id';
                $updateParameters = array_merge($accessParameters, [
                    'title' => $title,
                    'description' => $description ?: null,
                    'id' => $materialId,
                    'course_id' => $courseId,
                ]);
                if ($upload !== null) {
                    $updateParameters = array_merge($updateParameters, [
                        'stored' => $upload['stored_filename'],
                        'original' => $upload['original_filename'],
                        'mime' => $upload['mime_type'],
                        'size' => $upload['file_size'],
                    ]);
                }
                $update = database()->prepare($updateSql);
                $update->execute($updateParameters);

                if ($upload !== null && $editingMaterial !== null) {
                    $oldPath = __DIR__ . '/../uploads/materials/' . $editingMaterial['stored_filename'];
                    $referenceStatement = database()->prepare(
                        'SELECT COUNT(*)
                         FROM course_materials
                         WHERE stored_filename = :stored_filename AND id <> :material_id'
                    );
                    $referenceStatement->execute([
                        'stored_filename' => $editingMaterial['stored_filename'],
                        'material_id' => $materialId,
                    ]);
                    $isSharedFile = (int) $referenceStatement->fetchColumn() > 0;

                    if (
                        !$isSharedFile
                        && is_file($oldPath)
                        && $editingMaterial['stored_filename'] !== $upload['stored_filename']
                    ) {
                        @unlink($oldPath);
                    }
                }

                flash('success', 'Course material updated.');
            } else {
                $insert = database()->prepare(
                    'INSERT INTO course_materials
                        (course_id, uploaded_by, title, description, stored_filename,
                         original_filename, mime_type, file_size, member_access_at,
                         public_access_at)
                     VALUES
                        (:course_id, :uploaded_by, :title, :description, :stored,
                         :original, :mime, :size, :member_access_at,
                         :public_access_at)'
                );
                $insert->execute(array_merge($accessParameters, [
                    'course_id' => $courseId,
                    'uploaded_by' => $user['id'],
                    'title' => $title,
                    'description' => $description ?: null,
                    'stored' => $upload['stored_filename'],
                    'original' => $upload['original_filename'],
                    'mime' => $upload['mime_type'],
                    'size' => $upload['file_size'],
                ]));

                flash('success', 'Course material uploaded.');
            }
        } catch (Throwable $exception) {
            if ($upload !== null) {
                @unlink($upload['path']);
            }
            throw $exception;
        }

        redirect('instructor/materials.php?course_id=' . $courseId);
    }
}

// Count materials before loading only the requested six-item page.
$materials = [];
$totalMaterials = 0;
$totalPages = 1;
if ($selectedCourse) {
    $countStatement = database()->prepare(
        'SELECT COUNT(*) FROM course_materials WHERE course_id = :course'
    );
    $countStatement->execute(['course' => $courseId]);
    $totalMaterials = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalMaterials / $materialsPerPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $materialsPerPage;

    $materialStatement = database()->prepare(
        'SELECT *
         FROM course_materials
         WHERE course_id = :course
         ORDER BY uploaded_at DESC, id DESC
         LIMIT :limit OFFSET :offset'
    );
    $materialStatement->bindValue(':course', $courseId, PDO::PARAM_INT);
    $materialStatement->bindValue(':limit', $materialsPerPage, PDO::PARAM_INT);
    $materialStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $materialStatement->execute();
    $materials = $materialStatement->fetchAll();
}
$courseBreadcrumb = $selectedCourse ? [
    'label' => $selectedCourse['course_code'],
    'url' => url('instructor/course.php?id=' . $selectedCourse['id']),
] : null;
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
                    <h2 class="form-title"><?= $editingMaterial !== null ? 'Edit material' : 'Upload material' ?></h2>
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <?php if ($materialId !== null): ?>
                            <input type="hidden" name="material_id" value="<?= $materialId ?>">
                        <?php endif; ?>

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
                            <label class="form-label" for="member_access_at">Member Access Time</label>
                            <input
                                class="form-control <?= isset($errors['member_access_at']) ? 'is-invalid' : '' ?>"
                                type="datetime-local"
                                id="member_access_at"
                                name="member_access_at"
                                value="<?= e($memberAccessAt) ?>"
                                required
                            >
                            <?php if (isset($errors['member_access_at'])): ?>
                                <div class="invalid-feedback"><?= e($errors['member_access_at']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="public_access_at">Public Access Time</label>
                            <input
                                class="form-control <?= isset($errors['public_access_at']) ? 'is-invalid' : '' ?>"
                                type="datetime-local"
                                id="public_access_at"
                                name="public_access_at"
                                value="<?= e($publicAccessAt) ?>"
                                required
                            >
                            <?php if (isset($errors['public_access_at'])): ?>
                                <div class="invalid-feedback"><?= e($errors['public_access_at']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="material_file">File</label>
                            <input
                                class="form-control <?= isset($errors['material_file']) ? 'is-invalid' : '' ?>"
                                type="file"
                                id="material_file"
                                name="material_file"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
                                <?= $editingMaterial === null ? 'required' : '' ?>
                            >
                            <?php if (isset($errors['material_file'])): ?>
                                <div class="invalid-feedback"><?= e($errors['material_file']) ?></div>
                            <?php endif; ?>
                            <div class="form-hint">
                                <?= $editingMaterial !== null
                                    ? 'Leave empty to keep the current file, or choose a replacement.'
                                    : 'PDF, Office, text, or ZIP &middot; maximum 10 MB.' ?>
                            </div>
                        </div>

                        <button class="btn btn-ink w-100" type="submit">
                            <?= $editingMaterial !== null ? 'Save changes' : 'Upload material' ?>
                        </button>
                        <?php if ($editingMaterial !== null): ?>
                            <a
                                class="btn btn-outline-ink w-100 mt-2"
                                href="<?= e(url('instructor/materials.php?course_id=' . $courseId)) ?>"
                            >Cancel edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="content-panel">
                    <div class="panel-heading">
                        <h2><?= e($selectedCourse ? $selectedCourse['course_code'] . ' materials' : 'Select a course') ?></h2>
                        <span class="count-pill"><?= $totalMaterials ?></span>
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
                                <div class="material-row">
                                    <div>
                                        <strong><?= e($material['title']) ?></strong>
                                        <small>
                                            <?= e($material['original_filename']) ?>
                                            &middot;
                                            <?= number_format($material['file_size'] / 1024, 1) ?> KB
                                            &middot;
                                            Uploaded <?= e(format_datetime($material['uploaded_at'])) ?>
                                        </small>
                                        <small>
                                            Member access: <?= e(format_datetime($material['member_access_at'])) ?>
                                            &middot;
                                            Public access: <?= e(format_datetime($material['public_access_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="material-actions">
                                        <a href="<?= e(url('download.php?type=material&id=' . $material['id'])) ?>">Download</a>
                                        <a href="<?= e(url('instructor/materials.php?course_id=' . $courseId . '&edit_id=' . $material['id'])) ?>">Edit</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $paginationPage = $page;
                    $paginationTotalPages = $totalPages;
                    $paginationPath = 'instructor/materials.php';
                    $paginationParameters = ['course_id' => $courseId];
                    $paginationLabel = 'Material pages';
                    require __DIR__ . '/../includes/pagination.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
