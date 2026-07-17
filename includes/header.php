<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Prepare shared page data and navigation for the current user.

$pageTitle = $pageTitle ?? 'Learn together';
$activePage = $activePage ?? 'home';
$authenticatedUser = current_user();
$flashes = pull_flashes();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A focused space for courses, assignments, learning materials, and academic discussion.">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&amp;family=Newsreader:opsz,wght@6..72,500;6..72,600&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container py-2">
            <a class="navbar-brand brand-lockup" href="<?= e(url()) ?>" aria-label="Course Collaboration Platform home">
                <span class="brand-mark" aria-hidden="true">CC</span>
                <span>
                    <strong>Course Collaboration</strong>
                    <small>Learn · Share · Progress</small>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" <?= $activePage === 'home' ? 'aria-current="page"' : '' ?> href="<?= e(url()) ?>">Home</a>
                    </li>
                    <?php if ($authenticatedUser === null): ?>
                        <li class="nav-item"><a class="nav-link <?= $activePage === 'login' ? 'active' : '' ?>" href="<?= e(url('auth/login.php')) ?>">Sign in</a></li>
                        <li class="nav-item ms-lg-2"><a class="btn btn-ink" href="<?= e(url('auth/register.php')) ?>">Create account</a></li>
                    <?php elseif ($authenticatedUser['role'] === 'student'): ?>
                        <li class="nav-item"><a class="nav-link <?= $activePage === 'student-dashboard' ? 'active' : '' ?>" href="<?= e(url('student/dashboard.php')) ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link <?= $activePage === 'browse-courses' ? 'active' : '' ?>" href="<?= e(url('student/courses.php')) ?>">Browse courses</a></li>
                        <li class="nav-item"><a class="nav-link <?= $activePage === 'my-courses' ? 'active' : '' ?>" href="<?= e(url('student/enrolled.php')) ?>">My courses</a></li>
                        <li class="nav-item"><a class="nav-link <?= $activePage === 'student-profile' ? 'active' : '' ?>" href="<?= e(url('student/profile.php')) ?>">Profile</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link <?= $activePage === 'instructor-dashboard' ? 'active' : '' ?>" href="<?= e(url('instructor/dashboard.php')) ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link <?= $activePage === 'instructor-courses' ? 'active' : '' ?>" href="<?= e(url('instructor/courses.php')) ?>">My courses</a></li>
                    <?php endif; ?>
                    <?php if ($authenticatedUser !== null): ?>
                        <li class="nav-item dropdown ms-lg-2">
                            <button class="btn btn-ink dropdown-toggle" data-bs-toggle="dropdown" type="button" aria-expanded="false"><?= e($authenticatedUser['full_name']) ?></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><span class="dropdown-item-text role-label"><?= e(ucfirst($authenticatedUser['role'])) ?></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="post" action="<?= e(url('auth/logout.php')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="dropdown-item" type="submit">Sign out</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
<?php if ($flashes !== []): ?>
    <div class="flash-stack container" aria-live="polite">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= e(in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info') ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<main id="main-content">
