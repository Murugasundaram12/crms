import { test, expect } from '@playwright/test';

test.describe('E2E Layer 2: Core Modules UI Elements & Table Interactions', () => {
  test('03.01 - Forgot password page renders required inputs', async ({ page }) => {
    const consoleErrors: string[] = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    const response = await page.goto('forgot-password');
    expect(response?.status()).toBe(200);

    await expect(page.locator('input[name="email"], input[type="email"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
    expect(consoleErrors.filter(e => !e.includes('favicon'))).toEqual([]);
  });

  test('03.02 - Password reset form validates email format', async ({ page }) => {
    await page.goto('forgot-password');
    await page.fill('input[name="email"], input[type="email"]', 'not-an-email');
    await page.click('button[type="submit"]');

    // HTML5 validation or server response
    const isValid = await page.locator('input[name="email"], input[type="email"]').evaluate((el: HTMLInputElement) => el.checkValidity());
    expect(isValid).toBeFalsy();
  });
});
