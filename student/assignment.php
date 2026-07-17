<?php
declare(strict_types=1);

// Show an assignment, membership limits, attempt history, and latest grade.
require_once __DIR__ . '/../includes/upload.php';

$user = require_role('student');
$id = positive_id($_GET['id'] ?? null) ?? not_found('Invalid assignment ID.');

// Load the assignment only when the student is actively enrolled.
$assignmentStatement = database()->prepare(
    'SELECT a.*, c.course_code, c.title AS course_title
     FROM assignments a
     JOIN courses c ON c.id = a.course_id
     JOIN enrolments e
       ON e.course_id = c.id
      AND e.student_id = :student
      AND e.status = :status
     WHERE a.id = :id
     LIMIT 1'
);
$assignmentStatement->execute([
    'student' => $user['id'],
    'status' => 'active',
    'id' => $id,
]);
$assignment = $assignmentStatement->fetch()
    ?: not_found('Assignment not found or not accessible.');

$membershipStatus = membership_status((int) $user['id']);
$errors = [];
$note = '';

if (is_post()) {
    // Validate the file before checking the deadline and membership limit again in PHP.
    verify_csrf();
    $note = clean_text($_POST['student_note'] ?? '');
    if (mb_strlen($note) > 3000) {
        $errors['student_note'] = 'Keep the note below 3,000 characters.';
    }

    $upload = null;
    if ($errors === []) {
        try {
            $upload = store_upload($_FILES['submission_file'] ?? [], 'submission');
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $errors['submission_file'] = $exception->getMessage();
        }
    }

    if ($errors === [] && $upload !== null) {
        $pdo = database();

        try {
            $pdo->beginTransaction();

            // Lock the assignment so the deadline cannot change during this attempt.
            $assignmentLock = $pdo->prepare(
                'SELECT a.deadline
                 FROM assignments a
                 JOIN enrolments e
                   ON e.course_id = a.course_id
                  AND e.student_id = :student
                  AND e.status = :status
                 WHERE a.id = :assignment
                 FOR UPDATE'
            );
            $assignmentLock->execute([
                'student' => $user['id'],
                'status' => 'active',
                'assignment' => $id,
            ]);
            $deadline = $assignmentLock->fetchColumn();

            if (!$deadline) {
                throw new RuntimeException('Assignment access changed.');
            }
            if (new DateTimeImmutable() > new DateTimeImmutable($deadline)) {
                throw new DomainException('The deadline has passed. New attempts are closed.');
            }

            // A missing membership record is always treated as non-member.
            $membershipLock = $pdo->prepare(
                'SELECT membership_status
                 FROM memberships
                 WHERE user_id = :student
                 FOR UPDATE'
            );
            $membershipLock->execute(['student' => $user['id']]);
            $lockedMembership = $membershipLock->fetchColumn() === 'Member'
                ? 'Member'
                : 'Non-member';

            // Lock all attempts before calculating the next attempt number.
            $historyStatement = $pdo->prepare(
                'SELECT id, attempt_number
                 FROM submissions
                 WHERE assignment_id = :assignment AND student_id = :student
                 FOR UPDATE'
            );
            $historyStatement->execute([
                'assignment' => $id,
                'student' => $user['id'],
            ]);
            $attempts = $historyStatement->fetchAll();
            $attemptCount = count($attempts);
            $resubmissionsUsed = max(0, $attemptCount - 1);

            // Stop a non-member after the original attempt and two resubmissions.
            if (
                $attemptCount > 0
                && $lockedMembership === 'Non-member'
                && $resubmissionsUsed >= 2
            ) {
                throw new DomainException('Resubmission limit reached.');
            }

            $nextAttempt = 1;
            foreach ($attempts as $attempt) {
                $nextAttempt = max($nextAttempt, (int) $attempt['attempt_number'] + 1);
            }

            // Mark the old attempt as history before inserting the new latest attempt.
            $clearLatest = $pdo->prepare(
                'UPDATE submissions
                 SET is_latest = 0
                 WHERE assignment_id = :assignment
                   AND student_id = :student
                   AND is_latest = 1'
            );
            $clearLatest->execute([
                'assignment' => $id,
                'student' => $user['id'],
            ]);

            $insert = $pdo->prepare(
                'INSERT INTO submissions
                    (assignment_id, student_id, attempt_number, stored_filename,
                     original_filename, mime_type, file_size, student_note, is_latest)
                 VALUES
                    (:assignment, :student, :attempt, :stored,
                     :original, :mime, :size, :note, 1)'
            );
            $insert->execute([
                'assignment' => $id,
                'student' => $user['id'],
                'attempt' => $nextAttempt,
                'stored' => $upload['stored_filename'],
                'original' => $upload['original_filename'],
                'mime' => $upload['mime_type'],
                'size' => $upload['file_size'],
                'note' => $note !== '' ? $note : null,
            ]);

            $pdo->commit();
            flash('success', 'Submission attempt ' . $nextAttempt . ' received.');
            redirect('student/assignment.php?id=' . $id);
        } catch (DomainException $exception) {
            // Roll back rejected attempts and remove the unused uploaded file.
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            @unlink($upload['path']);
            $errors['submission_file'] = $exception->getMessage();
        } catch (Throwable $exception) {
            // Roll back unexpected failures before returning the error.
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            @unlink($upload['path']);
            throw $exception;
        }
    }
}

// Load every attempt and calculate the current membership allowance.
$submissionStatement = database()->prepare(
    'SELECT s.*, g.grade_value, g.feedback, g.graded_at
     FROM submissions s
     LEFT JOIN grades g ON g.submission_id = s.id
     WHERE s.assignment_id = :assignment AND s.student_id = :student
     ORDER BY s.attempt_number DESC'
);
$submissionStatement->execute([
    'assignment' => $id,
    'student' => $user['id'],
]);
$submissions = $submissionStatement->fetchAll();
$latest = $submissions[0] ?? null;
$attemptCount = count($submissions);
$currentAttempt = $latest ? (int) $latest['attempt_number'] : 0;
$resubmissionsUsed = max(0, $attemptCount - 1);
$remainingResubmissions = $membershipStatus === 'Member'
    ? null
    : max(0, 2 - $resubmissionsUsed);
$isOpen = new DateTimeImmutable() < new DateTimeImmutable($assignment['deadline']);
$canSubmit = $isOpen
    && (
        $attemptCount === 0
        || $membershipStatus === 'Member'
        || $remainingResubmissions > 0
    );

$pageTitle = $assignment['title'];
$activePage = 'my-courses';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow"><?= e($assignment['course_code']) ?> assignment</p>
                <h1><?= e($assignment['title']) ?></h1>
            </div>
            <a
                class="btn btn-outline-ink"
                href="<?= e(url('student/course.php?id=' . $assignment['course_id'])) ?>"
            >Back to course</a>
        </div>

        <div class="resubmission-status mb-4">
            <div>
                <span>Membership</span>
                <strong><?= e($membershipStatus) ?></strong>
            </div>
            <div>
                <span>Current attempt</span>
                <strong><?= $currentAttempt === 0 ? 'Not submitted' : $currentAttempt ?></strong>
            </div>
            <div>
                <span>Resubmissions used</span>
                <strong>
                    <?= $membershipStatus === 'Member'
                        ? $resubmissionsUsed
                        : $resubmissionsUsed . ' of 2' ?>
                </strong>
            </div>
            <div>
                <span>Remaining</span>
                <strong><?= $membershipStatus === 'Member' ? 'Unlimited' : $remainingResubmissions ?></strong>
            </div>
            <div>
                <span>Another attempt</span>
                <strong><?= $canSubmit ? 'Allowed' : 'Not allowed' ?></strong>
            </div>
            <div>
                <span>Deadline</span>
                <strong><?= e(format_datetime($assignment['deadline'])) ?></strong>
            </div>
        </div>

        <?php if ($membershipStatus === 'Member'): ?>
            <div class="membership-notice member-notice">
                Membership: Member &middot; Resubmissions: Unlimited before deadline
            </div>
        <?php elseif ($attemptCount > 0): ?>
            <div class="membership-notice">
                Membership: Non-member
                &middot; Resubmissions used: <?= $resubmissionsUsed ?> of 2
                &middot; Remaining: <?= $remainingResubmissions ?>
            </div>
        <?php else: ?>
            <div class="membership-notice">
                Membership: Non-member &middot; Resubmissions used: 0 of 2 &middot; Remaining: 2
            </div>
        <?php endif; ?>

        <div class="row g-4 mt-1">
            <div class="col-lg-7">
                <div class="content-panel">
                    <div class="deadline-banner <?= $isOpen ? 'open' : 'closed' ?>">
                        <span><?= $isOpen ? 'Submissions open' : 'Submissions closed' ?></span>
                        <strong>Deadline &middot; <?= e(format_datetime($assignment['deadline'])) ?></strong>
                    </div>
                    <h2 class="mt-4">Instructions</h2>
                    <p class="preserve-lines"><?= nl2br(e($assignment['instructions'])) ?></p>
                    <p><strong>Maximum grade:</strong> <?= e($assignment['max_grade']) ?></p>
                </div>

                <div class="content-panel mt-4">
                    <div class="panel-heading">
                        <h2>Attempt history</h2>
                        <span class="count-pill"><?= count($submissions) ?></span>
                    </div>

                    <?php if ($submissions === []): ?>
                        <p class="text-muted">No submission yet.</p>
                    <?php else: ?>
                        <div class="timeline-list">
                            <?php foreach ($submissions as $submission): ?>
                                <article class="timeline-item <?= $submission['is_latest'] ? 'latest' : '' ?>">
                                    <span>Attempt <?= $submission['attempt_number'] ?></span>
                                    <div>
                                        <strong><?= e($submission['original_filename']) ?></strong>
                                        <small>
                                            <?= e(format_datetime($submission['submitted_at'])) ?>
                                            <?= $submission['is_latest'] ? ' - Latest attempt' : '' ?>
                                        </small>
                                        <?php if ($submission['student_note']): ?>
                                            <p><?= e($submission['student_note']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?= e(url('download.php?type=submission&id=' . $submission['id'])) ?>">
                                        Download
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="content-panel">
                    <p class="eyebrow"><?= $latest ? 'Resubmit' : 'Submit assignment' ?></p>

                    <?php if (!$isOpen): ?>
                        <p class="text-muted">
                            The deadline has passed. Submission and resubmission are disabled.
                        </p>
                    <?php elseif (!$canSubmit): ?>
                        <div class="alert alert-warning" role="alert">
                            <strong>Resubmission limit reached</strong><br>
                            Non-members can make only two resubmissions.
                        </div>
                    <?php else: ?>
                        <form method="post" enctype="multipart/form-data">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label class="form-label" for="submission_file">Submission file</label>
                                <input
                                    class="form-control <?= isset($errors['submission_file']) ? 'is-invalid' : '' ?>"
                                    type="file"
                                    id="submission_file"
                                    name="submission_file"
                                    accept=".pdf,.doc,.docx,.txt,.zip"
                                    required
                                >
                                <?php if (isset($errors['submission_file'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['submission_file']) ?></div>
                                <?php endif; ?>
                                <div class="form-hint">
                                    PDF, Word, text, or ZIP &middot; maximum 20 MB.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="student_note">Note (optional)</label>
                                <textarea
                                    class="form-control"
                                    id="student_note"
                                    name="student_note"
                                ><?= e($note) ?></textarea>
                            </div>

                            <button class="btn btn-sun w-100" type="submit">
                                <?= $latest ? 'Submit new attempt' : 'Submit assignment' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if ($latest): ?>
                    <div class="content-panel mt-4 grade-card">
                        <p class="eyebrow">Latest submission status</p>
                        <h2>
                            <?= $latest['grade_value'] !== null
                                ? e($latest['grade_value']) . ' / ' . e($assignment['max_grade'])
                                : 'Awaiting grade' ?>
                        </h2>
                        <p>
                            <strong>Submitted:</strong>
                            <?= e(format_datetime($latest['submitted_at'])) ?>
                        </p>
                        <?php if ($latest['feedback'] !== null): ?>
                            <div class="feedback-box">
                                <strong>Instructor feedback</strong>
                                <p><?= nl2br(e($latest['feedback'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
