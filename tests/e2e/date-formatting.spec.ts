import { test, expect } from '@playwright/test';
import {
  loginAsAdmin,
  setDateFormat,
  selectFirstOption,
  pickTodayInOpenDatePicker,
  formatDateString,
} from './helpers';

const FORMATS = ['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'] as const;

// These tests mutate the global date_format setting, so they must not run in
// parallel with each other (fullyParallel would race on the shared backend).
test.describe.configure({ mode: 'serial' });

test.describe('Date Formatting in Dialogs', () => {
  test.beforeEach(async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined in Playwright config');
    await loginAsAdmin(page, baseURL);
  });

  for (const format of FORMATS) {
    test(`Invoice form displays dates in ${format} format`, async ({ page, baseURL }) => {
      await setDateFormat(page, format);

      // Full navigation reloads the app so the store picks up the new format
      await page.goto(baseURL + '/invoices');
      await page.locator('[data-test="btn-new"]').click();
      await expect(page.locator('.v-dialog').getByText('New Invoice')).toBeVisible();

      const today = new Date();
      const expected = formatDateString(today, format);

      // Issue date defaults to today and must already render in the configured format
      await expect(page.locator('[data-test="invoice-issue-date"] input')).toHaveValue(expected);

      // Pick today as due date via the date picker and verify the display format
      await page.locator('[data-test="invoice-due-date"] input').click();
      await pickTodayInOpenDatePicker(page);
      await expect(page.locator('[data-test="invoice-due-date"] input')).toHaveValue(expected);

      await page.locator('[data-test="btn-cancel-create"]').click();
    });
  }

  test('Work log form respects date format settings', async ({ page, baseURL }) => {
    await setDateFormat(page, 'DD/MM/YYYY');

    await page.goto(baseURL + '/work-logs');
    await expect(page.getByRole('heading', { name: 'Work Logs' })).toBeVisible();

    await page.locator('[data-test="btn-new-worklog"]').click();

    const dateField = page
      .locator('.v-dialog')
      .locator('.v-text-field', { has: page.locator('label', { hasText: 'Date' }) })
      .first()
      .locator('input');
    await expect(dateField).toBeVisible();

    await dateField.click();
    await pickTodayInOpenDatePicker(page);

    await expect(dateField).toHaveValue(formatDateString(new Date(), 'DD/MM/YYYY'));
  });

  test('Project form deadline respects date format settings', async ({ page, baseURL }) => {
    await setDateFormat(page, 'MM/DD/YYYY');

    await page.goto(baseURL + '/projects');
    await expect(page.getByRole('heading', { name: 'Projects' })).toBeVisible();

    await page.locator('[data-test="btn-new-project"]').click();

    const deadlineField = page
      .locator('.v-dialog')
      .locator('.v-text-field', { has: page.locator('label', { hasText: 'Deadline' }) })
      .first()
      .locator('input');
    await expect(deadlineField).toBeVisible();

    await deadlineField.click();
    await pickTodayInOpenDatePicker(page);

    await expect(deadlineField).toHaveValue(formatDateString(new Date(), 'MM/DD/YYYY'));
  });

  test('Invoice creation with date pickers works correctly', async ({ page, baseURL }) => {
    await setDateFormat(page, 'DD/MM/YYYY');

    await page.goto(baseURL + '/invoices');
    await page.locator('[data-test="btn-new"]').click();
    await expect(page.locator('.v-dialog').getByText('New Invoice')).toBeVisible();

    await selectFirstOption(page, 'invoice-customer');

    // Issue date defaults to today; set due date via the picker
    await page.locator('[data-test="invoice-due-date"] input').click();
    await pickTodayInOpenDatePicker(page);

    await page.locator('[data-test="invoice-total"] input').fill('500.00');
    await page.locator('[data-test="btn-save-create"]').click();

    await expect(page.getByText(/invoice created successfully/i)).toBeVisible();
  });

  test('Invoice edit dialog displays existing dates in correct format', async ({ page, baseURL }) => {
    await setDateFormat(page, 'YYYY-MM-DD');

    await page.goto(baseURL + '/invoices');
    await expect(page.locator('table')).toBeVisible();

    // Seeded data guarantees invoices exist; open the first row's edit dialog
    const editButton = page.locator('table tbody tr').first().locator('button:has(.mdi-pencil)');
    await expect(editButton).toBeVisible();
    await editButton.click();

    await expect(page.locator('.v-dialog').getByText('Edit Invoice')).toBeVisible();

    await expect(page.locator('[data-test="invoice-issue-date"] input')).toHaveValue(/^\d{4}-\d{2}-\d{2}$/);
    await expect(page.locator('[data-test="invoice-due-date"] input')).toHaveValue(/^\d{4}-\d{2}-\d{2}$/);
  });
});
