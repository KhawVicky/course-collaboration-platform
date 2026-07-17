<?php
declare(strict_types=1);

// Check database records created by the full browser test.

require_once __DIR__ . '/../config/database.php';

// Stop the test when one database rule fails.
function assert_database(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdo = database();
$expectedCounts = [
    'users' => 4,
    'memberships' => 2,
    'student_profiles' => 0,
    'courses' => 1,
    'enrolments' => 1,
    'course_materials' => 1,
    'assignments' => 2,
    'submissions' => 2,
    'grades' => 1,
    'announcements' => 1,
    'discussion_threads' => 2,
    'discussion_replies' => 1,
];

foreach ($expectedCounts as $table => $expected) {
    $actual = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    assert_database($actual === $expected, "{$table}: expected {$expected}, received {$actual}");
}

$submission = $pdo->query(
    "SELECT COUNT(*) AS attempt_count,
            SUM(is_latest) AS latest_count,
            MAX(attempt_number) AS max_attempt
     FROM submissions"
)->fetch();
assert_database((int) $submission['attempt_count'] === 2, 'Submission history must contain two attempts.');
assert_database((int) $submission['latest_count'] === 1, 'Exactly one submission attempt must be latest.');
assert_database((int) $submission['max_attempt'] === 2, 'The latest attempt number must be two.');

$lateCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     WHERE a.title = 'Closed Reflection'"
)->fetchColumn();
assert_database($lateCount === 0, 'The closed assignment must reject late submissions.');

$duplicateEnrolments = (int) $pdo->query(
    'SELECT COUNT(*) FROM (SELECT course_id, student_id, COUNT(*) AS total FROM enrolments GROUP BY course_id, student_id HAVING total > 1) duplicates'
)->fetchColumn();
assert_database($duplicateEnrolments === 0, 'Duplicate enrolment detected.');

$grade = $pdo->query('SELECT grade_value, feedback FROM grades LIMIT 1')->fetch();
assert_database((float) $grade['grade_value'] === 90.0, 'Updated grade value was not persisted.');
assert_database(str_contains((string) $grade['feedback'], 'Updated:'), 'Updated feedback was not persisted.');

$files = $pdo->query(
    "SELECT stored_filename FROM course_materials
     UNION ALL
     SELECT stored_filename FROM submissions"
)->fetchAll(PDO::FETCH_COLUMN);
foreach ($files as $filename) {
    assert_database((bool) preg_match('/^[a-f0-9]{40}\.[a-z0-9]+$/', (string) $filename), 'Unsafe stored filename: ' . $filename);
}

$accounts = $pdo->query('SELECT email, password_hash FROM users')->fetchAll();
foreach ($accounts as $account) {
    $password = str_starts_with($account['email'], 'instructor') ? 'Instructor123!' : 'Student123!';
    assert_database(password_verify($password, $account['password_hash']), 'Password verification failed for ' . $account['email']);
}

echo "A1 regression database assertions passed with the two A2 support tables.\n";
