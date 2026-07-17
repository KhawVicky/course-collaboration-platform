<?php
declare(strict_types=1);

// Let a student view and edit only their own collaboration profile.
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('student');

$requestedUserId = positive_id($_GET['user_id'] ?? null);
if ($requestedUserId !== null && $requestedUserId !== (int) $user['id']) {
    http_response_code(403);
    exit('You cannot edit another student profile.');
}

$statement = database()->prepare(
    'SELECT u.full_name, u.email, sp.skills, sp.collaboration_mode, sp.availability
     FROM users u
     LEFT JOIN student_profiles sp ON sp.user_id = u.id
     WHERE u.id = :user_id AND u.role = :role LIMIT 1'
);
$statement->execute(['user_id' => $user['id'], 'role' => 'student']);
$profile = $statement->fetch() ?: not_found('Student profile not found.');
$membershipStatus = membership_status((int) $user['id']);
$errors = [];

if (is_post()) {
    // Validate and save the user's name and collaboration details.
    verify_csrf();
    $fullName = clean_text($_POST['full_name'] ?? '');
    $skills = clean_text($_POST['skills'] ?? '');
    $collaborationMode = clean_text($_POST['collaboration_mode'] ?? '');
    $availability = clean_text($_POST['availability'] ?? '');

    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
        $errors['full_name'] = 'Enter a name between 2 and 120 characters.';
    }
    if (mb_strlen($skills) > 2000) {
        $errors['skills'] = 'Keep skills below 2,000 characters.';
    }
    if (!in_array($collaborationMode, ['Online', 'Offline'], true)) {
        $errors['collaboration_mode'] = 'Choose Online or Offline.';
    }
    if (mb_strlen($availability) > 2000) {
        $errors['availability'] = 'Keep availability below 2,000 characters.';
    }

    if ($errors === []) {
        $pdo = database();
        try {
            $pdo->beginTransaction();
            $updateUser = $pdo->prepare('UPDATE users SET full_name = :full_name WHERE id = :user_id AND role = :role');
            $updateUser->execute(['full_name' => $fullName, 'user_id' => $user['id'], 'role' => 'student']);
            $updateProfile = $pdo->prepare(
                'INSERT INTO student_profiles (user_id, skills, collaboration_mode, availability)
                 VALUES (:user_id, :skills, :mode, :availability)
                 ON DUPLICATE KEY UPDATE skills = VALUES(skills), collaboration_mode = VALUES(collaboration_mode), availability = VALUES(availability)'
            );
            $updateProfile->execute([
                'user_id' => $user['id'],
                'skills' => $skills !== '' ? $skills : null,
                'mode' => $collaborationMode,
                'availability' => $availability !== '' ? $availability : null,
            ]);
            $pdo->commit();
            $_SESSION['user']['full_name'] = $fullName;
            flash('success', 'Your collaboration profile was updated.');
            redirect('student/profile.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    $profile = [
        'full_name' => $fullName,
        'email' => $profile['email'],
        'skills' => $skills,
        'collaboration_mode' => $collaborationMode,
        'availability' => $availability,
    ];
}

$pageTitle = 'Collaboration profile';
$activePage = 'student-profile';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-shell profile-shell">
    <div class="container">
        <div class="page-heading">
            <div><p class="eyebrow">Enhanced student profile</p><h1>Your collaboration profile.</h1></div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <aside class="membership-card <?= $membershipStatus === 'Member' ? 'is-member' : '' ?>">
                    <span class="membership-label">Membership status</span>
                    <strong><?= e($membershipStatus) ?></strong>
                    <p><?= $membershipStatus === 'Member' ? 'Unlimited assignment resubmissions before the deadline.' : 'Up to two assignment resubmissions before the deadline.' ?></p>
                    <small>Your role and membership cannot be changed here.</small>
                </aside>
            </div>
            <div class="col-lg-8">
                <div class="content-panel">
                    <h2 class="form-title">Profile details</h2>
                    <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="full_name">Student name</label><input class="form-control" id="full_name" name="full_name" value="<?= e($profile['full_name']) ?>" maxlength="120" required></div>
                            <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" value="<?= e($profile['email']) ?>" readonly></div>
                        </div>
                        <div class="mt-3"><label class="form-label" for="skills">Skills or expertise</label><textarea class="form-control" id="skills" name="skills" maxlength="2000" placeholder="Example: PHP, UI design, research, presentation"><?= e($profile['skills']) ?></textarea></div>
                        <fieldset class="mt-3"><legend class="form-label">Preferred collaboration mode</legend><div class="mode-options"><label><input type="radio" name="collaboration_mode" value="Online" <?= $profile['collaboration_mode'] === 'Online' ? 'checked' : '' ?> required><span>Online</span></label><label><input type="radio" name="collaboration_mode" value="Offline" <?= $profile['collaboration_mode'] === 'Offline' ? 'checked' : '' ?> required><span>Offline</span></label></div></fieldset>
                        <div class="mt-3"><label class="form-label" for="availability">Availability (optional)</label><textarea class="form-control" id="availability" name="availability" maxlength="2000" placeholder="Example: Monday 7–9 PM, Saturday morning"><?= e($profile['availability']) ?></textarea></div>
                        <button class="btn btn-sun mt-4" type="submit">Save profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

