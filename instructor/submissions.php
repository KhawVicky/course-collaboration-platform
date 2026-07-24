<?php
declare(strict_types=1);

// Filter and show the latest student submissions for owned courses.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('instructor');
$courseId = positive_id($_GET['course_id'] ?? null);
$assignmentId = positive_id($_GET['assignment_id'] ?? null);

// Load courses owned by the current instructor.
$courseStatement = database()->prepare(
    'SELECT id, course_code, title
     FROM courses
     WHERE instructor_id = :owner
     ORDER BY course_code'
);
$courseStatement->execute(['owner' => $user['id']]);
$courses = $courseStatement->fetchAll();

$selectedCourse = $courseId ? owned_course($courseId, (int) $user['id']) : null;

// Load assignments for the selected course filter.
$assignments = [];
if ($courseId) {
    $assignmentStatement = database()->prepare(
        'SELECT id, title
         FROM assignments
         WHERE course_id = :course
         ORDER BY deadline'
    );
    $assignmentStatement->execute(['course' => $courseId]);
    $assignments = $assignmentStatement->fetchAll();
}

if ($assignmentId) {
    // Reject an assignment filter outside the instructor's owned courses.
    $ownershipSql =
        'SELECT COUNT(*)
         FROM assignments a
         JOIN courses c ON c.id = a.course_id
         WHERE a.id = :assignment
           AND c.instructor_id = :owner';
    $ownershipParameters = [
        'assignment' => $assignmentId,
        'owner' => $user['id'],
    ];

    if ($courseId) {
        $ownershipSql .= ' AND c.id = :course';
        $ownershipParameters['course'] = $courseId;
    }

    $ownershipStatement = database()->prepare($ownershipSql);
    $ownershipStatement->execute($ownershipParameters);
    if (!(int) $ownershipStatement->fetchColumn()) {
        not_found('Assignment not found.');
    }
}

// Build the latest-submission query from the selected filters.
$sql =
    'SELECT s.*, u.full_name AS student_name, u.email,
            a.title AS assignment_title, a.max_grade,
            c.course_code, g.grade_value, g.feedback
     FROM submissions s
     JOIN users u ON u.id = s.student_id
     JOIN assignments a ON a.id = s.assignment_id
     JOIN courses c ON c.id = a.course_id
     LEFT JOIN grades g ON g.submission_id = s.id
     WHERE c.instructor_id = :owner
       AND s.is_latest = 1';
$parameters = ['owner' => $user['id']];

if ($courseId) {
    $sql .= ' AND c.id = :course';
    $parameters['course'] = $courseId;
}
if ($assignmentId) {
    $sql .= ' AND a.id = :assignment';
    $parameters['assignment'] = $assignmentId;
}

$sql .= ' ORDER BY s.submitted_at DESC';
$submissionStatement = database()->prepare($sql);
$submissionStatement->execute($parameters);
$submissions = $submissionStatement->fetchAll();

$courseBreadcrumb = $selectedCourse ? [
    'label' => $selectedCourse['course_code'],
    'url' => url('instructor/course.php?id=' . $selectedCourse['id']),
] : null;
$pageTitle = 'Student submissions';
$activePage = 'instructor-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Review work</p>
                <h1>Student submissions.</h1>
            </div>
        </div>

        <div class="content-panel mb-4">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" for="course_id">Course</label>
                    <select
                        class="form-select"
                        id="course_id"
                        name="course_id"
                        data-submit-on-change
                    >
                        <option value="">All owned courses</option>
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

                <div class="col-md-5">
                    <label class="form-label" for="assignment_id">Assignment</label>
                    <select class="form-select" id="assignment_id" name="assignment_id">
                        <option value="">All assignments</option>
                        <?php foreach ($assignments as $assignment): ?>
                            <option
                                value="<?= $assignment['id'] ?>"
                                <?= $assignmentId === (int) $assignment['id'] ? 'selected' : '' ?>
                            >
                                <?= e($assignment['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-ink w-100" type="submit">Filter</button>
                </div>
            </form>
        </div>

        <div class="content-panel">
            <div class="panel-heading">
                <h2>Latest attempts</h2>
                <span class="count-pill"><?= count($submissions) ?></span>
            </div>

            <?php if ($submissions === []): ?>
                <div class="empty-state">
                    <p>No latest submissions match this filter.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Assignment</th>
                                <th>Attempt</th>
                                <th>Submitted</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($submission['student_name']) ?></strong>
                                        <small class="d-block"><?= e($submission['email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="course-code"><?= e($submission['course_code']) ?></span><br>
                                        <?= e($submission['assignment_title']) ?>
                                    </td>
                                    <td>#<?= $submission['attempt_number'] ?></td>
                                    <td><?= e(format_datetime($submission['submitted_at'])) ?></td>
                                    <td>
                                        <?= $submission['grade_value'] !== null
                                            ? e($submission['grade_value']) . ' / ' . e($submission['max_grade'])
                                            : 'Pending' ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?= e(url('download.php?type=submission&id=' . $submission['id'])) ?>">
                                                Download
                                            </a>
                                            <a href="<?= e(url('instructor/grade.php?submission_id=' . $submission['id'])) ?>">
                                                <?= $submission['grade_value'] !== null ? 'Update grade' : 'Grade' ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
