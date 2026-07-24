<?php
declare(strict_types=1);

// Show all published courses and the student's enrolment state.
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$page = positive_id($_GET['page'] ?? null) ?? 1;
$coursesPerPage = 6;
$enrolmentFilter = clean_text($_GET['enrolment'] ?? 'all');
if (!in_array($enrolmentFilter, ['all', 'enrolled', 'not-enrolled'], true)) {
    $enrolmentFilter = 'all';
}

// Filter published courses before loading only the requested twelve-item page.
$fromSql =
    ' FROM courses c
     JOIN users u ON u.id = c.instructor_id
     LEFT JOIN enrolments e
       ON e.course_id = c.id
      AND e.student_id = :student_id
     WHERE c.is_published = 1';
$parameters = ['student_id' => (int) $user['id']];

if ($enrolmentFilter === 'enrolled') {
    $fromSql .= ' AND e.status = :enrolment_status';
    $parameters['enrolment_status'] = 'active';
} elseif ($enrolmentFilter === 'not-enrolled') {
    $fromSql .= ' AND (e.id IS NULL OR e.status <> :enrolment_status)';
    $parameters['enrolment_status'] = 'active';
}

$countStatement = database()->prepare('SELECT COUNT(*)' . $fromSql);
foreach ($parameters as $name => $value) {
    $countStatement->bindValue(
        ':' . $name,
        $value,
        is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
    );
}
$countStatement->execute();
$totalCourses = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalCourses / $coursesPerPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $coursesPerPage;

$sql =
    'SELECT c.id, c.course_code, c.title, c.description,
            u.full_name AS instructor_name,
            CASE WHEN e.status = :active_status THEN 1 ELSE 0 END AS is_enrolled'
    . $fromSql
    . ' ORDER BY c.created_at DESC, c.id DESC
        LIMIT :limit OFFSET :offset';
$courseStatement = database()->prepare($sql);
$courseStatement->bindValue(':active_status', 'active');
foreach ($parameters as $name => $value) {
    $courseStatement->bindValue(
        ':' . $name,
        $value,
        is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
    );
}
$courseStatement->bindValue(':limit', $coursesPerPage, PDO::PARAM_INT);
$courseStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
$courseStatement->execute();
$courses = $courseStatement->fetchAll();
$pageTitle = 'Browse courses';
$activePage = 'browse-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Course catalogue</p>
                <h1>Find your next course.</h1>
            </div>
            <a class="btn btn-outline-ink" href="<?= e(url('student/enrolled.php')) ?>">
                View enrolled courses
            </a>
        </div>

        <div class="course-filter-bar mb-4">
            <form method="get">
                <label class="form-label" for="enrolment">Course status</label>
                <select
                    class="form-select"
                    id="enrolment"
                    name="enrolment"
                    data-submit-on-change
                >
                    <option value="all" <?= $enrolmentFilter === 'all' ? 'selected' : '' ?>>All courses</option>
                    <option value="enrolled" <?= $enrolmentFilter === 'enrolled' ? 'selected' : '' ?>>Enrolled</option>
                    <option value="not-enrolled" <?= $enrolmentFilter === 'not-enrolled' ? 'selected' : '' ?>>Not enrolled</option>
                </select>
            </form>
            <span class="count-pill"><?= $totalCourses ?> courses</span>
        </div>

        <?php if ($courses === []): ?>
            <div class="content-panel empty-state">
                <h3>No courses available</h3>
                <p>Published courses will appear here.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="catalog-card h-100">
                            <span class="course-code"><?= e($course['course_code']) ?></span>
                            <h2><?= e($course['title']) ?></h2>
                            <p>
                                <?= e(
                                    $course['description']
                                    ?: 'Explore this course and its learning activities.'
                                ) ?>
                            </p>
                            <small>
                                Instructor &middot; <?= e($course['instructor_name']) ?>
                            </small>
                            <a
                                class="btn <?= (int) $course['is_enrolled'] === 1
                                    ? 'btn-outline-ink'
                                    : 'btn-ink' ?> mt-4"
                                href="<?= e(url('student/course.php?id=' . $course['id'])) ?>"
                            >
                                <?= (int) $course['is_enrolled'] === 1
                                    ? 'Open course'
                                    : 'View details' ?>
                            </a>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php
        $paginationPage = $page;
        $paginationTotalPages = $totalPages;
        $paginationPath = 'student/courses.php';
        $paginationParameters = ['enrolment' => $enrolmentFilter];
        $paginationLabel = 'Course pages';
        require __DIR__ . '/../includes/pagination.php';
        ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
