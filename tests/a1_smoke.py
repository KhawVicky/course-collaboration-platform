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
    # Sign out from any application page.
    if page.get_by_role("button", name=account_name).count() == 0:
        page.goto(BASE, wait_until="networkidle")
    page.get_by_role("button", name=account_name).click()
    page.get_by_role("button", name="Sign out").click()
    page.wait_for_load_state("networkidle")
    assert page.url.endswith("/auth/login.php")


def numeric_query(url, key="id"):
    # Read a numeric ID from a link.
    match = re.search(rf"[?&]{key}=(\d+)", url)
    assert match, url
    return int(match.group(1))


with sync_playwright() as playwright:
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context(viewport={"width": 1440, "height": 1000}, accept_downloads=True)
    page = context.new_page()
    page.on("console", lambda message: console_errors.append(message.text) if message.type == "error" else None)
    page.on("pageerror", lambda error: console_errors.append(str(error)))

    # Instructor: course, material, assignments, announcement, and initial discussion.
    login(page, "instructor@example.com", "Instructor123!")
    assert page.url.endswith("/instructor/dashboard.php")
    page.goto(f"{BASE}/instructor/courses.php", wait_until="networkidle")
    page.get_by_label("Course code").fill("HCI101")
    page.get_by_label("Course title").fill("Human Computer Interaction")
    page.get_by_label("Description").fill("Design useful interactive systems through research and iteration.")
    page.get_by_role("button", name="Create course").click()
    page.wait_for_load_state("networkidle")
    course_id = numeric_query(page.url)
    assert "Human Computer Interaction" in page.locator("body").inner_text()

    page.goto(f"{BASE}/instructor/materials.php?course_id={course_id}", wait_until="networkidle")
    page.get_by_label("Material title").fill("Unsafe material")
    page.get_by_label("Description").fill("Must fail validation")
    page.get_by_label("File").set_input_files(str(ASSETS / "invalid.exe"))
    page.get_by_role("button", name="Upload material").click()
    assert "extension is not allowed" in page.locator("body").inner_text()
    page.get_by_label("Material title").fill("Week 1 Design Notes")
    page.get_by_label("Description").fill("Foundations and observation methods")
    page.get_by_label("File").set_input_files(str(ASSETS / "material.txt"))
    page.get_by_role("button", name="Upload material").click()
    page.wait_for_load_state("networkidle")
    assert "Course material uploaded" in page.locator("body").inner_text()
    material_href = page.get_by_role("link", name="Download").get_attribute("href")
    material_id = numeric_query(material_href)

    page.goto(f"{BASE}/instructor/assignments.php?course_id={course_id}", wait_until="networkidle")
    page.get_by_label("Title").fill("Prototype Reflection")
    page.get_by_label("Instructions").fill("Submit a concise reflection on the tested prototype and findings.")
    page.get_by_label("Deadline").fill("2027-12-31T23:59")
    page.get_by_label("Maximum grade").fill("100")
    page.get_by_role("button", name="Create assignment").click()
    page.wait_for_load_state("networkidle")
    edit_href = page.get_by_role("link", name="Edit").first.get_attribute("href")
    assignment_id = numeric_query(edit_href)
    page.goto(f"{BASE}{edit_href}", wait_until="networkidle")
    page.get_by_label("Title").fill("Prototype Reflection Updated")
    page.get_by_role("button", name="Save changes").click()
    page.wait_for_load_state("networkidle")
    assert "Assignment updated" in page.locator("body").inner_text()

    page.get_by_label("Title").fill("Closed Reflection")
    page.get_by_label("Instructions").fill("This past-deadline assignment verifies server-side deadline enforcement.")
    page.get_by_label("Deadline").fill("2025-01-01T10:00")
    page.get_by_label("Maximum grade").fill("50")
    page.get_by_role("button", name="Create assignment").click()
    page.wait_for_load_state("networkidle")
    closed_edit = page.get_by_role("link", name="Edit").first.get_attribute("href")
    closed_assignment_id = numeric_query(closed_edit)

    page.goto(f"{BASE}/instructor/announcements.php?course_id={course_id}", wait_until="networkidle")
    page.get_by_label("Title").fill("Studio session update")
    page.get_by_label("Announcement").fill("Bring your prototype and observation notes to the next studio.")
    page.get_by_role("button", name="Post announcement").click()
    page.wait_for_load_state("networkidle")
    assert "Announcement posted" in page.locator("body").inner_text()

    page.goto(f"{BASE}/discussions.php?course_id={course_id}", wait_until="networkidle")
    page.get_by_label("Thread title").fill("Prototype testing questions")
    page.get_by_label("Message").fill("Share one question you want your prototype test to answer.")
    page.get_by_role("button", name="Post thread").click()
    page.wait_for_load_state("networkidle")
    assert "Prototype testing questions" in page.locator("body").inner_text()
    logout(page, "Daniel Instructor")

    # Student: enrol, view/download content, submit twice, deadline rejection, announcements, discussions.
    login(page, "student@example.com", "Student123!")
    page.goto(f"{BASE}/student/course.php?id={course_id}", wait_until="networkidle")
    page.get_by_role("button", name="Enrol in this course").click()
    page.wait_for_load_state("networkidle")
    assert "now enrolled" in page.locator("body").inner_text()
    assert "Week 1 Design Notes" in page.locator("body").inner_text()
    material_response = context.request.get(f"{BASE}/download.php?type=material&id={material_id}")
    assert material_response.status == 200
    assert b"Course material verification" in material_response.body()

    page.get_by_role("link", name=re.compile("Prototype Reflection Updated")).click()
    page.wait_for_load_state("networkidle")
    page.get_by_label("Submission file").set_input_files(str(ASSETS / "submission-attempt-1.txt"))
    page.get_by_label("Note (optional)").fill("First attempt")
    page.get_by_role("button", name="Submit assignment").click()
    page.wait_for_load_state("networkidle")
    assert "Submission attempt 1 received" in page.locator("body").inner_text()
    page.get_by_label("Submission file").set_input_files(str(ASSETS / "submission-attempt-2.txt"))
    page.get_by_label("Note (optional)").fill("Improved second attempt")
    page.get_by_role("button", name="Submit new attempt").click()
    page.wait_for_load_state("networkidle")
    assert "Submission attempt 2 received" in page.locator("body").inner_text()
    assert "Latest attempt" in page.locator(".timeline-item.latest").inner_text()
    submission_href = page.locator(".timeline-item.latest a").get_attribute("href")
    submission_id = numeric_query(submission_href)

    page.goto(f"{BASE}/student/assignment.php?id={closed_assignment_id}", wait_until="networkidle")
    assert "SUBMISSIONS CLOSED" in page.locator("body").inner_text()
    assert page.get_by_role("button", name="Submit assignment").count() == 0
    csrf = page.locator('input[name="csrf_token"]').first.get_attribute("value")
    late_response = context.request.post(
        f"{BASE}/student/assignment.php?id={closed_assignment_id}",
        multipart={
            "csrf_token": csrf,
            "student_note": "Late attempt",
            "submission_file": {"name": "late.txt", "mimeType": "text/plain", "buffer": b"late"},
        },
    )
    assert late_response.status == 200
    assert "deadline has passed" in late_response.text().lower()

    page.goto(f"{BASE}/student/announcements.php", wait_until="networkidle")
    assert "Studio session update" in page.locator("body").inner_text()
    assert "Daniel Instructor" in page.locator("body").inner_text()
    page.goto(f"{BASE}/discussions.php?course_id={course_id}", wait_until="networkidle")
    page.get_by_label("Reply").first.fill("I want to test whether first-time users can find the primary action.")
    page.get_by_role("button", name="Reply").first.click()
    page.wait_for_load_state("networkidle")
    page.get_by_label("Thread title").fill("Student reflection thread")
    page.get_by_label("Message").fill("The strongest observation was a mismatch between labels and expectations.")
    page.get_by_role("button", name="Post thread").click()
    page.wait_for_load_state("networkidle")
    assert "Student reflection thread" in page.locator("body").inner_text()
    logout(page, "Aisha Student")

    # Another student cannot access discussions or submissions without enrolment/ownership.
    login(page, "student2@example.com", "Student123!")
    denied = page.goto(f"{BASE}/discussions.php?course_id={course_id}", wait_until="networkidle")
    assert denied is not None and denied.status == 403
    denied = page.goto(f"{BASE}/download.php?type=submission&id={submission_id}", wait_until="networkidle")
    assert denied is not None and denied.status == 403
    logout(page, "Mina Student")

    # Instructor: download, grade then update, view monitoring and enrolment.
    login(page, "instructor@example.com", "Instructor123!")
    page.goto(f"{BASE}/instructor/submissions.php?course_id={course_id}&assignment_id={assignment_id}", wait_until="networkidle")
    assert "Aisha Student" in page.locator("body").inner_text()
    submission_response = context.request.get(f"{BASE}/download.php?type=submission&id={submission_id}")
    assert submission_response.status == 200
    page.get_by_role("link", name="Grade").click()
    page.get_by_label(re.compile("Grade")).fill("85")
    page.get_by_label("Feedback").fill("Clear reflection with useful evidence.")
    page.get_by_role("button", name="Save grade and feedback").click()
    page.wait_for_load_state("networkidle")
    page.get_by_role("link", name="Update grade").click()
    page.get_by_label(re.compile("Grade")).fill("90")
    page.get_by_label("Feedback").fill("Updated: strong evidence and a precise next step.")
    page.get_by_role("button", name="Save grade and feedback").click()
    page.wait_for_load_state("networkidle")
    assert "90.00 / 100.00" in page.locator("body").inner_text()
    page.goto(f"{BASE}/instructor/monitoring.php?course_id={course_id}", wait_until="networkidle")
    monitoring_text = page.locator("body").inner_text()
    assert "Aisha Student" in monitoring_text
    assert "Prototype Reflection Updated: 90.00" in monitoring_text
    page.goto(f"{BASE}/instructor/course.php?id={course_id}", wait_until="networkidle")
    assert "Aisha Student" in page.locator("body").inner_text()
    logout(page, "Daniel Instructor")

    # Another instructor cannot manage or download from an unowned course.
    login(page, "instructor2@example.com", "Instructor123!")
    denied = page.goto(f"{BASE}/instructor/course.php?id={course_id}", wait_until="networkidle")
    assert denied is not None and denied.status == 404
    denied = page.goto(f"{BASE}/download.php?type=material&id={material_id}", wait_until="networkidle")
    assert denied is not None and denied.status == 403
    logout(page, "Nora Instructor")

    # Student sees the updated grade and feedback for the latest attempt.
    login(page, "student@example.com", "Student123!")
    page.goto(f"{BASE}/student/assignment.php?id={assignment_id}", wait_until="networkidle")
    final_text = page.locator("body").inner_text()
    assert "90.00 / 100.00" in final_text
    assert "Updated: strong evidence" in final_text
    logout(page, "Aisha Student")

    # Responsive guest and authenticated pages.
    mobile_context = browser.new_context(viewport={"width": 390, "height": 844})
    mobile = mobile_context.new_page()
    login(mobile, "student@example.com", "Student123!")
    mobile.goto(f"{BASE}/student/course.php?id={course_id}", wait_until="networkidle")
    assert mobile.get_by_role("button", name="Toggle navigation").is_visible()
    overflowing = mobile.evaluate("""
        [...document.querySelectorAll('body *')].filter((element) => {
            const style = getComputedStyle(element);
            const box = element.getBoundingClientRect();
            return style.position !== 'fixed' && (box.left < -2 || box.right > innerWidth + 2);
        }).map((element) => element.className).slice(0, 10)
    """)
    assert not overflowing, overflowing
    mobile_context.close()
    browser.close()

unexpected_errors = [
    error for error in console_errors
    if "403 (Forbidden)" not in error and "404 (Not Found)" not in error
]
assert not unexpected_errors, unexpected_errors
print(f"A1 end-to-end browser checks passed for course {course_id}, assignment {assignment_id}, submission {submission_id}")
