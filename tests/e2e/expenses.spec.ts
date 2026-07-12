import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers';

const todayISO = new Date().toISOString().slice(0, 10);

test.describe('Expenses e2e', () => {
  test('create an expense via dialog and see it in the table', async ({ page, baseURL }) => {
    if (!baseURL) throw new Error('baseURL is not defined in Playwright config');

    await loginAsAdmin(page, baseURL);
    await page.goto(baseURL + '/expenses');
    await expect(page.getByRole('heading', { name: 'Expenses' })).toBeVisible();

    await page.getByRole('button', { name: 'New Expense' }).click();

    const dialog = page.locator('[role="dialog"]');
    await expect(dialog).toBeVisible();

    const uniqueDescription = `E2E expense ${Date.now()}`;
    await dialog.getByLabel('Description').fill(uniqueDescription);
    await dialog.getByLabel('Amount').fill('42.50');
    await dialog.getByLabel('Currency').fill('EUR');
    await dialog.getByLabel('Date').fill(todayISO);
    await dialog.getByLabel('Category').fill('Software');

    await dialog.getByRole('button', { name: 'Save' }).click();
    await expect(dialog).toBeHidden();

    await expect(page.locator('tr', { hasText: uniqueDescription })).toBeVisible();
  });
});
