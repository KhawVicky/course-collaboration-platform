# A1 + A2 Completion Plan

## Goal
Preserve the completed A1 platform and add only the A2 student profile and membership-based assignment resubmission functions.

## Scope
- Stage 1 foundation (complete)
- Stage 2 authentication, sessions, CSRF, validation, role guards, dashboards
- Stage 3 instructor course management and student enrolment
- Stage 4 materials, assignments, protected downloads, transactional submission attempts
- Stage 5 grading, announcements, discussions, and participation monitoring
- Final automated checks, documentation, accounts, flow, and manual checklist
- Setup documentation and verification

## Phases

| Phase | Work | Status |
|---|---|---|
| 1 | Inspect workspace and define architecture | Complete |
| 2 | Create schema and database connection | Complete |
| 3 | Create shared layout and homepage | Complete |
| 4 | Document and verify Stage 1 | Complete |
| 5 | Implement Stage 2 authentication and role access | Complete |
| 6 | Implement all remaining A1 course, content, submission, grading, communication, and monitoring functions | Complete |
| 7 | Run one consolidated syntax, database, responsive UI, role-access, and completion audit | Complete |
| 8 | Publish final A1 documentation and report | Complete |
| 9 | Add A2 profile and membership schema plus migration | Complete |
| 10 | Implement student/instructor profile views and membership resubmission rules | Complete |
| 11 | Test A2 cases and run A1 regression checks | Complete |
| 12 | Update documentation and deploy the verified A2 files | Complete |

## Decisions
- Public registration will create students only; instructor accounts are administrative.
- Submission attempts are separate rows with an attempt number and latest marker.
- Bootstrap 5 is loaded from CDN for Stage 1.
- The visual direction is warm academic/editorial with accessible, restrained motion.
- Shared PHP helpers will centralise authentication, CSRF, flash messages, ID validation, ownership checks, and upload validation.
- Public registration remains student-only; deterministic instructor and student test accounts will be seeded by `database.sql`.
- Uploaded files remain outside direct navigation and are served only by a permission-checked PHP download endpoint.
- A2 membership stays separate from user role and course enrolment.
- Missing membership records are treated as `Non-member` in PHP.
- A2 adds no payment, subscription, or priority material access.

## Errors Encountered

| Error | Attempt | Resolution |
|---|---:|---|
| Existing goal prevented creation of a duplicate goal | 1 | Retrieved and continued the active user-created goal |
| Combined inspection command returned exit code 1 because the empty workspace gave `rg` no matches | 1 | Inspected the directory and skill files with separate PowerShell commands |
| Playwright could not create its subprocess pipe inside the filesystem sandbox | 1 | Reran the local-only browser test with approved elevated execution |
| Mobile smoke test found horizontal overflow in Bootstrap's no-wrap navbar brand | 1 | Allowed the brand to wrap and tightened its mobile sizing |
| Responsive fix appeared ineffective in the built-in-server test | 2 | Found that the hard-coded XAMPP base URL caused CSS and JS 404s; replaced it with document-root-based URL detection |
| Decorative hero orbit extended the mobile document width after assets loaded correctly | 3 | Removed non-essential orbit decoration at small-screen width and retained the core layout |
| Combined patch omitted a file update marker | 1 | Corrected the patch boundaries and reapplied the focused edits |
| Mobile document remained wider although all DOM element bounds fit | 1 | Identified the off-canvas course-window pseudo-element and disabled that decoration on small screens |
| Second combined patch omitted a file update marker | 1 | Reapplied the edit with explicit file boundaries |
| Decorative shadows still contributed to document scroll width while all content bounds fit | 1 | Added page-level horizontal overflow clipping after verifying no content element exceeded the viewport |
| Visual screenshot showed CLI session-path warnings and unrevealed scroll sections | 1 | Configured a writable session path for the test server and made the smoke test scroll through reveal sections before capture |
| Public homepage emitted session warnings before authentication existed | 1 | Removed premature session startup; the authentication stage will own complete secure session initialization |
| Inline Windows environment setup was parsed as helper arguments | 1 | Added a narrow test-server batch wrapper that accepts the temporary database and port |
| Stage 2 registration did not reach the expected redirect | 1 | Added response diagnostics before changing implementation |
| CLI test server ignored the intended session path | 1 | Corrected PHP `-d` quoting in the dedicated test-server wrapper |
| XAMPP CLI continued using its php.ini session path | 2 | Added an explicit `SESSION_SAVE_PATH` application override used by the test wrapper and optional deployments |
| Test-helper batch environment was not inherited by PHP | 3 | Added a built-in-server fallback to `sys_get_temp_dir()` while leaving Apache configuration unchanged |
| PowerShell rejected POSIX input redirection for MySQL | 1 | Imported the schema using a PowerShell pipeline instead |
| Student dashboard body did not match after successful login redirect | 1 | Added response-body diagnostics to identify the query or rendering failure |
| Stage 2 assertion used title case for an uppercase eyebrow label | 1 | Matched the rendered accessible text and isolated mobile testing in a guest browser context |
| Browser console recorded the two intentionally tested 403 responses | 1 | Filtered only the expected denial-resource messages while retaining all other console failures |
| Consolidated PHP lint found a missing short-echo close in the assignment deadline class | 1 | Corrected the template expression before runtime testing |
| End-to-end test passed a root-relative edit link directly to `page.goto` | 1 | Prefixed the test server origin before navigation |
| Closed-assignment page did not show the expected status text | 1 | Confirmed database deadlines and added page diagnostics before changing behavior |
| Closed status assertion used title case while CSS-rendered label is uppercase | 1 | Matched the accessible rendered text; PHP deadline state was correct |
| Test attempted UI logout from a deliberate plain-text 403 download response | 1 | Return to the shared-layout homepage before using the logout menu when needed |
| Final security scan returned exit 1 when `rg` correctly found no direct upload links | 1 | Added an explicit successful exit after interpreting the no-match result |
| Combined session patch omitted a file boundary | 1 | Reapplied the focused edits with explicit file update markers |
| Git status was unavailable because the workspace is not a Git repository | 1 | Completed file-level verification and reported the repository state without attempting Git operations |
