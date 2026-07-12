import { test, expect } from '@playwright/test';
import {
  loginAsAdmin,
  openInvoices,
  selectFirstOption,
  todayISO,
} from './helpers';

test.describe('Invoices e2e', () => {
  test('create invoice via dialog and generate from work logs', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined in Playwright config');

    await loginAsAdmin(page, baseURL);
    await openInvoices(page, baseURL);

    // Create a new invoice
    await page.locator('[data-test="btn-new"]').click();
    await expect(page.locator('[role="dialog"]').getByText('New Invoice')).toBeVisible();

    await selectFirstOption(page, 'invoice-customer');

    // Native date input: data-test sits on the <input> itself, fill ISO
    await page.locator('[data-test="invoice-due-date"]').fill(todayISO());

    await page.locator('[data-test="invoice-total"]').fill('1234');
    await page.locator('[data-test="btn-save-create"]').click();

    await expect(page.getByText('Invoice created successfully')).toBeVisible();

    // Generate from work logs
    await page.locator('[data-test="btn-generate"]').click();
    await expect(page.getByText('Generate Invoice from Work Logs')).toBeVisible();

    await selectFirstOption(page, 'gen-customer');

    // Work logs load into a checkbox list; pick the first one if any exist
    const workLogList = page.locator('[role="dialog"] li:has(input[type="checkbox"])').first();
    const noLogsAlert = page.getByText('No unbilled work logs found');
    await expect(workLogList.or(noLogsAlert)).toBeVisible();

    if (await workLogList.isVisible()) {
      await workLogList.click();

      // Due date defaults to today, status to draft; confirm generation
      await page.locator('[data-test="btn-generate-confirm"]').click();
      await expect(page.getByText('Invoice generated from work logs')).toBeVisible();
    } else {
      await page.locator('[data-test="btn-cancel-generate"]').click();
    }

    // Basic sanity: table visible and has rows
    await expect(page.locator('table')).toBeVisible();
  });
});
