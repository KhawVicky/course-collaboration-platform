<?php
declare(strict_types=1);

// Show basic participation data for students in an owned course.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('instructor');
$courseId = positive_id($_GET['course_id'] ?? null);

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
$students = [];

if ($selectedCourse) {
    // Calculate discussion, submission, and grade totals for each student.
    $monitoringStatement = database()->prepare(
        "SELECT u.id, u.full_name, u.email,
            (
                SELECT COUNT(*)
                FROM discussion_threads dt
                WHERE dt.course_id = :course1 AND dt.author_id = u.id
            ) AS thread_count,
            (
                SELECT COUNT(*)
                FROM discussion_replies dr
                JOIN discussion_threads dt2 ON dt2.id = dr.thread_id
                WHERE dt2.course_id = :course2 AND dr.author_id = u.id
            ) AS reply_count,
            (
                SELECT COUNT(*)
                FROM assignments a
                WHERE a.course_id = :course3
            ) AS assignment_count,
            (
                SELECT COUNT(DISTINCT s.assignment_id)
                FROM submissions s
                JOIN assignments a2 ON a2.id = s.assignment_id
                WHERE a2.course_id = :course4
                  AND s.student_id = u.id
                  AND s.is_latest = 1
            ) AS submitted_count,
            (
                SELECT MAX(s2.submitted_at)
                FROM submissions s2
                JOIN assignments a3 ON a3.id = s2.assignment_id
                WHERE a3.course_id = :course5 AND s2.student_id = u.id
            ) AS latest_submission,
            (
                SELECT GROUP_CONCAT(
                    CONCAT(a4.title, ': ', g.grade_value)
                    ORDER BY a4.deadline SEPARATOR ' | '
                )
                FROM submissions s3
                JOIN assignments a4 ON a4.id = s3.assignment_id
                LEFT JOIN grades g ON g.submission_id = s3.id
                WHERE a4.course_id = :course6
                  AND s3.student_id = u.id
                  AND s3.is_latest = 1
                  AND g.id IS NOT NULL
            ) AS grades
         FROM enrolments e
         JOIN users u ON u.id = e.student_id
         WHERE e.course_id = :course7 AND e.status = :status
         ORDER BY u.full_name"
    );
    $monitoringStatement->execute([
        'course1' => $courseId,
        'course2' => $courseId,
        'course3' => $courseId,
        'course4' => $courseId,
        'course5' => $courseId,
        'course6' => $courseId,
        'course7' => $courseId,
        'status' => 'active',
    ]);
    $students = $monitoringStatement->fetchAll();
}

$pageTitle = 'Student participation';
$activePage = 'instructor-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Participation overview</p>
                <h1>Student monitoring.</h1>
            </div>
        </div>

        <div class="content-panel mb-4">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label class="form-label" for="course_id">Owned course</label>
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
                <div class="col-md-3">
                    <button class="btn btn-ink w-100" type="submit">View participation</button>
                </div>
            </form>
        </div>

        <div class="content-panel">
            <div class="panel-heading">
                <h2><?= e($selectedCourse ? $selectedCourse['course_code'] . ' participation' : 'Select a course') ?></h2>
                <span class="count-pill"><?= count($students) ?></span>
            </div>

            <?php if ($students === []): ?>
                <div class="empty-state">
                    <p>No enrolled students to monitor.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Threads</th>
                                <th>Replies</th>
                                <th>Submission status</th>
                                <th>Latest submission</th>
                                <th>Grades</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($student['full_name']) ?></strong>
                                        <small class="d-block"><?= e($student['email']) ?></small>
                                    </td>
                                    <td><?= $student['thread_count'] ?></td>
                                    <td><?= $student['reply_count'] ?></td>
                                    <td>
                                        <?= $student['submitted_count'] ?> /
                                        <?= $student['assignment_count'] ?> assignments
                                    </td>
                                    <td><?= e(format_datetime($student['latest_submission'], 'No submission')) ?></td>
                                    <td><?= e($student['grades'] ?: 'Not graded') ?></td>
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
