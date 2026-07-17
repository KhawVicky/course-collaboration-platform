# Findings

- The project workspace was empty at the start of Stage 1.
- There is no existing architecture, dependency manifest, code style, or project documentation to preserve.
- The requested stack targets XAMPP, so the implementation uses plain PHP includes and PDO without a package manager.
- The ten requested tables are sufficient for Stage 1 and can preserve submission history without introducing an extra history table.
- Upload directories are part of the final structure, but file handling is outside Stage 1.
- Stage 2–5 work can use the existing ten-table schema without adding membership limits or unrelated entities.
- `submissions.attempt_number` plus `is_latest` supports the required history when updates occur inside one transaction.
- The project has no framework or package manager; shared plain-PHP helpers are the smallest consistent architecture.
- Final visual QA showed the instructor desktop course workspace with all six management tools and the enrolled-student table correctly composed.
- Final mobile QA showed the student course page at 390px with readable navigation, materials, assignments, announcement content, and no clipped controls.
- The complete browser workflow proved two-attempt history, one latest attempt, late-upload rejection, updated grading, course communication, and cross-account denial.
- A2 requires two new one-to-one tables: `student_profiles` and `memberships`.
- Non-members may have attempts 1–3; attempt 4 must be rejected. Members remain unlimited before the deadline.
- The existing transaction is the correct place to lock membership and submission rows before choosing the next attempt number.
- Fresh installation creates 12 tables, two student membership records, and no instructor membership records.
- The A2 migration successfully adds both tables, fills missing students as Non-member, and leaves instructors without membership records.
- Browser and database evidence proves Member attempt 4 succeeds, Non-member attempt 4 fails, and rejected limit/deadline requests create no submission row.
- A1 end-to-end and database regression suites still pass after the A2 changes.
