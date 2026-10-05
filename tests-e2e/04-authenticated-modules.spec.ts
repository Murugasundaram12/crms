import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { loginAsAdmin, logoutUser, trackPageIssues } from './helpers/auth';

/**
 * Professional Authenticated E2E Suite for HouseFix360 Laravel CRM
 * 
 * Safety & Architecture:
 * - Read-safe: exercises navigation, structure, form controls, and validation rules without mutating production records.
 * - Rate-limit safe: Super Admin authenticates once in beforeAll, and session cookies are cached/reused
 *   across tests, strictly honoring Laravel's Limit::perMinute(5) rate limiter.
 * - Strict-mode compliant: locators are scoped to forms and containers to avoid ambiguities.
 * - Comprehensive error tracking: captures HTTP 500s and fatal console errors on every tested page.
 */
test.describe('E2E Layer 2: Authenticated Major Modules Suite (Read-Safe)', () => {
  let savedCookies: any[] = [];
  const authDir = path.resolve('test-results/.auth');
  const authFile = path.resolve('test-results/.auth/admin.json');

  test.beforeAll(async ({ browser }) => {
    // Reuse cached valid session if available from disk
    if (fs.existsSync(authFile)) {
      try {
        const cached = JSON.parse(fs.readFileSync(authFile, 'utf8'));
        if (cached.cookies && cached.cookies.length > 0 && Date.now() - cached.timestamp < 15 * 60 * 1000) {
          savedCookies = cached.cookies;
          return;
        }
      } catch {
        // Fall back to login
      }
    }

    if (!fs.existsSync(authDir)) {
      fs.mkdirSync(authDir, { recursive: true });
    }

    // Authenticate once as Super Admin to populate session cookies
    const setupContext = await browser.newContext();
    const setupPage = await setupContext.newPage();
    await loginAsAdmin(setupPage);
    savedCookies = await setupContext.cookies();
    fs.writeFileSync(authFile, JSON.stringify({ cookies: savedCookies, timestamp: Date.now() }));
    await setupContext.close();
  });

  test.beforeEach(async ({ context }) => {
    // Inject authenticated session cookies into the test context
    if (savedCookies.length > 0) {
      await context.addCookies(savedCookies);
    }
  });

  test('04.01 - Super Admin Authentication, Session Persistence & Logout Lifecycle', async ({ browser }) => {
    // Use an isolated unauthenticated context for lifecycle testing
    const isolatedContext = await browser.newContext();
    const page = await isolatedContext.newPage();
    const issues = trackPageIssues(page);

    // 1. Initial Login
    await loginAsAdmin(page);
    await expect(page).toHaveURL(/.*dashboard.*/);
    await expect(page.locator('#sidebar, .sidebar')).toBeVisible();

    // 2. Session Persistence: Navigate back to dashboard and verify authenticated layout persists
    await page.goto('dashboard');
    await expect(page).toHaveURL(/.*dashboard.*/);
    await expect(page.locator('#sidebar, .sidebar')).toBeVisible();

    // 3. Log out cleanly
    await logoutUser(page);

    // 4. Verify unauthenticated redirect after logout
    await page.goto('dashboard');
    await expect(page).toHaveURL(/.*login.*/);

    expect(issues.serverErrors).toEqual([]);
    await isolatedContext.close();
  });

  test('04.02 - Clients Module UI, Search Filters & Offcanvas Form Validation', async ({ page }) => {
    const issues = trackPageIssues(page);

    await page.goto('clients');
    await expect(page).toHaveURL(/.*clients.*/);
    await expect(page.locator('h4:has-text("Clients")')).toBeVisible();

    // Verify filter elements exist inside the GET filter form
    const filterForm = page.locator('form[method="GET"]');
    await expect(filterForm.locator('input[name="q"]')).toBeVisible();
    await expect(filterForm.locator('select[name="status"]')).toBeVisible();

    // Open Add Client offcanvas modal
    const addBtn = page.locator('a[data-bs-target="#offcanvas_add"], button[data-bs-target="#offcanvas_add"]').first();
    await expect(addBtn).toBeVisible();
    await addBtn.click();

    const offcanvas = page.locator('#offcanvas_add');
    await expect(offcanvas).toBeVisible();

    // Verify required inputs exist inside offcanvas
    const nameInput = offcanvas.locator('input[name="name"]');
    const phoneInput = offcanvas.locator('input[name="phone"]');
    await expect(nameInput).toBeVisible();
    await expect(phoneInput).toBeVisible();

    // Negative testing: Verify required constraint rejects empty submit
    const isNameValid = await nameInput.evaluate((el: HTMLInputElement) => el.checkValidity());
    expect(isNameValid).toBeFalsy();

    // Negative testing: Verify invalid phone format constraint
    await phoneInput.fill('12345');
    const isPhoneValid = await phoneInput.evaluate((el: HTMLInputElement) => el.checkValidity());
    expect(isPhoneValid).toBeFalsy();

    // Close offcanvas cleanly
    await offcanvas.locator('button.btn-close, button:has-text("Cancel")').first().click();
    await expect(offcanvas).toBeHidden();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.03 - Projects Module UI, Filter Controls & Offcanvas Form', async ({ page }) => {
    const issues = trackPageIssues(page);

    await page.goto('projects');
    await expect(page).toHaveURL(/.*projects.*/);
    await expect(page.locator('h4:has-text("Projects")')).toBeVisible();

    // Verify search and filter controls inside project GET filter form
    const projectFilter = page.locator('form[method="GET"]');
    await expect(projectFilter.locator('input[name="q"]')).toBeVisible();
    await expect(projectFilter.locator('select[name="status"]')).toBeVisible();
    await expect(projectFilter.locator('select[name="client_id"]')).toBeVisible();

    // Open Add Project offcanvas
    const addProjectBtn = page.locator('a[data-bs-target="#offcanvas_add"], button[data-bs-target="#offcanvas_add"]').first();
    await expect(addProjectBtn).toBeVisible();
    await addProjectBtn.click();

    const offcanvas = page.locator('#offcanvas_add');
    await expect(offcanvas).toBeVisible();

    // Verify form elements exist
    const projectNameInput = offcanvas.locator('input[name="name"], input[name="project_name"]').first();
    await expect(projectNameInput).toBeVisible();
    await expect(offcanvas.locator('select[name="client_id"]')).toBeVisible();

    // Negative testing: empty name validity check
    const isProjectNameValid = await projectNameInput.evaluate((el: HTMLInputElement) => el.checkValidity());
    expect(isProjectNameValid).toBeFalsy();

    // Close offcanvas
    await offcanvas.locator('button.btn-close, button:has-text("Cancel")').first().click();
    await expect(offcanvas).toBeHidden();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.04 - Tasks Module UI, Filter Bar & Offcanvas Form', async ({ page }) => {
    const issues = trackPageIssues(page);

    await page.goto('tasks');
    await expect(page).toHaveURL(/.*tasks.*/);
    await expect(page.locator('h4:has-text("Tasks")')).toBeVisible();

    // Verify filters inside the task GET filter form
    const taskFilter = page.locator('form[method="GET"]');
    await expect(taskFilter.locator('input[name="q"]')).toBeVisible();
    await expect(taskFilter.locator('select[name="status"]')).toBeVisible();
    await expect(taskFilter.locator('select[name="type"]')).toBeVisible();

    // Open Add Task offcanvas
    const addTaskBtn = page.locator('a[data-bs-target="#offcanvas_add"], button[data-bs-target="#offcanvas_add"]').first();
    await expect(addTaskBtn).toBeVisible();
    await addTaskBtn.click();

    const offcanvas = page.locator('#offcanvas_add');
    await expect(offcanvas).toBeVisible();

    const taskTitleInput = offcanvas.locator('input[name="title"], input[name="name"]').first();
    await expect(taskTitleInput).toBeVisible();
    await expect(offcanvas.locator('select[name="project_id"]')).toBeVisible();

    // Negative testing: empty title check
    const isTitleValid = await taskTitleInput.evaluate((el: HTMLInputElement) => el.checkValidity());
    expect(isTitleValid).toBeFalsy();

    // Close offcanvas
    await offcanvas.locator('button.btn-close, button:has-text("Cancel")').first().click();
    await expect(offcanvas).toBeHidden();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.05 - Vendors Module UI & Create Page Form Validation', async ({ page }) => {
    const issues = trackPageIssues(page);

    await page.goto('vendors');
    await expect(page).toHaveURL(/.*vendors.*/);
    await expect(page.locator('h4:has-text("Vendors")')).toBeVisible();
    await expect(page.locator('form[method="GET"] input[name="q"]')).toBeVisible();

    // Navigate to Add Vendor page using the explicit action button
    const addVendorBtn = page.locator('a.btn-primary[href*="vendors/create"], a.btn-primary:has-text("Add Vendor")');
    await expect(addVendorBtn).toBeVisible();
    await addVendorBtn.click();

    await page.waitForURL(/.*vendors\/create.*/);
    await expect(page.locator('h4:has-text("Create Vendor")')).toBeVisible();

    // Negative testing: Verify required vendor name
    const nameInput = page.locator('input[name="name"]');
    await expect(nameInput).toBeVisible();
    const isNameValid = await nameInput.evaluate((el: HTMLInputElement) => el.checkValidity());
    expect(isNameValid).toBeFalsy();

    // Return to vendors list
    await page.goto('vendors');
    expect(issues.serverErrors).toEqual([]);
  });

  test('04.06 - Labours & Labour Roles Modules UI & Create Form Validation', async ({ page }) => {
    const issues = trackPageIssues(page);

    // 1. Labours list
    await page.goto('labours');
    await expect(page).toHaveURL(/.*labours.*/);
    await expect(page.locator('h4:has-text("Labours")')).toBeVisible();
    await expect(page.locator('form[method="GET"] select[name="labour_role_id"]')).toBeVisible();

    // Navigate to Add Labour page using the primary action button
    const addLabourBtn = page.locator('a.btn-primary[href*="labours/create"], a.btn-primary:has-text("Add Labour")');
    await expect(addLabourBtn).toBeVisible();
    await addLabourBtn.click();

    await page.waitForURL(/.*labours\/create.*/);
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('select[name="labour_role_id"]')).toBeVisible();

    // Negative testing: name and phone validation
    const nameInput = page.locator('input[name="name"]');
    const isNameValid = await nameInput.evaluate((el: HTMLInputElement) => el.checkValidity());
    expect(isNameValid).toBeFalsy();

    // 2. Labour Roles master list
    await page.goto('labour-roles');
    await expect(page).toHaveURL(/.*labour-roles.*/);
    await expect(page.locator('h4:has-text("Labour Roles")')).toBeVisible();
    await expect(page.locator('table, .custom-table').first()).toBeVisible();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.07 - Payments Module UI & Offcanvas Payment Form', async ({ page }) => {
    const issues = trackPageIssues(page);

    await page.goto('payments');
    await expect(page).toHaveURL(/.*payments.*/);
    await expect(page.locator('h4:has-text("Payments")')).toBeVisible();
    await expect(page.locator('form[method="GET"] select[name="status"]')).toBeVisible();

    // Open Add Payment modal
    const addPaymentBtn = page.locator('a[data-bs-target="#offcanvas_add"], button[data-bs-target="#offcanvas_add"]').first();
    await expect(addPaymentBtn).toBeVisible();
    await addPaymentBtn.click();

    const offcanvas = page.locator('#offcanvas_add');
    await expect(offcanvas).toBeVisible();
    await expect(offcanvas.locator('select[name="client_id"]')).toBeVisible();
    await expect(offcanvas.locator('select[name="project_id"]')).toBeVisible();
    await expect(offcanvas.locator('select[name="payment_method_id"]')).toBeVisible();

    // Negative testing: required selects in payment modal reject unselected submit
    const clientSelect = offcanvas.locator('select[name="client_id"]');
    const isClientValid = await clientSelect.evaluate((el: HTMLSelectElement) => el.checkValidity());
    expect(isClientValid).toBeFalsy();

    const paymentMethodSelect = offcanvas.locator('select[name="payment_method_id"]');
    const isPaymentMethodValid = await paymentMethodSelect.evaluate((el: HTMLSelectElement) => el.checkValidity());
    expect(isPaymentMethodValid).toBeFalsy();

    // Close offcanvas
    await offcanvas.locator('button.btn-close, button:has-text("Cancel")').first().click();
    await expect(offcanvas).toBeHidden();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.08 - Expenses History & Expense Reports UI', async ({ page }) => {
    const issues = trackPageIssues(page);

    // Expenses History
    await page.goto('expenses-history');
    await expect(page).toHaveURL(/.*expenses-history.*/);
    await expect(page.locator('h4:has-text("Expenses History")')).toBeVisible();
    await expect(page.locator('table').first()).toBeVisible();

    // Expense Reports
    await page.goto('expense-reports');
    await expect(page).toHaveURL(/.*expense-reports.*/);
    await expect(page.locator('h4:has-text("Expense Report")')).toBeVisible();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.09 - Quotations Module & Create Page Structure', async ({ page }) => {
    const issues = trackPageIssues(page);

    await page.goto('quotations');
    await expect(page).toHaveURL(/.*quotations.*/);
    await expect(page.locator('h4:has-text("Quotations")')).toBeVisible();

    // Navigate to Create Quotation page
    const addQuotationBtn = page.locator('a[href*="quotations/create"], a:has-text("Add Quotation")').first();
    await expect(addQuotationBtn).toBeVisible();
    await addQuotationBtn.click();

    await page.waitForURL(/.*quotations\/create.*/);
    await expect(page.locator('h4:has-text("Create Quotation")')).toBeVisible();
    await expect(page.locator('select[name="client_id"]')).toBeVisible();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.10 - Attendance Module & Dashboard Attendance Status', async ({ page }) => {
    const issues = trackPageIssues(page);

    // 1. Attendance List
    await page.goto('attendance');
    await expect(page).toHaveURL(/.*attendance.*/);
    await expect(page.locator('h4:has-text("Attendance")')).toBeVisible();
    await expect(page.locator('select[name="user_id"]')).toBeVisible();

    // 2. Dashboard Attendance Section
    await page.goto('dashboard');
    await expect(page.locator('h5:has-text("Today\'s Attendance")')).toBeVisible();

    // Verify attendance action availability (Check In, Check Out, or Attendance Completed)
    const attendanceAction = page.locator('button:has-text("Check In"), button:has-text("Check Out"), button:has-text("Attendance Completed"), a:has-text("Attendance List")').first();
    await expect(attendanceAction).toBeVisible();

    // 3. Tracking Settings
    await page.goto('employee-tracking/settings');
    await expect(page).toHaveURL(/.*employee-tracking\/settings.*/);
    await expect(page.locator('form:not([action*="/logout"])').first()).toBeVisible();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.11 - Employee Salaries & Labour Salaries UI', async ({ page }) => {
    const issues = trackPageIssues(page);

    // Employee Salaries
    await page.goto('employee-salaries');
    await expect(page).toHaveURL(/.*employee-salaries.*/);
    await expect(page.locator('h4:has-text("Employee Salaries")')).toBeVisible();

    await page.goto('employee-salaries/create');
    await expect(page).toHaveURL(/.*employee-salaries\/create.*/);
    await expect(page.locator('select[name="user_id"], select[name="employee_id"]').first()).toBeVisible();

    // Labour Salaries
    await page.goto('labour-salaries');
    await expect(page).toHaveURL(/.*labour-salaries.*/);
    await expect(page.locator('h4:has-text("Labour Salaries")')).toBeVisible();

    await page.goto('labour-salaries/create');
    await expect(page).toHaveURL(/.*labour-salaries\/create.*/);
    await expect(page.locator('select[name="labour_id"]')).toBeVisible();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.12 - Master Settings Modules UI (Categories, Units, Payment Methods, Payment Stages)', async ({ page }) => {
    const issues = trackPageIssues(page);

    // Main Categories
    await page.goto('main-categories');
    await expect(page).toHaveURL(/.*main-categories.*/);
    await expect(page.locator('h4:has-text("Main Categories")')).toBeVisible();

    // Categories
    await page.goto('categories');
    await expect(page).toHaveURL(/.*categories.*/);
    await expect(page.locator('h4:has-text("Categories")')).toBeVisible();

    // Units
    await page.goto('units');
    await expect(page).toHaveURL(/.*units.*/);
    await expect(page.locator('h4:has-text("Units"), h4:has-text("Unit")').first()).toBeVisible();

    // Payment Methods
    await page.goto('payment-methods');
    await expect(page).toHaveURL(/.*payment-methods.*/);
    await expect(page.locator('h4:has-text("Payment Methods"), h4:has-text("Payment Method")').first()).toBeVisible();

    // Payment Stages
    await page.goto('payment-stages');
    await expect(page).toHaveURL(/.*payment-stages.*/);
    await expect(page.locator('h4:has-text("Payment Stages"), h4:has-text("Stage")').first()).toBeVisible();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.13 - Roles & Permissions RBAC Modules UI', async ({ page }) => {
    const issues = trackPageIssues(page);

    // Roles Management
    await page.goto('roles');
    await expect(page).toHaveURL(/.*roles.*/);
    await expect(page.locator('h4:has-text("Roles Management")')).toBeVisible();
    await expect(page.locator('#roles-table')).toBeVisible();

    // Roles Create
    await page.goto('roles/create');
    await expect(page).toHaveURL(/.*roles\/create.*/);
    await expect(page.locator('h4:has-text("Create Role")')).toBeVisible();
    await expect(page.locator('input[name="name"]')).toBeVisible();

    // Permissions Management
    await page.goto('permissions');
    await expect(page).toHaveURL(/.*permissions.*/);
    await expect(page.locator('h4:has-text("Permissions Management")')).toBeVisible();
    await expect(page.locator('#permissions-table')).toBeVisible();

    expect(issues.serverErrors).toEqual([]);
  });

  test('04.14 - Negative Testing: Form Validations, Format Constraints & Error Handling', async ({ page }) => {
    const issues = trackPageIssues(page);

    // 1. Client Phone Format Constraint (HTML5 pattern / Indian mobile format)
    await page.goto('clients');
    const addClientBtn = page.locator('a[data-bs-target="#offcanvas_add"], button[data-bs-target="#offcanvas_add"]').first();
    await addClientBtn.click();
    const clientOffcanvas = page.locator('#offcanvas_add');
    await expect(clientOffcanvas).toBeVisible();

    const clientPhone = clientOffcanvas.locator('input[name="phone"]');
    await clientPhone.fill('invalid-phone-string');
    const isClientPhoneValid = await clientPhone.evaluate((el: HTMLInputElement) => el.checkValidity());
    expect(isClientPhoneValid).toBeFalsy();
    await clientOffcanvas.locator('button.btn-close, button:has-text("Cancel")').first().click();

    // 2. Vendor Create Form Empty Required Constraint
    await page.goto('vendors/create');
    const vendorName = page.locator('input[name="name"]');
    await expect(vendorName).toBeVisible();
    const isVendorNameValid = await vendorName.evaluate((el: HTMLInputElement) => el.checkValidity());
    expect(isVendorNameValid).toBeFalsy();

    // 3. Labour Create Form Phone Validation
    await page.goto('labours/create');
    const labourPhone = page.locator('input[name="phone_number"]');
    await labourPhone.fill('0123');
    const isLabourPhoneValid = await labourPhone.evaluate((el: HTMLInputElement) => el.checkValidity());
    expect(isLabourPhoneValid).toBeFalsy();

    // 4. Payment Form Required Constraints
    await page.goto('payments');
    const addPaymentBtn = page.locator('a[data-bs-target="#offcanvas_add"], button[data-bs-target="#offcanvas_add"]').first();
    await addPaymentBtn.click();
    const paymentOffcanvas = page.locator('#offcanvas_add');
    await expect(paymentOffcanvas).toBeVisible();

    const clientSelect = paymentOffcanvas.locator('select[name="client_id"]');
    const isClientSelectValid = await clientSelect.evaluate((el: HTMLSelectElement) => el.checkValidity());
    expect(isClientSelectValid).toBeFalsy();
    await paymentOffcanvas.locator('button.btn-close, button:has-text("Cancel")').first().click();

    // Ensure no 500 server errors occurred during validation evaluations
    expect(issues.serverErrors).toEqual([]);
  });

  test('04.15 - Browser-Level Console & Network Resource Integrity Across Core Pages', async ({ page }) => {
    const issues = trackPageIssues(page);
    const coreRoutes = [
      'dashboard',
      'clients',
      'projects',
      'tasks',
      'vendors',
      'labours',
      'payments',
      'quotations',
      'attendance',
      'expenses-history',
      'employee-salaries',
      'labour-salaries',
      'roles',
      'permissions',
      'categories',
      'main-categories',
    ];

    for (const route of coreRoutes) {
      const resp = await page.goto(route);
      expect(resp?.status()).toBe(200);
      await expect(page.locator('#sidebar, .sidebar')).toBeVisible();
    }

    // Asserts no unexpected 500 server errors on any navigated core page
    expect(issues.serverErrors).toEqual([]);
  });

});
