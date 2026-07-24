<?php
declare(strict_types=1);

// Show the public platform homepage.
// Send signed-in users to their role dashboard.
require_once __DIR__ . '/includes/auth.php';
$user = current_user();

if ($user !== null) {
    redirect(dashboard_path($user));
}

$pageTitle = 'Learn together';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero-section overflow-hidden">
    <div class="container position-relative">
        <div class="hero-orbit orbit-one" aria-hidden="true"></div>
        <div class="hero-orbit orbit-two" aria-hidden="true"></div>
        <div class="row align-items-center min-vh-hero gy-5">
            <div class="col-lg-7">
                <p class="eyebrow reveal-item">One campus. Every learning moment.</p>
                <h1 class="display-title reveal-item delay-1">A course space built for <em>shared progress.</em></h1>
                <p class="hero-copy reveal-item delay-2">Bring materials, assignments, feedback, announcements, and thoughtful discussion into one calm, connected place.</p>
                <div class="d-flex flex-wrap gap-3 reveal-item delay-3">
                    <a class="btn btn-sun btn-lg" href="<?= e(url('auth/register.php')) ?>">Create student account <span aria-hidden="true">&rarr;</span></a>
                    <a class="btn btn-outline-ink btn-lg" href="<?= e(url('auth/login.php')) ?>">Sign in</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="course-window reveal-item delay-2" aria-label="Course dashboard preview">
                    <div class="window-topbar">
                        <span></span><span></span><span></span>
                        <small>MY LEARNING</small>
                    </div>
                    <div class="window-body">
                        <div class="course-label">CURRENT COURSE</div>
                        <h2>Human&ndash;Computer Interaction</h2>
                        <p>Designing useful systems through observation, iteration, and care.</p>
                        <div class="progress-meta">
                            <span>Weekly progress</span><strong>72%</strong>
                        </div>
                        <div class="progress course-progress" role="progressbar" aria-label="Example course progress" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 72%"></div>
                        </div>
                        <div class="next-session">
                            <div class="date-tile"><strong>18</strong><span>JUL</span></div>
                            <div><small>NEXT MILESTONE</small><strong>Prototype reflection</strong></div>
                            <span class="arrow-circle" aria-hidden="true">&#8599;</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="principles-strip" aria-label="Platform principles">
    <div class="container">
        <div class="row g-0">
            <div class="col-md-4 principle"><span>01</span><strong>Focused learning</strong></div>
            <div class="col-md-4 principle"><span>02</span><strong>Visible progress</strong></div>
            <div class="col-md-4 principle"><span>03</span><strong>Open dialogue</strong></div>
        </div>
    </div>
</section>

<section class="section-space" id="platform-preview">
    <div class="container">
        <div class="row align-items-end mb-5 gy-3">
            <div class="col-lg-7">
                <p class="eyebrow">Designed around the work</p>
                <h2 class="section-title">Everything a course needs.<br>Nothing it doesn&rsquo;t.</h2>
            </div>
            <div class="col-lg-5">
                <p class="section-intro mb-0">Students stay oriented. Instructors keep course activity moving. Both meet in a space made for useful exchange.</p>
            </div>
        </div>
        <div class="row g-4 feature-grid">
            <div class="col-md-6 col-lg-4">
                <article class="feature-card feature-card-dark h-100 reveal-on-scroll">
                    <span class="feature-number">A</span>
                    <div class="feature-icon" aria-hidden="true">&#9636;</div>
                    <h3>Course materials</h3>
                    <p>Organised resources stay close to the course and ready to download.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card feature-card-sun h-100 reveal-on-scroll">
                    <span class="feature-number">B</span>
                    <div class="feature-icon" aria-hidden="true">&check;</div>
                    <h3>Assignments & feedback</h3>
                    <p>Clear deadlines, tracked attempts, submission status, grades, and feedback.</p>
                </article>
            </div>
            <div class="col-md-12 col-lg-4">
                <article class="feature-card feature-card-paper h-100 reveal-on-scroll">
                    <span class="feature-number">C</span>
                    <div class="feature-icon" aria-hidden="true">&#8627;</div>
                    <h3>Course conversation</h3>
                    <p>Announcements and threaded discussion keep the learning community connected.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="stage-section" id="stage-one">
    <div class="container">
        <div class="stage-panel reveal-on-scroll">
            <div class="row align-items-center gy-4">
                <div class="col-lg-7">
                    <p class="eyebrow eyebrow-light">Platform foundation</p>
                    <h2>A dependable place for every course moment.</h2>
                    <p>Secure role-based workspaces connect enrolment, materials, assignments, feedback, announcements, and course conversations.</p>
                </div>
                <div class="col-lg-5">
                    <ul class="foundation-list list-unstyled mb-0">
                        <li><span>01</span> Student learning workspace</li>
                        <li><span>02</span> Instructor teaching workspace</li>
                        <li><span>03</span> Secure course collaboration</li>
                        <li><span>04</span> Clear progress and feedback</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
