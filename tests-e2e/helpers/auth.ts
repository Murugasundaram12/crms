import { Page, expect } from '@playwright/test';

export const ADMIN_CREDENTIALS = {
  email: process.env.E2E_ADMIN_EMAIL || 'admin@example.com',
  password: process.env.E2E_ADMIN_PASSWORD || 'password',
};

/**
 * Logs in as the Super Admin test user.
 */
export async function loginAsAdmin(page: Page): Promise<void> {
  await page.goto('login');
  await page.fill('input[name="email"], input[type="email"]', ADMIN_CREDENTIALS.email);
  await page.fill('input[name="password"], input[type="password"]', ADMIN_CREDENTIALS.password);
  await page.click('button[type="submit"]');

  await page.waitForURL(/.*dashboard.*/, { timeout: 15000 });
  await expect(page.locator('#sidebar, .sidebar')).toBeVisible({ timeout: 10000 });
}

/**
 * Logs out the authenticated user.
 */
export async function logoutUser(page: Page): Promise<void> {
  await page.goto('dashboard');
  await page.locator('.profile-dropdown > a.dropdown-toggle').click();
  await page.locator('form[action*="/logout"] button[type="submit"]').click();
  await page.waitForURL(/.*login.*/, { timeout: 15000 });
  await expect(page.locator('button[type="submit"]')).toBeVisible();
}

/**
 * Attaches page error and HTTP 500 failure tracking.
 */
export function trackPageIssues(page: Page) {
  const consoleErrors: string[] = [];
  const serverErrors: string[] = [];

  page.on('console', msg => {
    if (msg.type() === 'error') {
      const text = msg.text();
      // Ignore common non-fatal assets like missing favicons
      if (!text.includes('favicon')) {
        consoleErrors.push(text);
      }
    }
  });

  page.on('response', resp => {
    if (resp.status() >= 500) {
      serverErrors.push(`${resp.status()} ${resp.url()}`);
    }
  });

  return { consoleErrors, serverErrors };
}
