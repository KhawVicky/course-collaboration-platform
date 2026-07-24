<?php
declare(strict_types=1);

// Show an enrolled student's collaboration profile to the course owner.
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('instructor');
$courseId = positive_id($_GET['course_id'] ?? null) ?? not_found('Invalid course ID.');
$studentId = positive_id($_GET['student_id'] ?? null) ?? not_found('Invalid student ID.');
$course = owned_course($courseId, (int) $user['id']);

$statement = database()->prepare(
    'SELECT u.full_name, u.email, sp.skills, sp.collaboration_mode, sp.availability
     FROM enrolments e
     JOIN users u ON u.id = e.student_id AND u.role = :role
     LEFT JOIN student_profiles sp ON sp.user_id = u.id
     WHERE e.course_id = :course_id AND e.student_id = :student_id AND e.status = :status LIMIT 1'
);
$statement->execute([
    'role' => 'student',
    'course_id' => $courseId,
    'student_id' => $studentId,
    'status' => 'active',
]);
$profile = $statement->fetch() ?: not_found('This student is not enrolled in your course.');

$courseBreadcrumb = [
    'label' => $course['course_code'],
    'url' => url('instructor/course.php?id=' . $courseId),
];
$pageTitle = 'Student collaboration profile';
$activePage = 'instructor-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell profile-shell">
    <div class="container narrow-container">
        <div class="page-heading">
            <div><p class="eyebrow"><?= e($course['course_code']) ?> collaboration profile</p><h1><?= e($profile['full_name']) ?>.</h1></div>
            <a class="btn btn-outline-ink" href="<?= e(url('instructor/course.php?id=' . $courseId)) ?>">Back to course</a>
        </div>
        <div class="profile-view-card">
            <div><span>Student name</span><strong><?= e($profile['full_name']) ?></strong></div>
            <div><span>Skills or expertise</span><p><?= nl2br(e($profile['skills'] ?: 'Not added yet.')) ?></p></div>
            <div><span>Preferred collaboration</span><strong><?= e($profile['collaboration_mode'] ?: 'Not selected yet.') ?></strong></div>
            <div><span>Availability</span><p><?= nl2br(e($profile['availability'] ?: 'Not provided.')) ?></p></div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

