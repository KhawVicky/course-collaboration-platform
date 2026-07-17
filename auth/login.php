<?php
declare(strict_types=1);

// Sign students and instructors into the correct dashboard.

require_once __DIR__ . '/../includes/auth.php';
require_guest();

$error = '';
$email = '';

// Check the submitted email and password.
if (is_post()) {
    verify_csrf();
    $email = strtolower(clean_text($_POST['email'] ?? ''));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email address and password.';
    } else {
        $statement = database()->prepare('SELECT id, full_name, email, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || !(bool) $user['is_active'] || !password_verify($password, $user['password_hash'])) {
            $error = 'The email or password is incorrect.';
        } else {
            login_user($user);
            flash('success', 'Welcome back, ' . $user['full_name'] . '.');
            redirect(dashboard_path($user));
        }
    }
}

$pageTitle = 'Sign in';
$activePage = 'login';
require __DIR__ . '/../includes/header.php';
?>
<section class="auth-section">
    <div class="container">
        <div class="row g-0 auth-frame auth-frame-login">
            <div class="col-lg-5 auth-story">
                <p class="eyebrow eyebrow-light">Welcome back</p>
                <h1>Return to the work that matters.</h1>
                <p>One sign-in serves both students and instructors. Your role determines the workspace you enter.</p>
            </div>
            <div class="col-lg-7 auth-form-panel">
                <h2>Sign in</h2>
                <p class="form-intro">New student? <a href="<?= e(url('auth/register.php')) ?>">Create an account</a>.</p>
                <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-sun w-100" type="submit">Sign in securely</button>
                </form>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
