# Progress

## 2026-07-17

- Inspected the empty workspace.
- Read the `frontend-design` and `planning-with-files` skill instructions.
- Presented the proposed folder structure, schema, relationships, main page flow, and assumptions before implementation.
- Created the final application directory structure.
- Began the database foundation phase.
- Created the complete ten-table MySQL schema with indexes, constraints, and foreign keys.
- Added the application configuration and reusable PDO connection with native prepared statements.
- Began the shared layout and homepage phase.
- Added session-aware shared header and footer includes.
- Built the responsive public homepage using semantic HTML5 and Bootstrap 5.
- Added an original warm academic/editorial CSS system and lightweight progressive JavaScript enhancements.
- Began setup documentation and verification.
- PHP lint passed for all five PHP files; the local MySQL service responded successfully.
- Browser testing identified and corrected a mobile navbar overflow caused by Bootstrap's default brand whitespace rule.
- Replaced the hard-coded application URL with automatic document-root detection after browser testing exposed missing assets outside the expected XAMPP subfolder.
- Removed the oversized decorative hero orbit on small screens to keep the document within the viewport.
- Removed the off-canvas course preview accent on small screens after confirming all content elements fit the viewport.
- Added document-level horizontal clipping for decorative shadows only; DOM bounds verification showed no hidden or overflowing content.
- Visually inspected the desktop capture and improved the smoke test to exercise scroll-reveal content and avoid XAMPP CLI-only session path warnings.
- Removed premature session startup from the public layout after visual QA; secure sessions remain explicitly assigned to the later authentication bootstrap.
- Final Playwright smoke test passed at desktop and mobile widths with zero browser console errors.
- Visually verified the complete homepage without PHP warnings or hidden sections.
- Imported `database.sql` into a temporary MySQL database successfully: 10 tables and 17 foreign keys; removed the temporary database afterward.
- Final PHP lint, JavaScript syntax, and ten-table declaration checks passed.
- Removed temporary browser-test artifacts; only Stage 1 project and planning files remain.
- Confirmed the workspace is not currently a Git repository, so no diff or commit was produced.

## Stages 2–5 continuation

- Re-audited the Stage 1 source, schema, documentation, and planning records.
- Confirmed the ten existing tables cover the remaining original A1 scope.
- Began Stage 2 authentication and role access.
- Added secure session bootstrap, authentication helpers, CSRF tokens, flash messages, ID and text validation helpers, and role guards.
- Added student registration, shared login, POST-only logout, and deterministic local test accounts using password hashes.
- Added protected student and instructor dashboards with role-specific statistics and navigation.
- Extended the academic/editorial design system to cover authentication and application pages responsively.
- Stage 2 automated gate passed registration, password verification, session regeneration, CSRF rejection, logout, cross-role 403 responses, and desktop/mobile rendering.
- Confirmed the registered password is stored as a bcrypt hash and the account role is `student`.
- Per user direction, consolidated all remaining implementation before the next verification pass.
- Implemented instructor course creation, owned-course listing, enrolled-student viewing, and ownership enforcement.
- Implemented student course browsing, course details, enrolment, duplicate prevention, and enrolled-course listing.
- Implemented validated material uploads, protected material downloads, assignment creation/editing, and assignment listings.
- Implemented transactional submission/resubmission attempts, PHP deadline enforcement, status, dates, latest markers, and full attempt history.
- Implemented protected submission downloads, grading and feedback upserts, student latest-grade display, announcements, restricted discussions, replies, and participation monitoring.
- Extended responsive application styling and progressive form behavior for all new pages.
- Consolidated PHP syntax checks passed for every application and PHP test file; JavaScript syntax passed with Node.
- Full Playwright workflow passed course creation, enrolment, invalid and valid uploads, protected downloads, assignment editing, two submission attempts, deadline denial, grading updates, announcements, discussions, monitoring, role ownership, and mobile rendering.
- Database assertions passed exact ten-table workflow counts, duplicate-enrolment prevention, one-latest-attempt invariant, zero late submissions, updated grade/feedback, safe filenames, and password verification.
- Visual QA passed the instructor desktop course workspace and student 390px mobile course page.
- Security scan found CSRF verification in all 11 POST handlers and no direct upload-file links.
- Reset the local database to a clean four-account seed state and removed generated upload/test screenshot files.
- Replaced the Stage 1 README with the final A1 function list, limitations, accounts, tables, flow, checklist, test commands, and XAMPP instructions.

## A2 continuation

- Read the A2 objective and confirmed only enhanced student profiles and membership-based resubmission are in scope.
- Began the A2 database and migration work while preserving all A1 functions.
- Added the `student_profiles` and `memberships` tables to the fresh schema and created an A1-to-A2 migration.
- Added automatic Non-member creation for new registrations and a safe Non-member fallback for missing records.
- Implemented student profile view/edit, Online or Offline validation, optional availability, CSRF protection, and protected role/membership fields.
- Implemented instructor profile viewing restricted to enrolled students in owned courses.
- Implemented PHP-enforced unlimited Member resubmissions and the Non-member two-resubmission limit inside the submission transaction.
- Added membership, attempt, remaining-resubmission, permission, and deadline status to the assignment page.
- Added A2 Playwright and database assertion suites covering every required A2 case.
- Fresh schema import, migration verification, PHP lint, JavaScript syntax, A2 browser/database tests, A1 regression tests, desktop layout, and 390px mobile layout passed.
- Replaced the off-screen radio-control positioning after mobile testing found hidden elements outside the viewport.
- Reset the database to the clean four-account A2 seed state and removed generated upload/test-cache files.
- Synchronized 50 source files to both hyphenated project copies with zero hash mismatches.
- Verified the Apache homepage and login return 200 and the protected profile redirects guests to login.
