<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Send the user to another application page.
function redirect(string $path = ''): never
{
    header('Location: ' . url($path));
    exit;
}

// Check whether the current request uses POST.
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// Return the signed-in user stored in the session.
function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

// Return the signed-in user ID.
function user_id(): ?int
{
    $user = current_user();
    return $user === null ? null : (int) $user['id'];
}

// Save the user in the session and create a new session ID.
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
}

// Clear the current login session.
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parameters = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $parameters['path'], $parameters['domain'], $parameters['secure'], $parameters['httponly']);
    }

    session_destroy();
}

// Keep signed-in users away from guest-only pages.
function require_guest(): void
{
    $user = current_user();
    if ($user !== null) {
        redirect($user['role'] === 'instructor' ? 'instructor/dashboard.php' : 'student/dashboard.php');
    }
}

// Require a signed-in user for the current page.
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        flash('warning', 'Please sign in to continue.');
        redirect('auth/login.php');
    }

    return $user;
}

// Require one specific account role.
function require_role(string $role): array
{
    $user = require_login();
    if (($user['role'] ?? '') !== $role) {
        http_response_code(403);
        require __DIR__ . '/forbidden.php';
        exit;
    }

    return $user;
}

// Create or return the session CSRF token.
function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Create the hidden CSRF form field.
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Reject a POST request with an invalid CSRF token.
function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('The form session expired. Please go back, refresh the page, and try again.');
    }
}

// Save a short message for the next page.
function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

// Read and remove all saved messages.
function pull_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return is_array($flashes) ? $flashes : [];
}

// Accept only a positive integer ID.
function positive_id(mixed $value): ?int
{
    $validated = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $validated === false ? null : (int) $validated;
}

// Trim text received from a form.
function clean_text(mixed $value): string
{
    return trim(is_string($value) ? $value : '');
}

// Return the correct dashboard for a role.
function dashboard_path(array $user): string
{
    return ($user['role'] ?? '') === 'instructor' ? 'instructor/dashboard.php' : 'student/dashboard.php';
}

// Show a database date in a readable format.
function format_datetime(?string $value, string $fallback = 'Not available'): string
{
    if ($value === null || $value === '') {
        return $fallback;
    }

    $timestamp = strtotime($value);
    return $timestamp === false ? $fallback : date('d M Y, g:i A', $timestamp);
}

// Stop the request when a record cannot be found.
function not_found(string $message = 'The requested record was not found.'): never
{
    http_response_code(404);
    exit(e($message));
}

// Load a course only when it belongs to this instructor.
function owned_course(int $courseId, int $instructorId): array
{
    $statement = database()->prepare(
        'SELECT c.*, u.full_name AS instructor_name
         FROM courses c JOIN users u ON u.id = c.instructor_id
         WHERE c.id = :course_id AND c.instructor_id = :instructor_id LIMIT 1'
    );
    $statement->execute(['course_id' => $courseId, 'instructor_id' => $instructorId]);
    $course = $statement->fetch();
    if (!$course) {
        not_found('Course not found or not owned by this instructor.');
    }
    return $course;
}

// Load a course only when this student is enrolled.
function enrolled_course(int $courseId, int $studentId): array
{
    $statement = database()->prepare(
        'SELECT c.*, u.full_name AS instructor_name
         FROM courses c
         JOIN users u ON u.id = c.instructor_id
         JOIN enrolments e ON e.course_id = c.id AND e.student_id = :student_id AND e.status = :status
         WHERE c.id = :course_id LIMIT 1'
    );
    $statement->execute(['student_id' => $studentId, 'status' => 'active', 'course_id' => $courseId]);
    $course = $statement->fetch();
    if (!$course) {
        not_found('Course not found or you are not enrolled.');
    }
    return $course;
}

// Check whether the user may open a course.
function can_access_course(int $courseId, array $user): bool
{
    if ($user['role'] === 'instructor') {
        $statement = database()->prepare('SELECT COUNT(*) FROM courses WHERE id = :course_id AND instructor_id = :user_id');
    } else {
        $statement = database()->prepare(
            'SELECT COUNT(*) FROM enrolments WHERE course_id = :course_id AND student_id = :user_id AND status = :status'
        );
    }
    $parameters = ['course_id' => $courseId, 'user_id' => $user['id']];
    if ($user['role'] === 'student') {
        $parameters['status'] = 'active';
    }
    $statement->execute($parameters);
    return (int) $statement->fetchColumn() > 0;
}

// Stop users who cannot access the course.
function require_course_access(int $courseId, array $user): void
{
    if (!can_access_course($courseId, $user)) {
        http_response_code(403);
        exit('You do not have access to this course.');
    }
}

// Return a student's membership or the non-member default.
function membership_status(int $studentId): string
{
    $statement = database()->prepare(
        'SELECT membership_status FROM memberships WHERE user_id = :student_id LIMIT 1'
    );
    $statement->execute(['student_id' => $studentId]);
    $status = $statement->fetchColumn();
    return $status === 'Member' ? 'Member' : 'Non-member';
}
