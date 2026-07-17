<?php
declare(strict_types=1);

// Show one owned course and its enrolled students.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('instructor');
$courseId = positive_id($_GET['id'] ?? null) ?? not_found('Invalid course ID.');
$course = owned_course($courseId, (int) $user['id']);

// Load active students enrolled in this owned course.
$studentStatement = database()->prepare(
    'SELECT u.id, u.full_name, u.email, e.enrolled_at
     FROM enrolments e
     JOIN users u ON u.id = e.student_id
     WHERE e.course_id = :course_id AND e.status = :status
     ORDER BY u.full_name'
);
$studentStatement->execute([
    'course_id' => $courseId,
    'status' => 'active',
]);
$students = $studentStatement->fetchAll();

$pageTitle = $course['course_code'];
$activePage = 'instructor-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="course-hero">
            <span><?= e($course['course_code']) ?></span>
            <h1><?= e($course['title']) ?></h1>
            <p><?= e($course['description'] ?: 'No course description has been added yet.') ?></p>
        </div>

        <nav class="action-grid" aria-label="Course tools">
            <a href="<?= e(url('instructor/materials.php?course_id=' . $courseId)) ?>">
                <span>01</span> Materials
            </a>
            <a href="<?= e(url('instructor/assignments.php?course_id=' . $courseId)) ?>">
                <span>02</span> Assignments
            </a>
            <a href="<?= e(url('instructor/submissions.php?course_id=' . $courseId)) ?>">
                <span>03</span> Submissions
            </a>
            <a href="<?= e(url('instructor/announcements.php?course_id=' . $courseId)) ?>">
                <span>04</span> Announcements
            </a>
            <a href="<?= e(url('discussions.php?course_id=' . $courseId)) ?>">
                <span>05</span> Discussions
            </a>
            <a href="<?= e(url('instructor/monitoring.php?course_id=' . $courseId)) ?>">
                <span>06</span> Monitoring
            </a>
        </nav>

        <div class="content-panel mt-4">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow mb-2">Class list</p>
                    <h2>Enrolled students</h2>
                </div>
                <span class="count-pill"><?= count($students) ?></span>
            </div>

            <?php if ($students === []): ?>
                <div class="empty-state">
                    <h3>No students enrolled</h3>
                    <p>Students will appear here after they enrol.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Enrolled</th>
                                <th>Profile</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><strong><?= e($student['full_name']) ?></strong></td>
                                    <td><?= e($student['email']) ?></td>
                                    <td><?= e(format_datetime($student['enrolled_at'])) ?></td>
                                    <td>
                                        <a href="<?= e(url(
                                            'instructor/student_profile.php?course_id=' . $courseId
                                            . '&student_id=' . $student['id']
                                        )) ?>">View profile</a>
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
