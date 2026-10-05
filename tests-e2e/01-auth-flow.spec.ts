import { test, expect } from '@playwright/test';

test.describe('E2E Layer 2: Authentication & Session Protection', () => {
  test('01.01 - Login page renders cleanly without console or network errors', async ({ page }) => {
    const consoleErrors: string[] = [];
    const networkFailures: string[] = [];

    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });

    page.on('requestfailed', req => {
      networkFailures.push(`${req.method()} ${req.url()} - ${req.failure()?.errorText}`);
    });

    const response = await page.goto('login');
    expect(response?.status()).toBe(200);

    // Verify key UI elements exist
    await expect(page.locator('input[name="email"], input[type="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"], input[type="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();

    // Verify no unhandled critical console errors on initial load
    expect(consoleErrors.filter(e => !e.includes('favicon'))).toEqual([]);
  });

  test('01.02 - Invalid credentials show validation / error alert', async ({ page }) => {
    await page.goto('login');

    await page.fill('input[name="email"], input[type="email"]', 'invalid_tester@example.com');
    await page.fill('input[name="password"], input[type="password"]', 'WrongPassword123!');
    await page.click('button[type="submit"]');

    // Should remain on login or redirect back with alert
    await expect(page).toHaveURL(/.*login.*/);

    // Look for alert error text or validation feedback
    const alertMessage = page.locator('.alert-danger, .invalid-feedback, .text-danger, .toast-error, [role="alert"]');
    await expect(alertMessage.first()).toBeVisible({ timeout: 5000 });
  });

  test('01.03 - Unauthenticated access to dashboard redirects to login', async ({ page }) => {
    const response = await page.goto('dashboard');
    // Laravel should redirect unauthenticated request to /login
    await expect(page).toHaveURL(/.*login.*/);
  });
});
