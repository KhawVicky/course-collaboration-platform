from playwright.sync_api import sync_playwright


BASE = "http://127.0.0.1:8765"
errors = []


def sign_in(page, email, password):
    # Sign in during the authentication test.
    page.goto(f"{BASE}/auth/login.php", wait_until="networkidle")
    page.get_by_label("Email address").fill(email)
    page.get_by_label("Password", exact=True).fill(password)
    page.get_by_role("button", name="Sign in securely").click()
    page.wait_for_load_state("networkidle")


with sync_playwright() as playwright:
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context(viewport={"width": 1440, "height": 950})
    page = context.new_page()
    page.on("console", lambda message: errors.append(message.text) if message.type == "error" else None)
    page.on("pageerror", lambda error: errors.append(str(error)))

    page.goto(f"{BASE}/student/dashboard.php", wait_until="networkidle")
    assert page.url.endswith("/auth/login.php"), {"url": page.url, "body": page.locator("body").inner_text()[:1000]}

    page.goto(f"{BASE}/auth/register.php", wait_until="networkidle")
    page.get_by_label("Full name").fill("Stage Two Student")
    page.get_by_label("Email address").fill("stage2.student@example.com")
    page.get_by_label("Password", exact=True).fill("Register123!")
    page.get_by_label("Confirm password").fill("Register123!")
    page.get_by_role("button", name="Create student account").click()
    page.wait_for_load_state("networkidle")
    assert page.url.endswith("/auth/login.php"), {"url": page.url, "body": page.locator("body").inner_text()[:1000]}
    assert "Account created" in page.locator("body").inner_text()

    session_before_login = next(cookie["value"] for cookie in context.cookies() if cookie["name"] == "ccp_session")
    sign_in(page, "stage2.student@example.com", "Register123!")
    session_after_login = next(cookie["value"] for cookie in context.cookies() if cookie["name"] == "ccp_session")
    assert session_after_login != session_before_login
    assert page.url.endswith("/student/dashboard.php")
    assert "STUDENT WORKSPACE" in page.locator("body").inner_text()

    response = page.goto(f"{BASE}/instructor/dashboard.php", wait_until="networkidle")
    assert response is not None and response.status == 403
    assert "do not have access" in page.locator("body").inner_text()

    csrf_response = context.request.post(f"{BASE}/auth/logout.php", form={})
    assert csrf_response.status == 419

    page.goto(f"{BASE}/student/dashboard.php", wait_until="networkidle")
    page.get_by_role("button", name="Stage Two Student").click()
    page.get_by_role("button", name="Sign out").click()
    page.wait_for_load_state("networkidle")
    assert page.url.endswith("/auth/login.php")

    sign_in(page, "instructor@example.com", "Instructor123!")
    assert page.url.endswith("/instructor/dashboard.php")
    response = page.goto(f"{BASE}/student/dashboard.php", wait_until="networkidle")
    assert response is not None and response.status == 403

    mobile_context = browser.new_context(viewport={"width": 390, "height": 844})
    mobile = mobile_context.new_page()
    mobile.goto(f"{BASE}/auth/login.php", wait_until="networkidle")
    assert mobile.get_by_role("button", name="Toggle navigation").is_visible()
    visible_overflow = mobile.evaluate("""
        [...document.querySelectorAll('*')].filter((element) => {
            const box = element.getBoundingClientRect();
            return box.left < -1 || box.right > document.documentElement.clientWidth + 1;
        }).length
    """)
    assert visible_overflow == 0

    mobile_context.close()
    browser.close()

unexpected_errors = [error for error in errors if "403 (Forbidden)" not in error]
assert not unexpected_errors, unexpected_errors
print("Stage 2 authentication, CSRF, role access, desktop, and mobile checks passed")
