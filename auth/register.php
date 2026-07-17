<?php
declare(strict_types=1);

// Register new student accounts only.

require_once __DIR__ . '/../includes/auth.php';
require_guest();

$errors = [];
$fullName = '';
$email = '';

// Validate the form and save the new password hash.
if (is_post()) {
    verify_csrf();
    $fullName = clean_text($_POST['full_name'] ?? '');
    $email = strtolower(clean_text($_POST['email'] ?? ''));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';

    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
        $errors['full_name'] = 'Enter a name between 2 and 120 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Use at least 8 characters with a letter and a number.';
    }
    if ($password !== $confirmation) {
        $errors['password_confirmation'] = 'The passwords do not match.';
    }

    if ($errors === []) {
        $pdo = database();
        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, :role)');
            $statement->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'student',
            ]);
            $studentId = (int) $pdo->lastInsertId();
            $membership = $pdo->prepare(
                'INSERT INTO memberships (user_id, membership_status) VALUES (:user_id, :status)'
            );
            $membership->execute(['user_id' => $studentId, 'status' => 'Non-member']);
            $pdo->commit();
            flash('success', 'Account created. Sign in to begin learning.');
            redirect('auth/login.php');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                $errors['email'] = 'An account already uses this email address.';
            } else {
                throw $exception;
            }
        }
    }
}

$pageTitle = 'Create student account';
$activePage = 'register';
require __DIR__ . '/../includes/header.php';
?>
<section class="auth-section">
    <div class="container">
        <div class="row g-0 auth-frame">
            <div class="col-lg-5 auth-story">
                <p class="eyebrow eyebrow-light">Student registration</p>
                <h1>Begin with one shared place to learn.</h1>
                <p>Create your student account to enrol in courses, submit work, receive feedback, and join discussions.</p>
                <span class="auth-note">Instructor accounts are created administratively.</span>
            </div>
            <div class="col-lg-7 auth-form-panel">
                <h2>Create your account</h2>
                <p class="form-intro">Already registered? <a href="<?= e(url('auth/login.php')) ?>">Sign in here</a>.</p>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="full_name">Full name</label>
                        <input class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name" value="<?= e($fullName) ?>" maxlength="120" autocomplete="name" required>
                        <?php if (isset($errors['full_name'])): ?><div class="invalid-feedback"><?= e($errors['full_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" type="email" id="email" name="email" value="<?= e($email) ?>" maxlength="190" autocomplete="email" required>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="password">Password</label>
                            <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" type="password" id="password" name="password" autocomplete="new-password" required>
                            <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">Confirm password</label>
                            <input class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                            <?php if (isset($errors['password_confirmation'])): ?><div class="invalid-feedback"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <p class="form-hint">Minimum 8 characters, including a letter and a number.</p>
                    <button class="btn btn-sun w-100 mt-2" type="submit">Create student account</button>
                </form>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
