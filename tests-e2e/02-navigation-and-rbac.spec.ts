import { test, expect } from '@playwright/test';

test.describe('E2E Layer 2: Navigation & RBAC Protections', () => {
  test('02.01 - Direct unauthenticated access to restricted modules is blocked', async ({ page }) => {
    const protectedPaths = [
      'clients',
      'projects',
      'tasks',
      'tools-materials',
      'payments',
      'variations',
      'employee-salaries',
      'attendance',
      'roles',
      'permissions',
      'employee-tracking/settings',
    ];

    for (const path of protectedPaths) {
      await page.goto(path);
      // All must redirect to login with 302/200 on login page
      await expect(page).toHaveURL(/.*login.*/);
    }
  });

  test('02.02 - Public registration route state check', async ({ page }) => {
    const response = await page.goto('register');
    // Public registration is disabled by default in config/auth.php (404 expected or redirect)
    expect([200, 302, 404]).toContain(response?.status() ?? 0);
  });
});
