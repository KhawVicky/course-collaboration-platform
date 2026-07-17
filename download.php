<?php
declare(strict_types=1);

// Check file permissions before sending any stored file.
require_once __DIR__ . '/includes/auth.php';

$user = require_login();
$id = positive_id($_GET['id'] ?? null) ?? not_found('Invalid file ID.');
$type = clean_text($_GET['type'] ?? '');

if ($type === 'material') {
    // Load a course material and confirm course access.
    $statement = database()->prepare(
        'SELECT m.*, c.instructor_id
         FROM course_materials m
         JOIN courses c ON c.id = m.course_id
         WHERE m.id = :id'
    );
    $statement->execute(['id' => $id]);
    $file = $statement->fetch() ?: not_found('Material not found.');
    require_course_access((int) $file['course_id'], $user);
    $folder = 'materials';
} elseif ($type === 'submission') {
    // Load a submission and allow only its student or course owner.
    $statement = database()->prepare(
        'SELECT s.*, a.course_id, c.instructor_id
         FROM submissions s
         JOIN assignments a ON a.id = s.assignment_id
         JOIN courses c ON c.id = a.course_id
         WHERE s.id = :id'
    );
    $statement->execute(['id' => $id]);
    $file = $statement->fetch() ?: not_found('Submission not found.');

    if ($user['role'] === 'student' && (int) $file['student_id'] !== (int) $user['id']) {
        http_response_code(403);
        exit('You cannot access another student\'s submission.');
    }

    if ($user['role'] === 'instructor' && (int) $file['instructor_id'] !== (int) $user['id']) {
        http_response_code(403);
        exit('You do not own this submission\'s course.');
    }

    $folder = 'submissions';
} else {
    not_found('Unknown download type.');
}

// Stop when the database file no longer exists on disk.
$path = __DIR__ . '/uploads/' . $folder . '/' . $file['stored_filename'];
if (!is_file($path)) {
    not_found('The stored file is unavailable.');
}

// Send safe download headers and stream the file.
header('Content-Type: ' . $file['mime_type']);
header('Content-Length: ' . filesize($path));
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($file['original_filename']));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
