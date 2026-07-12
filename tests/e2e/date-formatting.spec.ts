import { test, expect } from '@playwright/test';
import {
  loginAsAdmin,
  setDateFormat,
  selectFirstOption,
  todayISO,
} from './helpers';

const FORMATS = ['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'] as const;

// These tests mutate the global date_format setting, so they must not run in
// parallel with each other (fullyParallel would race on the shared backend).
test.describe.configure({ mode: 'serial' });

// Since the Tailwind redesign, form date fields are native <input type="date">:
// their *value* is always ISO (YYYY-MM-DD) and the *display* follows the
// browser locale, not the app's date_format setting. The setting still governs
// dates rendered in tables and text. These tests therefore assert ISO values
// in form fields across every configured format.
test.describe('Date handling in dialogs', () => {
  test.beforeEach(async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined in Playwright config');
    await loginAsAdmin(page, baseURL);
  });

  for (const format of FORMATS) {
    test(`Invoice form date inputs hold ISO values with ${format} configured`, async ({ page, baseURL }) => {
      await setDateFormat(page, format);

      // Full navigation reloads the app so the store picks up the new format
      await page.goto(baseURL + '/invoices');
      await page.locator('[data-test="btn-new"]').click();
      await expect(page.locator('[role="dialog"]').getByText('New Invoice')).toBeVisible();

      // Issue date defaults to today (ISO value regardless of display setting)
      await expect(page.locator('[data-test="invoice-issue-date"]')).toHaveValue(todayISO());

      await page.locator('[data-test="invoice-due-date"]').fill(todayISO());
      await expect(page.locator('[data-test="invoice-due-date"]')).toHaveValue(todayISO());

      await page.locator('[data-test="btn-cancel-create"]').click();
    });
  }

  test('Work log form date field accepts ISO input', async ({ page, baseURL }) => {
    await setDateFormat(page, 'DD/MM/YYYY');

    await page.goto(baseURL + '/work-logs');
    await expect(page.getByRole('heading', { name: 'Work Logs' })).toBeVisible();

    await page.locator('[data-test="btn-new-worklog"]').click();

    const dateField = page
      .locator('[role="dialog"] div:has(> label:has-text("Date"))')
      .first()
      .locator('input');
    await expect(dateField).toBeVisible();

    await dateField.fill(todayISO());
    await expect(dateField).toHaveValue(todayISO());
  });

  test('Project form deadline accepts ISO input', async ({ page, baseURL }) => {
    await setDateFormat(page, 'MM/DD/YYYY');

    await page.goto(baseURL + '/projects');
    await expect(page.getByRole('heading', { name: 'Projects' })).toBeVisible();

    await page.locator('[data-test="btn-new-project"]').click();

    const deadlineField = page
      .locator('[role="dialog"] div:has(> label:has-text("Deadline"))')
      .first()
      .locator('input');
    await expect(deadlineField).toBeVisible();

    await deadlineField.fill(todayISO());
    await expect(deadlineField).toHaveValue(todayISO());
  });

  test('Invoice creation with date inputs works correctly', async ({ page, baseURL }) => {
    await setDateFormat(page, 'DD/MM/YYYY');

    await page.goto(baseURL + '/invoices');
    await page.locator('[data-test="btn-new"]').click();
    await expect(page.locator('[role="dialog"]').getByText('New Invoice')).toBeVisible();

    await selectFirstOption(page, 'invoice-customer');

    await page.locator('[data-test="invoice-due-date"]').fill(todayISO());

    await page.locator('[data-test="invoice-total"]').fill('500.00');
    await page.locator('[data-test="btn-save-create"]').click();

    await expect(page.getByText(/invoice created successfully/i)).toBeVisible();
  });

  test('Invoice edit dialog holds ISO dates', async ({ page, baseURL }) => {
    await setDateFormat(page, 'YYYY-MM-DD');

    await page.goto(baseURL + '/invoices');
    await expect(page.locator('table')).toBeVisible();

    // Seeded data guarantees invoices exist; open the first row's edit dialog
    const editButton = page.locator('table tbody tr').first().locator('button[title="Edit"]');
    await expect(editButton).toBeVisible();
    await editButton.click();

    await expect(page.locator('[role="dialog"]').getByText('Edit Invoice')).toBeVisible();

    await expect(page.locator('[data-test="invoice-issue-date"]')).toHaveValue(/^\d{4}-\d{2}-\d{2}$/);
    await expect(page.locator('[data-test="invoice-due-date"]')).toHaveValue(/^\d{4}-\d{2}-\d{2}$/);
  });
});
