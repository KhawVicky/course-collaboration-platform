<?php
declare(strict_types=1);

// Check A2 records created by the browser test.
require_once __DIR__ . '/../includes/auth.php';

// Stop the test when one database rule fails.
function assert_a2(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdo = database();

// Confirm the final records and the failed-attempt invariants.
$expectedCounts = [
    'users' => 5,
    'courses' => 1,
    'enrolments' => 2,
    'assignments' => 2,
    'memberships' => 3,
    'student_profiles' => 2,
    'submissions' => 7,
];
foreach ($expectedCounts as $table => $expected) {
    $actual = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    assert_a2($actual === $expected, "{$table}: expected {$expected}, received {$actual}");
}

$memberships = $pdo->query(
    'SELECT u.email, u.role, m.membership_status
     FROM users u LEFT JOIN memberships m ON m.user_id = u.id ORDER BY u.email'
)->fetchAll();
$byEmail = [];
foreach ($memberships as $membership) {
    $byEmail[$membership['email']] = $membership;
}
assert_a2($byEmail['student@example.com']['membership_status'] === 'Member', 'Member test student is incorrect.');
assert_a2($byEmail['student2@example.com']['membership_status'] === 'Non-member', 'Non-member test student is incorrect.');
assert_a2($byEmail['newa2@example.com']['membership_status'] === 'Non-member', 'New registration did not receive non-member status.');
assert_a2($byEmail['student2@example.com']['role'] === 'student', 'A student changed their protected role.');
assert_a2($byEmail['instructor@example.com']['membership_status'] === null, 'Instructor received a membership record.');
assert_a2($byEmail['instructor2@example.com']['membership_status'] === null, 'Second instructor received a membership record.');

$attempts = $pdo->query(
    "SELECT u.email, COUNT(*) AS total, MAX(s.attempt_number) AS highest, SUM(s.is_latest) AS latest_count
     FROM submissions s JOIN users u ON u.id = s.student_id
     JOIN assignments a ON a.id = s.assignment_id
     WHERE a.title = 'Future Collaboration Task'
     GROUP BY u.id, u.email"
)->fetchAll();
$attemptsByEmail = [];
foreach ($attempts as $attempt) {
    $attemptsByEmail[$attempt['email']] = $attempt;
}
assert_a2((int) $attemptsByEmail['student@example.com']['total'] === 4, 'Member must have four successful attempts.');
assert_a2((int) $attemptsByEmail['student@example.com']['highest'] === 4, 'Member attempt numbering is incorrect.');
assert_a2((int) $attemptsByEmail['student2@example.com']['total'] === 3, 'Rejected non-member attempt created a database record.');
assert_a2((int) $attemptsByEmail['student2@example.com']['highest'] === 3, 'Non-member fourth attempt was not rejected.');
assert_a2((int) $attemptsByEmail['student@example.com']['latest_count'] === 1, 'Member must have one latest attempt.');
assert_a2((int) $attemptsByEmail['student2@example.com']['latest_count'] === 1, 'Non-member must have one latest attempt.');

$lateAttempts = (int) $pdo->query(
    "SELECT COUNT(*) FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     WHERE a.title = 'Closed Collaboration Task'"
)->fetchColumn();
assert_a2($lateAttempts === 0, 'A rejected late submission created a database record.');

$profile = $pdo->query(
    "SELECT sp.skills, sp.collaboration_mode, sp.availability
     FROM student_profiles sp JOIN users u ON u.id = sp.user_id
     WHERE u.email = 'student@example.com'"
)->fetch();
assert_a2(str_contains((string) $profile['skills'], 'PHP'), 'Student skills were not saved.');
assert_a2($profile['collaboration_mode'] === 'Offline', 'The final collaboration mode was not saved.');
assert_a2(str_contains((string) $profile['availability'], 'Monday'), 'Student availability was not saved.');

// Prove the PHP fallback for an existing student with no membership row.
$pdo->beginTransaction();
$temporary = $pdo->prepare(
    'INSERT INTO users (full_name, email, password_hash, role) VALUES (:name, :email, :password, :role)'
);
$temporary->execute([
    'name' => 'Missing Membership Test',
    'email' => 'missing-membership@example.com',
    'password' => password_hash('Student123!', PASSWORD_DEFAULT),
    'role' => 'student',
]);
$temporaryId = (int) $pdo->lastInsertId();
assert_a2(membership_status($temporaryId) === 'Non-member', 'Missing membership was not treated as non-member.');
$pdo->rollBack();

echo "A2 database assertions passed for membership, profiles, attempts, and deadline invariants.\n";
