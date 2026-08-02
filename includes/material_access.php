<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Parse a datetime-local form value using the application's local timezone.
function parse_material_datetime(mixed $value): ?DateTimeImmutable
{
    $raw = clean_text($value);
    if ($raw === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $raw);
    $errors = DateTimeImmutable::getLastErrors();
    if (
        $date === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
    ) {
        return null;
    }

    return $date;
}

// Store a parsed local date in the DATETIME format used by course_materials.
function material_datetime_for_database(DateTimeImmutable $date): string
{
    return $date->format('Y-m-d H:i:s');
}

// Fill a datetime-local input from a course_materials DATETIME value.
function material_datetime_local_value(?string $value): string
{
    $raw = clean_text($value);
    if ($raw === '') {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $raw);
    $errors = DateTimeImmutable::getLastErrors();
    if (
        $date === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
    ) {
        return '';
    }

    return $date->format('Y-m-d\TH:i');
}

// Parse a stored access time before applying the access policy.
function material_datetime_from_database(?string $value): ?DateTimeImmutable
{
    $raw = clean_text($value);
    if ($raw === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $raw);
    $errors = DateTimeImmutable::getLastErrors();
    if (
        $date === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
    ) {
        return null;
    }

    return $date;
}

// Confirm active enrolment for a student before checking the schedule.
function material_student_is_enrolled(int $courseId, int $studentId): bool
{
    $statement = database()->prepare(
        'SELECT COUNT(*)
         FROM enrolments
         WHERE course_id = :course_id AND student_id = :student_id AND status = :status'
    );
    $statement->execute([
        'course_id' => $courseId,
        'student_id' => $studentId,
        'status' => 'active',
    ]);

    return (int) $statement->fetchColumn() > 0;
}

// Return the shared course-material access decision for page rendering and downloads.
function material_access_state(
    array $material,
    array $user,
    ?string $knownMembershipStatus = null,
    ?bool $knownEnrollment = null
): array {
    $denied = static function (string $status, string $message, string $denialMessage = ''): array {
        return [
            'allowed' => false,
            'status' => $status,
            'message' => $message,
            'denial_message' => $denialMessage !== '' ? $denialMessage : $message,
        ];
    };

    $role = (string) ($user['role'] ?? '');
    $userId = (int) ($user['id'] ?? 0);
    $courseId = (int) ($material['course_id'] ?? 0);

    if ($role === 'instructor') {
        if ((int) ($material['instructor_id'] ?? 0) !== $userId) {
            return $denied('denied', 'You do not own this material\'s course.', 'You do not own this material\'s course.');
        }

        return [
            'allowed' => true,
            'status' => 'instructor',
            'message' => 'Available to you.',
            'denial_message' => '',
        ];
    }

    if ($role !== 'student' || $courseId < 1) {
        return $denied('denied', 'You cannot access this material.');
    }

    $isEnrolled = $knownEnrollment ?? material_student_is_enrolled($courseId, $userId);
    if (!$isEnrolled) {
        return $denied('denied', 'You must be enrolled in this course to access this material.');
    }

    $membershipStatus = $knownMembershipStatus ?? membership_status($userId);
    $memberAccessAt = material_datetime_from_database((string) ($material['member_access_at'] ?? ''));
    $publicAccessAt = material_datetime_from_database((string) ($material['public_access_at'] ?? ''));
    if ($memberAccessAt === null || $publicAccessAt === null || $publicAccessAt < $memberAccessAt) {
        return $denied('denied', 'This material has an invalid access schedule.');
    }

    $now = new DateTimeImmutable('now');
    if ($now < $memberAccessAt) {
        return $denied('not_available', 'Not available yet.', 'This material is not available yet.');
    }

    if ($now < $publicAccessAt && $membershipStatus !== 'Member') {
        $message = 'Available to members first. Public access starts on '
            . format_datetime((string) $material['public_access_at']) . '.';
        return $denied('member_priority', $message, 'This material is currently available to Members only.');
    }

    if ($now < $publicAccessAt) {
        return [
            'allowed' => true,
            'status' => 'member_priority',
            'message' => 'Available to members first.',
            'denial_message' => '',
        ];
    }

    return [
        'allowed' => true,
        'status' => 'public',
        'message' => 'Available to all enrolled students.',
        'denial_message' => '',
    ];
}

// Enforce the same schedule, membership, enrolment, and ownership rules for downloads.
function require_material_access(array $material, array $user): void
{
    $state = material_access_state($material, $user);
    if (!$state['allowed']) {
        http_response_code(403);
        exit((string) $state['denial_message']);
    }
}
