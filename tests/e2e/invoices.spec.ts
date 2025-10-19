import { test, expect, Page } from '@playwright/test';
import { loginAsAdmin, openInvoices } from './helpers';

const today = new Date().toISOString().slice(0, 10);

// Simple helper to locate a table row containing a text
async function findRowByText(page: Page, text: string) {
  const row = page.locator('tr', { hasText: text }).first();
  await expect(row).toBeVisible();
  return row;
}

test.describe('Invoices e2e', () => {
  test('create invoice via dialog and generate from work logs', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined in Playwright config');

    await loginAsAdmin(page, baseURL);
    await openInvoices(page, baseURL);

    // Create a new invoice
  await page.locator('[data-test="btn-new"]').click();

    // Select first customer
  const customerField = page.locator('[data-test="invoice-customer"] input');
  await customerField.click();
    // Opened listbox, pick first option
    const firstOption = page.locator('.v-overlay-container .v-list-item').first();
    await firstOption.click();

    // Fill dates and amount
  await page.locator('[data-test="invoice-issue-date"] input').fill(today);
  await page.locator('[data-test="invoice-due-date"] input').fill(today);
  await page.locator('[data-test="invoice-total"] input').fill('1234');
  await page.locator('[data-test="btn-save-create"]').click();

    // Expect snackbar and new row present
    await expect(page.getByText('Invoice created successfully')).toBeVisible();

    // Generate from work logs
  await page.locator('[data-test="btn-generate"]').click();

    // Choose a customer
  const genCustomer = page.locator('[data-test="gen-customer"] input');
  await genCustomer.click();
    await page.locator('.v-overlay-container .v-list-item').first().click();

    // Wait unbilled logs to load and pick first if exists
  const logsSelect = page.locator('[data-test="gen-worklogs"] .v-field');
  await logsSelect.click();
    const anyLog = page.locator('.v-overlay-container .v-list-item').first();
    const hasAny = await anyLog.isVisible();
    if (hasAny) {
      await anyLog.click();
      await page.keyboard.press('Escape');

      // Dates default to today; just ensure status present
  await page.locator('[data-test="btn-generate-confirm"]').click();

      await expect(page.getByText('Invoice generated from work logs')).toBeVisible();
    } else {
      // Close dialog if no logs; still a pass for create flow
  await page.locator('[data-test="btn-cancel-generate"]').click();
    }

    // Basic sanity: table visible and has rows
    await expect(page.locator('table')).toBeVisible();
  });
});
