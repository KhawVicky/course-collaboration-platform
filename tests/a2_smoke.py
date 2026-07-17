import re
from pathlib import Path

from playwright.sync_api import sync_playwright


BASE = "http://127.0.0.1:8765"
ASSETS = Path(__file__).resolve().parent / "assets"
console_errors = []


def login(page, email, password):
    # Sign in with one test account.
    page.goto(f"{BASE}/auth/login.php", wait_until="networkidle")
    page.get_by_label("Email address").fill(email)
    page.get_by_label("Password", exact=True).fill(password)
    page.get_by_role("button", name="Sign in securely").click()
    page.wait_for_load_state("networkidle")


def logout(page, account_name):
    # Return to a shared page before signing out after a plain error response.
    if page.get_by_role("button", name=account_name).count() == 0:
        page.goto(BASE, wait_until="networkidle")
    page.get_by_role("button", name=account_name).click()
    page.get_by_role("button", name="Sign out").click()
    page.wait_for_load_state("networkidle")
    assert page.url.endswith("/auth/login.php")


def numeric_query(url, key="id"):
    # Read a numeric record ID from a URL.
    match = re.search(rf"[?&]{key}=(\d+)", url)
    assert match, url
    return int(match.group(1))


def create_assignment(page, course_id, title, deadline):
    # Create one assignment and return its ID from the edit link.
    page.goto(f"{BASE}/instructor/assignments.php?course_id={course_id}", wait_until="networkidle")
    page.get_by_label("Title").fill(title)
    page.get_by_label("Instructions").fill(f"Test instructions for {title}.")
    page.get_by_label("Deadline").fill(deadline)
    page.get_by_label("Maximum grade").fill("100")
    page.get_by_role("button", name="Create assignment").click()
    page.wait_for_load_state("networkidle")
    row = page.locator(".assignment-admin", has_text=title)
    assert row.count() == 1
    return numeric_query(row.get_by_role("link", name="Edit").get_attribute("href"))


def submit_attempt(page, assignment_id, attempt_number, expected_button):
    # Upload a valid text file as the next assignment attempt.
    page.goto(f"{BASE}/student/assignment.php?id={assignment_id}", wait_until="networkidle")
    page.get_by_label("Submission file").set_input_files(str(ASSETS / "submission-attempt-1.txt"))
    page.get_by_label("Note (optional)").fill(f"A2 attempt {attempt_number}")
    page.get_by_role("button", name=expected_button).click()
    page.wait_for_load_state("networkidle")
    assert f"Submission attempt {attempt_number} received" in page.locator("body").inner_text()


def csrf_from_page(page):
    # Use the session token rendered in the current page.
    token = page.locator('input[name="csrf_token"]').first.get_attribute("value")
    assert token
    return token


def assert_no_horizontal_overflow(page):
    # Check that normal content stays inside the current viewport.
    overflowing = page.evaluate(
        """
        [...document.querySelectorAll('body *')].filter((element) => {
            const style = getComputedStyle(element);
            const box = element.getBoundingClientRect();
            return style.position !== 'fixed' && (box.left < -2 || box.right > innerWidth + 2);
        }).map((element) => element.className || element.tagName).slice(0, 10)
        """
    )
    assert not overflowing, overflowing


with sync_playwright() as playwright:
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context(viewport={"width": 1440, "height": 1000})
    page = context.new_page()
    page.on("console", lambda message: console_errors.append(message.text) if message.type == "error" else None)
    page.on("pageerror", lambda error: console_errors.append(str(error)))

    # Register a new student and confirm the normal registration flow still works.
    page.goto(f"{BASE}/auth/register.php", wait_until="networkidle")
    page.get_by_label("Full name").fill("New A2 Student")
    page.get_by_label("Email address").fill("newa2@example.com")
    page.get_by_label("Password", exact=True).fill("Student123!")
    page.get_by_label("Confirm password").fill("Student123!")
    page.get_by_role("button", name="Create student account").click()
    page.wait_for_load_state("networkidle")
    assert page.url.endswith("/auth/login.php")
    assert "Account created" in page.locator("body").inner_text()

    # Create one course plus future and closed assignments as the owning instructor.
    login(page, "instructor@example.com", "Instructor123!")
    page.goto(f"{BASE}/instructor/courses.php", wait_until="networkidle")
    page.get_by_label("Course code").fill("A2T101")
    page.get_by_label("Course title").fill("A2 Collaboration Testing")
    page.get_by_label("Description").fill("A focused course for profile and membership rule verification.")
    page.get_by_role("button", name="Create course").click()
    page.wait_for_load_state("networkidle")
    course_id = numeric_query(page.url)
    future_assignment_id = create_assignment(page, course_id, "Future Collaboration Task", "2035-12-31T23:59")
    closed_assignment_id = create_assignment(page, course_id, "Closed Collaboration Task", "2025-01-01T10:00")
    logout(page, "Daniel Instructor")

    # The member student saves skills, both modes, and optional availability.
    login(page, "student@example.com", "Student123!")
    page.goto(f"{BASE}/student/profile.php", wait_until="networkidle")
    assert "Member" in page.locator(".membership-card").inner_text()
    page.get_by_label("Skills or expertise").fill("PHP, interface design, usability testing")
    page.locator(".mode-options label", has_text="Online").click()
    page.get_by_label("Availability (optional)").fill("Monday 7-9 PM and Saturday morning")
    page.get_by_role("button", name="Save profile").click()
    page.wait_for_load_state("networkidle")
    assert "collaboration profile was updated" in page.locator("body").inner_text()
    assert page.get_by_label("Online").is_checked()
    assert page.get_by_label("Skills or expertise").input_value() == "PHP, interface design, usability testing"
    assert page.get_by_label("Availability (optional)").input_value() == "Monday 7-9 PM and Saturday morning"
    page.locator(".mode-options label", has_text="Offline").click()
    page.get_by_role("button", name="Save profile").click()
    page.wait_for_load_state("networkidle")
    assert page.get_by_label("Offline").is_checked()

    # A student cannot open another student's profile or bypass CSRF.
    denied = page.goto(f"{BASE}/student/profile.php?user_id=3", wait_until="networkidle")
    assert denied is not None and denied.status == 403
    page.goto(f"{BASE}/student/profile.php", wait_until="networkidle")
    no_csrf = context.request.post(
        f"{BASE}/student/profile.php",
        form={"full_name": "Changed Without Token", "collaboration_mode": "Online"},
    )
    assert no_csrf.status == 419

    # Enrol and prove that a member can submit more than two resubmissions.
    page.goto(f"{BASE}/student/course.php?id={course_id}", wait_until="networkidle")
    page.get_by_role("button", name="Enrol in this course").click()
    page.wait_for_load_state("networkidle")
    for attempt in range(1, 5):
        submit_attempt(page, future_assignment_id, attempt, "Submit assignment" if attempt == 1 else "Submit new attempt")
    member_text = page.locator("body").inner_text()
    assert "Membership: Member" in member_text
    assert "Resubmissions: Unlimited before deadline" in member_text
    current_attempt = page.locator(".resubmission-status > div", has_text="Current attempt").locator("strong")
    assert current_attempt.inner_text() == "4"

    # A direct member POST after the deadline is rejected.
    page.goto(f"{BASE}/student/assignment.php?id={closed_assignment_id}", wait_until="networkidle")
    member_late = context.request.post(
        f"{BASE}/student/assignment.php?id={closed_assignment_id}",
        multipart={
            "csrf_token": csrf_from_page(page),
            "student_note": "Late member attempt",
            "submission_file": {"name": "late-member.txt", "mimeType": "text/plain", "buffer": b"late"},
        },
    )
    assert member_late.status == 200
    assert "deadline has passed" in member_late.text().lower()
    logout(page, "Aisha Student")

    # The non-member cannot change role or membership with extra POST fields.
    login(page, "student2@example.com", "Student123!")
    page.goto(f"{BASE}/student/profile.php", wait_until="networkidle")
    protected_update = context.request.post(
        f"{BASE}/student/profile.php",
        form={
            "csrf_token": csrf_from_page(page),
            "full_name": "Mina Student",
            "skills": "Documentation and presentation",
            "collaboration_mode": "Online",
            "availability": "Friday afternoon",
            "role": "instructor",
            "membership_status": "Member",
        },
    )
    assert protected_update.status == 200
    assert protected_update.url.endswith("/student/profile.php")

    # Enrol and use the original submission plus exactly two resubmissions.
    page.goto(f"{BASE}/student/course.php?id={course_id}", wait_until="networkidle")
    page.get_by_role("button", name="Enrol in this course").click()
    page.wait_for_load_state("networkidle")
    for attempt in range(1, 4):
        submit_attempt(page, future_assignment_id, attempt, "Submit assignment" if attempt == 1 else "Submit new attempt")
    nonmember_text = page.locator("body").inner_text()
    assert "Membership: Non-member" in nonmember_text
    assert "Resubmissions used: 2 of 2" in nonmember_text
    assert "Remaining: 0" in nonmember_text
    assert "Resubmission limit reached" in nonmember_text
    limit_token = csrf_from_page(page)

    # A direct fourth attempt cannot bypass the PHP limit.
    rejected = context.request.post(
        f"{BASE}/student/assignment.php?id={future_assignment_id}",
        multipart={
            "csrf_token": limit_token,
            "student_note": "Forbidden fourth attempt",
            "submission_file": {"name": "attempt-4.txt", "mimeType": "text/plain", "buffer": b"blocked"},
        },
    )
    assert rejected.status == 200
    assert "Resubmission limit reached" in rejected.text()
    page.goto(f"{BASE}/student/assignment.php?id={future_assignment_id}", wait_until="networkidle")
    assert page.locator(".timeline-item").count() == 3

    # A direct non-member POST after the deadline is also rejected.
    page.goto(f"{BASE}/student/assignment.php?id={closed_assignment_id}", wait_until="networkidle")
    nonmember_late = context.request.post(
        f"{BASE}/student/assignment.php?id={closed_assignment_id}",
        multipart={
            "csrf_token": csrf_from_page(page),
            "student_note": "Late non-member attempt",
            "submission_file": {"name": "late-nonmember.txt", "mimeType": "text/plain", "buffer": b"late"},
        },
    )
    assert nonmember_late.status == 200
    assert "deadline has passed" in nonmember_late.text().lower()
    logout(page, "Mina Student")

    # The course owner can see enrolled collaboration profiles.
    login(page, "instructor@example.com", "Instructor123!")
    page.goto(f"{BASE}/instructor/student_profile.php?course_id={course_id}&student_id=1", wait_until="networkidle")
    owner_view = page.locator("body").inner_text()
    assert "Aisha Student" in owner_view
    assert "PHP, interface design, usability testing" in owner_view
    assert "Offline" in owner_view
    assert "Monday 7-9 PM and Saturday morning" in owner_view
    assert_no_horizontal_overflow(page)
    logout(page, "Daniel Instructor")

    # A different instructor cannot view the same student's profile.
    login(page, "instructor2@example.com", "Instructor123!")
    denied = page.goto(f"{BASE}/instructor/student_profile.php?course_id={course_id}&student_id=1", wait_until="networkidle")
    assert denied is not None and denied.status == 404
    logout(page, "Nora Instructor")

    # Verify the key A2 pages at a mobile viewport.
    mobile_context = browser.new_context(viewport={"width": 390, "height": 844})
    mobile = mobile_context.new_page()
    login(mobile, "student@example.com", "Student123!")
    mobile.goto(f"{BASE}/student/profile.php", wait_until="networkidle")
    assert mobile.get_by_role("button", name="Toggle navigation").is_visible()
    assert_no_horizontal_overflow(mobile)
    mobile.goto(f"{BASE}/student/assignment.php?id={future_assignment_id}", wait_until="networkidle")
    mobile_attempt = mobile.locator(".resubmission-status > div", has_text="Current attempt").locator("strong")
    assert mobile_attempt.inner_text() == "4"
    assert_no_horizontal_overflow(mobile)
    mobile_context.close()
    context.close()
    browser.close()

unexpected_errors = [
    error for error in console_errors
    if "403 (Forbidden)" not in error and "404 (Not Found)" not in error and "419" not in error
]
assert not unexpected_errors, unexpected_errors
print(
    "A2 browser checks passed for profiles, membership limits, direct POST enforcement, "
    f"role access, deadline blocking, and responsive pages (course {course_id})."
)
